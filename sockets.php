<?PHP

class SocketProcessor {
    
    protected $sockets;
    
    public function addSocket($socket_id, $type, $params = NULL){
        $this->sockets[$socket_id] = new Socket($socket_id, $type, $params);   
    }
    
    public function removeSocket($socket_id){
        if (!$this->socket($socket_id)->close()){
            $this->throwError($this->socket($socket_id)->getError());
            return FALSE;
        }
        unset($this->sockets[$socket_id]);        
    }
    
    public function socket($socket_id){
        if (is_scalar($socket_id)) return $this->sockets[$socket_id];
        else return $this->lookup_table[$socket_id];
    }
    
    protected function rebuildLookupTable(){
        $this->lookup_table = array();
        foreach ($this->sockets as $socket){
            $this->lookup_table[$socket->socket()] = $socket;
        }
    }
    
    protected function throwError($err){
        //echo "SocketProcessor Error: $err\n";
        debug("SocketProcessor Error: $err");
    }
    
    public function poll(){
        
        $read = array(); $write = array(); $except = array();
        foreach ($this->sockets as $socket_id => $socket){
        
            if (!$socket->autoReconnect()) $this->throwError($socket->getError());
            
            if ($socket->isConnected()){
                
                // queue up writes 
                if ($socket->hasWrite()){
                    $write[$socket_id] = $socket->socket();            
                }
                $read[$socket_id] = $socket->socket();
                $except[$socket_id] = $socket->socket();
            }
        }
        
        if (empty($read) && empty($write) && empty($except)) return TRUE;
        
        // socket select()
        $affected = socket_select($read, $write, $except, 1);    
        
        if ($affected){   
        
            // rebuild the ghetto fucking lookup table
            $this->rebuildLookupTable();
            
            foreach ($except as $socket_id => $socket_r){
                $socket = $this->socket($socket_r);
                if (!$socket->socketExcept()){
                    $this->throwError($socket->getError());
                }
            }
            foreach ($write as $socket_id => $socket_r){
                $socket = $this->socket($socket_r);
                if (!$socket->socketWrite()){
                    $this->throwError($socket->getError());
                }
            }
            
            foreach ($read as $socket_id => $socket_r){
                $socket = $this->socket($socket_r);
                if (!$socket->socketRead()){
                    $this->throwError($socket->getError());
                }
            }
            
        }
        
        return TRUE;
    }
}


class Socket {
    
    // define socket type
    static $RECONNECT = 1;
    static $REMOVE = 2;
    static $PASSIVE = 3;
    static $LISTENER = 4;
    
    protected $callback = NULL;
    protected $params = NULL;
    protected $read = NULL;
    protected $read_queue = NULL;
    protected $write_queue = NULL;
    protected $except = NULL;
    protected $connected = FALSE;
    protected $error = FALSE;
    protected $clients = array();
    
    function __construct($socket_id, $type, $params = NULL){
        $this->socket_id = $socket_id;
        $this->params = $params;
        $this->type = $type;
        $this->callback = (array($this, "addToReadQueue"));        
        debug("constructed Socket object");
    }
    
    function __destruct(){
        $this->close();
    }
    
    public function & socket(){
        return $this->socket;
    }
    
    protected function setSocket($socket){
        unset($this->socket);
        $this->socket = $socket;
    }
    
    public function socketType(){
        return $this->type;
    }   
    
    public function addClient($client){
        $this->clients[] = $client;
        
    }
    
    public function autoReconnect(){
        
        if ($this->isConnected()) return TRUE;
        switch ($this->type){
            case self::$RECONNECT: return $this->connectOutbound();
            case self::$LISTENER: return $this->setupListener();
            default: return TRUE;
        }
    }
    
    protected function connectOutbound(){
    
        debug("connecting to client...");
        if ($this->type != self::$RECONNECT) return $this->throwError('connectOubound called on non-autoreconnect socket');
        if ($this->isConnected()) return $this->throwError('attempted to connectOutbound on an already connected socket');

        // try to reconnect
        $this->socket = socket_create($this->params['domain'], $this->params['type'], $this->params['protocol']);
        if (!$this->socket) return $this->throwError('socket create error');        
        
        @$res = socket_connect($this->socket, $this->params['addr'], $this->params['port']);
        if (!$res){
            //if (socket_last_error($this->socket) == 111) sleep (1);
            return $this->throwError('socket connect error');
        }
            
        
        $this->connected = TRUE;      
        debug("connected to client!");
        return TRUE;
    }
    
    protected function setupListener(){
    
        $num_clients = count($this->clients);
        
        if ($this->isConnected()) return $this->throwError('attempted to setupListener on an already connected socket');
        
        if (!$this->params['port']) return $this->throwError('listener port not defined');
        
        @$res = $this->socket = socket_create_listen($this->params['port'], $num_clients-1);
        if (!$res){
            sleep(1);
            return $this->throwError('unable to bind, port probably busy');
        }
        if (!$this->socket) return $this->throwError('socket listener creation failed');
        $this->setConnected(TRUE);
        debug("set up listener on port ".$this->params['port']);
        return TRUE;
    }
    
    protected function socketAccept(){
        
        if ($this->type != self::$LISTENER) return $this->throwError('called socketAccept on a non-listener socket');        
        
        // check if we've accepted a connection
        if (!($new_socket = socket_accept($this->socket))) return $this->throwError('socket_accept failed');
        
        
        socket_set_block($new_socket);
        foreach ($this->clients as $socket){
            if (!$socket->isConnected()){
                $socket->setSocket($new_socket);
                $socket->setConnected(TRUE);   
                debug("socket accepted!");
                return TRUE;                            
            }
        }                    
        socket_close($new_socket);
        return $this->throwError("accepted socket has nowhere to go");                                                
    }
    
    
    public function setCallback($callback){
        $this->callback = $callback;
    }
    
    public function socketWrite(){
        // socket write
        if (!empty($this->write_queue)){
            while ($write = array_shift($this->write_queue)){
                // do a little delay dance here for robots
                @$res = socket_write($this->socket, $write . (defined('SS_HACK_ENTER_KEY')?"\r":""));
                if (!$res) return $this->throwError('socket write error');            
                debug ("SEND: $write");
            }
            $this->write_queue = array();
        }    
        return TRUE;
    }
    
    public function socketRead(){
        
        if ($this->type == self::$LISTENER){
            return $this->socketAccept();
        }
        
        // socket read
        if (defined('SS_HACK_ENTER_KEY')){
			@$res = socket_read($this->socket, 255,  PHP_NORMAL_READ);
		}else{
			@$res = socket_read($this->socket, 255,  PHP_BINARY_READ);
		}
		
        if (!$res){
            return $this->throwError('socket read error');                
        }
		if (defined('SS_HACK_ENTER_KEY')){
			$res = str_replace("\n", "", $res);
			$res = str_replace("\r", "", $res);
		}
        if ($res != ""){
            $this->onRead($res);
            debug ("READ: $res");
        }
        return TRUE;
    }
    
    public function socketExcept(){
        return $this->throwError("Exception: " . socket_last_error($this->socket)); 
    }
    
    protected function addToReadQueue($read){
        array_push($this->read_queue, $read);
    }
    
    public function writeQueue($text){
        if (!$this->write_queue) $this->write_queue = array();
        $this->write_queue[] = $text;
    }
    
    public function readQueue(){
        if ($this->hasWrite()){
            $read = array_shift($this->read_queue); 
            if (!$read) return "Empty Read";
            return $read;
        }
        return FALSE;
    }    
    
    protected function onRead($read){
        call_user_func($this->callback, $read);
    }
    
    public function hasWrite(){
        return count($this->write_queue)?TRUE:FALSE;
    }
    
    public function isConnected(){
        return $this->connected;
    }
    
    protected function setConnected($is_connected){
        $this->connected = $is_connected;
    }
    
    public function close($socket = NULL){
        if (!$socket){
            $this->setConnected(FALSE);
            $socket = $this->socket();
        }
        
        if ($socket) @socket_close($socket);
        debug("socket disconnected");        
    }
    
    public function getSocketError(){
        $error = socket_last_error($this->socket());
        socket_clear_error($this->socket());        
        return $error;
    }
    
    protected function throwError($error){
        
        // if there is a socket, try to do stuff
        if ($this->socket){
            $socket_error = socket_last_error($this->socket());
            if ($socket_error == 10054 || $socket_error == 104){
                // gracefully close
                $this->close();
                return TRUE;
            }
            $this->error = "Socket Error: $error : " .$this->getSocketError() . " " . socket_strerror($this->getSocketError()) . ($this->connected?" CONNECTION":" NO CONNECTION");
            return FALSE;
        }
        
        $this->error = "$error";
        
        
        
        
        
        return FALSE;
    }
    
    public function getError(){
        $error = $this->error;
        $this->error = FALSE;
        return $error;       
    }
}