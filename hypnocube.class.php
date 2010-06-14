<?PHP
//define('DEBUG_ON', TRUE);
require_once("../../library/sockets.php");

define('HC_SYNC', 0xC0);
define('HC_ESC', 0xDB);
define('HC_CMD_PING', 60);

class HypnoCube{

	protected $sp;
	protected $matrix;
	protected $fps = 30;
	static $debug = FALSE;

	function __construct(){

		$sp = new SocketProcessor();
		$socket_params = array(
			'domain' => AF_INET,
			'type' => SOCK_STREAM,
			'protocol' => SOL_TCP,
			'addr' => 'localhost',
			'port' => 5333);
		$sp->addSocket(1, Socket::$RECONNECT, $socket_params);
		$sp->socket(1)->setCallback('hypnocube_callback');	
		hypnocube_callback("", $this);
		$this->sp = $sp;
		$this->matrix = array_fill(0,64, array(0,0,0));
		$this->login();
	}
	
	public function login(){
	
		echo "loggin in...\n";
		$this->send(chr(0x00) .  pack ('N', 0xABADC0DE));		
	}
	
	public function setPixel($x, $y, $z, $r, $g, $b){
		$this->matrix[$this->convertPosition($x,$y,$z)] = array ($r, $g, $b);
	}
	
	public function setMatrix($matrix){
		foreach ($matrix as $x => $yzp){
			foreach ($yzp as $y => $zp){
				foreach($zp as $z => $p){
					$this->matrix[$this->convertPosition($x,$y,$z)] = array( (int) $p[0], (int) $p[1], (int) $p[2]);			
				}
			}
		}		
	}
	
	
	public function sendFrame(){
		$parity = 0;
		$pixel_string = "";
		foreach ($this->matrix as $pixel){
			foreach($pixel as $color){
				if ($parity == 0){
					$pixel_char = $color * 16;
					$parity = 1;
				}
				else{
					$pixel_char += $color;
					$pixel_string .= chr($pixel_char);
					$parity = 0;
				}				
			}
		}	
		//echo "____FRAME____\n";
		$this->send(chr(81) . $pixel_string);
		$this->sp->poll();
	}
	
	public function flipFrame(){
		
		static $last_time = NULL;
		
		if ($last_time){
			$wait = FALSE;
			while (microtime(TRUE) < ($last_time + 1/$this->fps)){
				$wait = TRUE;
				// wait
			}		
			if (!$wait) echo "fps too fast\n";
		}
		$last_time = microtime(TRUE);
		
		$this->send(chr(80));
		$this->sp->poll();
		
	}
	
	public function setFPS($fps){
		$this->fps = $fps;
	}
	
	public function poll(){
		$this->sp->poll();
	}
	
	protected function convertPosition($x,$y,$z){
		return $x * 16 + $y * 4 + $z;
	}
	
	protected function send($data){
		
		$datas = str_split($data, 50);
		$sequence = 0;
		$packets = array();
		foreach ($datas as $data){

			if ($sequence == count($datas)-1) $type = 3;
			else $type = 2;
			
			//echo "type is $type and sequence is $sequence\n";
			
			$packet = chr($type * 32 + $sequence);	// TYPE/SEQUENCE
			$packet .= chr(strlen($data));			// LENGTH
			$packet .= chr(16);						// DEST
			$packet .= $data;						// DATA
			$packet .= pack ("n", crc16($packet));	// CHKSUM

			/*
			echo "_____SEND______\n";
			display($packet);
			echo "\n________________\n";		
			*/
			
			$packet = str_replace(chr(HC_ESC), chr(HC_ESC) . chr(HC_ESC + 2), $packet);
			$packet = str_replace(chr(HC_SYNC), chr(HC_ESC) . chr(HC_ESC + 1), $packet);
				

			$packet = chr(HC_SYNC) . $packet . chr(HC_SYNC);
			
			$packets[] = $packet;
			$sequence++;
		}
		
		foreach ($packets as $packet){
			$this->sp->socket(1)->writeQueue($packet);
		}	
	
	
	}
		
	public function callback($res){

		//echo "CALLBACK: $res\n";
		
		static $in_packet = FALSE;
		static $current_packet = "";
		
		while ($res){
		
			$sync = strpos($res, chr(HC_SYNC));
			
			if ($sync !== FALSE){
			
				if ($in_packet){
					// packet is done!
					$packet = $current_packet . substr($res, 0, $sync);
					$res = substr($res, $sync+1);
					$current_packet = "";				
					$this->rcv($packet);
					$in_packet = FALSE;
				}else{
					$res = substr($res, $sync+1);
					$in_packet = TRUE;		
				}
			}else{
			
				if ($in_packet){
					$current_packet .= $res;
					$res = "";
				}else{
					$res = "";
				}		
			
			}
		}
	}		
	
	protected function rcv($packet){
		/*
		echo "_____RCV______\n";
		display($packet);
		echo "\n_______________\n";	
		*/
	}
}

function hypnocube_callback($res, $define_object = NULL){
	static $object = NULL;
	if ($define_object){ $object = $define_object; return; }
	$object->callback($res);
}


function crc16($data)
{
  $crc = 0xFFFF;
  for ($i = 0; $i < strlen($data); $i++)
  {
    $x = (($crc >> 8) ^ ord($data[$i])) & 0xFF;
    $x ^= $x >> 4;
    $crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ $x) & 0xFFFF;
  }
  return $crc;
}




function display ($data){
	
	$data = str_split($data);
	$i = 0;
	foreach ($data as $char){
		echo bin2hex($char) . " ";	
		if (++$i % 8 == 0) echo "\n"; 
	}		echo "\n";
}



function debug($debug){
    if (!defined('DEBUG_ON')) return TRUE;
    echo "DEBUG: ";
    echo memory_get_usage(TRUE);
    echo " ";
    if (is_scalar($debug)) echo $debug . "\n";
    else var_dump($debug);
    usleep(0);
}

function get_hypnocube_matrix($c){
	for ($x = 0; $x < 4; $x++){
		for ($y = 0; $y < 4; $y++){
			for ($z = 0; $z < 4; $z++){
				$matrix[$x][$y][$z] = $c;
			}
		}
	}
	return $matrix;
}











/*

$login = chr(0x00) .  pack ('N', 0xABADC0DE);



echo "program starting...\n";

$sp = new SocketProcessor();
$socket_params = array(
    'domain' => AF_INET,
    'type' => SOCK_STREAM,
    'protocol' => SOL_TCP,
    'addr' => 'localhost',
    'port' => 5333);
$sp->addSocket(1, Socket::$RECONNECT, $socket_params);
$sp->socket(1)->setCallback('callback');

echo "SENDING LOGIN...\n";
display($login);
echo "WAITING...\n";
send_packets($login);

for (;;){
    $sp->poll();   
	$r = mt_rand(0,127);
	$g = mt_rand(0,127);
	$b = mt_rand(0,127);
	$x = mt_rand(1,4);
	$y = mt_rand(1,4);
	$z = mt_rand(1,4);
	
	$set_pixel = chr(84);
	$set_pixel .= pack('c*', $r, $g, $b, $x, $y, $z);
	
	$string = "";
	for ($i = 0; $i < 96; $i++){
		$string .= chr(mt_rand(0,10));		
	}
	
	$draw = chr(81) . $string;
	
	send_packets($draw);
	
	send_packets(chr(80));
	
	
	
	usleep(100000);
	
	
}


function send_packets($data){

	global $sp;

	$datas = str_split($data, 50);
	$sequence = 0;
	$packets = array();
	foreach ($datas as $data){

		if ($sequence == count($datas)-1) $type = 3;
		else $type = 2;
		
		echo "type is $type and sequence is $sequence\n";
		
		$packet = chr($type * 32 + $sequence);		// TYPE/SEQUENCE
		$packet .= chr(strlen($data));			// LENGTH
		$packet .= chr(16);						// DEST
		$packet .= $data;						// DATA
		$packet .= pack ("n", crc16($packet));	// CHKSUM

		echo "_____SEND______\n";
		display($packet);
		echo "\n________________\n";		
		
		$packet = str_replace(chr(HC_ESC), chr(HC_ESC) . chr(HC_ESC + 2), $packet);
		$packet = str_replace(chr(HC_SYNC), chr(HC_ESC) . chr(HC_ESC + 1), $packet);
			

		$packet = chr(HC_SYNC) . $packet . chr(HC_SYNC);
		
		$packets[] = $packet;
		$sequence++;
	}
	
	foreach ($packets as $packet){
		$sp->socket(1)->writeQueue($packet);
	}
	
	
	return $packet;
}

function display ($data){
	
	$data = str_split($data);
	$i = 0;
	foreach ($data as $char){
		echo bin2hex($char) . " ";	
		if (++$i % 8 == 0) echo "\n"; 
	}		echo "\n";
}

function callback($res){

	echo "CALLBACK: $res\n";
	
	static $in_packet = FALSE;
	static $current_packet = "";
	
	while ($res){
	
		$sync = strpos($res, chr(HC_SYNC));
		
		if ($sync !== FALSE){
		
			if ($in_packet){
				// packet is done!
				$packet = $current_packet . substr($res, 0, $sync);
				$res = substr($res, $sync+1);
				$current_packet = "";				
				run_packet($packet);
				$in_packet = FALSE;
			}else{
				$res = substr($res, $sync+1);
				$in_packet = TRUE;		
			}
		}else{
		
			if ($in_packet){
				$current_packet .= $res;
				$res = "";
			}else{
				$res = "";
			}		
		
		}
	}
}

function run_packet($packet){
	echo "_____PACKET______\n";
	display($packet);
	echo "\n_________________\n";	
}

function debug($debug){
    if (!defined('DEBUG_ON')) return TRUE;
    echo "DEBUG: ";
    echo memory_get_usage(TRUE);
    echo " ";
    if (is_scalar($debug)) echo $debug . "\n";
    else var_dump($debug);
    usleep(0);
}

function crc16($data)
{
  $crc = 0xFFFF;
  for ($i = 0; $i < strlen($data); $i++)
  {
    $x = (($crc >> 8) ^ ord($data[$i])) & 0xFF;
    $x ^= $x >> 4;
    $crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ $x) & 0xFFFF;
  }
  return $crc;
}
*/