<?PHP

//define('DEBUG_ON', TRUE);
require_once("hypnocube.class.php");

echo "program starting...\n";

$hypnocube = new HypnoCube();
$hypnocube->setFPS(60);
$hypnocube->login();

$a = 0.00;
$frame = 0;

// set initial values
$rotate_speed['x'] = 0.10;//0.3;//
$rotate_speed['y'] = 0.01;//0.021111;//0.05;
$rotate_speed['z'] = 0.00;//0.01;//0.02;
$rot['x'] = 0.0;
$rot['y'] = 0.0;
$rot['z'] = 0.0;


// make bubbles
$bubbles = array();
$bubbles_count = 5;
for ($i= 0; $i < $bubbles_count; $i++){
	$bubble = array ('x' => (float) mt_rand(0,2500), 'y' => (float) mt_rand(0,2500), 'z' => (float) mt_rand(-2500,2500), 'c' => mt_rand(1,3));	
	//+ 1500
	// super rare white
	if (mt_rand (1,50) == 1) $bubble['c'] = 4;
	$bubbles[] = $bubble;
}

for (;;){

	$frame ++;
	
	$a += 0.05;

	$color = array (mt_rand(0,15), mt_rand(0,15),mt_rand(0,15));
		
	for ($xled = 0; $xled < 4; $xled++){
		for ($yled = 0; $yled < 4; $yled++){
			for ($zled = 0; $zled < 4; $zled++){
			
				if ($frame == 1){
					$matrix[$xled][$yled][$zled] = array (0,0,0);
				}

				$pixel = $color;

				
				
				$matrix[$xled][$yled][$zled] = array ((int) $pixel[0], (int) $pixel[1], (int) $pixel[2]);
			}
		}
	}
	
	
		
	$hypnocube->setMatrix($matrix);
	$hypnocube->sendFrame();	
	$hypnocube->flipFrame();
}	


function rotate($P0, $rot){

		$xcos = cos($rot['x']);
		$xsin = sin($rot['x']);
		$ycos = cos($rot['y']);
		$ysin = sin($rot['y']);
		$zcos = cos($rot['z']);
		$zsin = sin($rot['z']);
		
		$P['x'] = $P0['x'] * $zcos - $P0['y'] * $zsin;
		$P['y'] = $P0['x'] * $zsin + $P0['y'] * $zcos;
				
		$new_y = $P['y'];
		
		$P['y'] = $new_y * $xcos - $P0['z'] * $xsin;
		$P['z'] = $new_y * $xsin + $P0['z'] * $xcos;		
		
		$new_x = $P['x'];
		$new_z = $P['z'];
		
		$P['z'] = $new_z * $ycos - $new_x * $ysin;
		$P['x'] = $new_z * $ysin + $new_x * $ycos;		
		
		return $P;

}

// triangle wave

/*$rf = abs(($a % 60) - 30) - 15;
$bf = abs(( ($a + 7) % 60) - 30) - 15;
$gf = abs(( ($a + 28) % 60) - 30) - 15;



