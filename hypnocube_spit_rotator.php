<?PHP

$timeout = isset($argv[1])? (time() + $argv[1]):0;

//define('DEBUG_ON', TRUE);
require_once("hypnocube.class.php");

echo "program starting...\n";

$hypnocube = new HypnoCube();
$hypnocube->setFPS(30);

$a = 0.00;
$frame = 0;

// set initial values
$rotate_speed['x'] = 0.05;
$rotate_speed['y'] = 0.005;
$rotate_speed['z'] = 0.00;
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

$p_r = array('x' => 0.0 , 'y' => 1000.00, 'z' => 0, 'c' => 1);
$rff = 1; $bff = 1; $gff = 1; 


$source = array (1.0,0.0,0.0,0.0,0.0,1.0);
$colors = array (0.0,0.0,0.0,0.0,0.0,0.0);
$center = array (0,0,0);


for (;;){
	
	if ($timeout && time() > $timeout ) exit;
	
	$frame ++;
	
	$a += 0.01;
		
	if ($frame % 200 == 1){
	
		$source = array_fill(0,9,0);
		$stack = array(0,1,2);
		shuffle($stack);
		$source[array_pop($stack)] = 1;
		shuffle($stack);
		$source[array_pop($stack)+3] = 1;
		$source[array_pop($stack)+6] = 1;
	}
	

	foreach ($colors as $k => $v){
		$colors[$k] = $colors[$k] * 0.98 + $source[$k] * 0.02;	
	}
	
	foreach ($center as $color => $v){
		$center[$color] = $v * 0.90 + $source[$color + 6] * 0.10;
	}
		
	$rot['x'] += $rotate_speed['x'];
	$rot['y'] += $rotate_speed['y'];
	$rot['z'] += $rotate_speed['z'];
	
	
	
	$p = rotate($p_r, $rot);
	$p['c'] = $p_r['c'];
	
	for ($xled = 0; $xled < 4; $xled++){
		for ($yled = 0; $yled < 4; $yled++){
			for ($zled = 0; $zled < 4; $zled++){
			
				if ($frame == 1){
					$matrix[$xled][$yled][$zled] = array (0,0,0);
				}
				
				$x = ($xled - 1.5) * 1000;
				$y = ($yled - 1.5) * 1000;
				$z = ($zled - 1.5) * 1000;
				
				
				$d = ($p['x'] * $x + $p['y'] * $y + $p['z'] * $z) / 
								sqrt( $p['x'] * $p['x'] + $p['y'] * $p['y'] + $p['z'] * $p['z']);
				
				$gradient_length = 500;
				
				for ($i = 0; $i <= 3; $i++){					
					$sides[$i] = (-1/$gradient_length * $d + 1/2) * $colors[$i];
				}
				
				for ($i = 3; $i <= 5; $i++){					
					$sides[$i] = (1/$gradient_length * $d + 1/2) * $colors[$i];
				}
				foreach ($sides as $k =>  $side){
					if ($side > 1.0) $sides[$k] = 1;
					if ($side < 0.0) $sides[$k] = 0;
				}
				
				
				$pixel[0] = ($sides[0] + $sides[3]) * 15;
				$pixel[1] = ($sides[1] + $sides[4]) * 15;
				$pixel[2] = ($sides[2] + $sides[5]) * 15;
			
				
				foreach ($pixel as $k => $color){
					if ($color > 15) $pixel[$color] = 15;
				}
				if (abs($d) > 2500) $pixel = array (15,15,15);
				$d = sqrt ($x*$x + $y*$y + $z*$z);
				
				//if ($d < 1000) { 
					//$pixel = array (0,0,0);
					if (mt_rand(1,500) == 1){
						$pixel = array (15,15,15);
					}
					
					
					//$pixel = array ($center[0] * 15, $center[1] * 15, $center[2] * 15 );
					//$pixel = array (0,0,0);

					
				//}
				
				if ($d > 2500) { 
				
					$pixel = array ($pixel[0] * 0.2, $pixel[1] * 0.2, $pixel[2] * 0.2 );
				}
				
				
				
				
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



