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
$rotate_speed['x'] = 0.3;//
$rotate_speed['y'] = 0.021111;//0.05;
$rotate_speed['z'] = 0.01;//0.02;
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

$bg_color = array (mt_rand(0,1), mt_rand(0,1), mt_rand(0,1));
for (;;){

	if ($timeout && time() > $timeout ) exit;

	$frame ++;

	$a += 0.02;
	$aa = abs(($a % 15) - 7);
	
	$rot['x'] += $rotate_speed['x'];
	$rot['y'] += $rotate_speed['y'];
	$rot['z'] += $rotate_speed['z'];
	
	$xcos = cos($rot['x']);
	$xsin = sin($rot['x']);
	$ycos = cos($rot['y']);
	$ysin = sin($rot['y']);
	$zcos = cos($rot['z']);
	$zsin = sin($rot['z']);
	
	if (mt_rand(1,125) == 1){
	/*
		// make bubbles
		$bubble = array ('x' => (float) mt_rand(0,2500), 'y' => (float) mt_rand(0,2500), 'z' => (float) mt_rand(-2500,2500), 'c' => mt_rand(1,3));	
		if (mt_rand (1,50) == 1) $bubble['c'] = 4;
		
		$bubbles[mt_rand(0,count($bubbles)-1)] = $bubble;
	*/
		
		
		
		$bubbles = array();
		for ($i= 0; $i < $bubbles_count; $i++){
			$bubble = array ('x' => (float) mt_rand(0,2500), 'y' => (float) mt_rand(0,2500), 'z' => (float) mt_rand(-2500,2500), 'c' => mt_rand(1,3));	
			//+ 1500
			// super rare white
			if (mt_rand (1,50) == 1) $bubble['c'] = 4;
			$bubbles[] = $bubble;
		}	
		
		$rotrand = (float) mt_rand(0,1000) / 1000;
		
		$rotate_speed['x'] = 0.2 * $rotrand+ 0.1;
		
		$bg_color = array (mt_rand(0,1), mt_rand(0,1), mt_rand(0,1));
		

	}
	$bubbles_r = array();
	
	foreach ($bubbles as $k => $bubble){
	
		$bubble_r = $bubble;
		
		$bubble_r['x'] = $bubble['x'] * $zcos - $bubble['y'] * $zsin;
		$bubble_r['y'] = $bubble['x'] * $zsin + $bubble['y'] * $zcos;
				
		$new_y = $bubble_r['y'];
		
		$bubble_r['y'] = $new_y * $xcos - $bubble['z'] * $xsin;
		$bubble_r['z'] = $new_y * $xsin + $bubble['z'] * $xcos;		
		
		$new_x = $bubble_r['x'];
		$new_z = $bubble_r['z'];
		
		$bubble_r['z'] = $new_z * $ycos - $new_x * $ysin;
		$bubble_r['x'] = $new_z * $ysin + $new_x * $ycos;		
		
		$bubble_r['x'] += 1500;
		$bubble_r['y'] += 1500;
		$bubble_r['z'] += 1500;
		
		
		$bubbles_r[] = $bubble_r;
		
	}
	
	
	for ($xled = 0; $xled < 4; $xled++){
		for ($yled = 0; $yled < 4; $yled++){
			for ($zled = 0; $zled < 4; $zled++){
				
								
				$r = $bg_color[0];
				$g = 0;
				$b = $bg_color[2];
				
			
				
			
				foreach ($bubbles_r as $bubble){
					
					$x_d = (float) abs( $bubble['x'] - $xled * 1000);
					$y_d = (float) abs( $bubble['y'] - $yled * 1000);
					$z_d = (float) abs( $bubble['z'] - $zled * 1000);							
					$distance = sqrt( $x_d*$x_d + $y_d*$y_d + $z_d*$z_d) - 1200;
					$brightness = (exp(-$distance*0.005) + 0.00 )* 16;
					if ($brightness < 0) $brightness = 0;
					if ($brightness > 15) $brightness = 15;
					
					if ($brightness > 14) { $r = 0; $b = 0; $g = 0;}

					switch ($bubble['c']){
						case 1:
							if ($brightness > $r) $r = $brightness;
							break;
						case 2:
							if ($brightness > $g) $g = $brightness;
							break;
						case 3:
							if ($brightness > $b) $b = $brightness;
							break;
						case 4:
							if ($brightness > $r) $r = $brightness;
							if ($brightness > $g) $g = $brightness;
							if ($brightness > $b) $b = $brightness;
							break;
					}
				}
				
				$matrix[$xled][$yled][$zled] = array ((int) $r, (int) $g, (int) $b);
			}
		}
	}
	
	$hypnocube->setMatrix($matrix);
	$hypnocube->sendFrame();	
	$hypnocube->flipFrame();
}	


// triangle wave

/*$rf = abs(($a % 60) - 30) - 15;
$bf = abs(( ($a + 7) % 60) - 30) - 15;
$gf = abs(( ($a + 28) % 60) - 30) - 15;



