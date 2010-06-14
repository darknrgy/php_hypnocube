<?PHP


$timeout = isset($argv[1])? (time() + $argv[1]):0;

//define('DEBUG_ON', TRUE);
require_once("hypnocube.class.php");

echo "program starting...\n";

$hypnocube = new HypnoCube();
$hypnocube->setFPS(30);


$speed_range = 50;

$dx = 300;
$a = 0;

$bubbles = array();
for (;;){

	if ($timeout && time() > $timeout ) exit;

	$a += 0.02;
	$aa = abs(($a % 15) - 7);
	
	
	//echo "$aa\n";
	
	if (mt_rand(0,$aa) == 0){
		$bubble = array ('x' => -6000, 'y' => mt_rand(-2000,4999), 'z' => mt_rand(-2000,4999), 'c' => mt_rand(1,3));	
		
		// super rare white
		if (mt_rand (1,50) == 1) $bubble['c'] = 4;
		
		$bubbles[] = $bubble;
	}
	
	foreach ($bubbles as $k => $bubble){
		
		if ($bubble['x']  + $dx > 8000){
			unset ($bubbles[$k]);		
		}else{
			$bubbles[$k]['x'] += $dx;
		}
	
	}
	
	
	
	
	$rr = mt_rand(0,1);
	$gg = mt_rand(0,1);
	$bb = mt_rand(0,1); 
	
	for ($xled = 0; $xled < 4; $xled++){
		for ($yled = 0; $yled < 4; $yled++){
			for ($zled = 0; $zled < 4; $zled++){
				
				/*$rf = abs(($a % 60) - 30) - 15;
				$bf = abs(( ($a + 7) % 60) - 30) - 15;
				$gf = abs(( ($a + 28) % 60) - 30) - 15;
				
				$r = (int) $rf;
				$b = (int) $bf;
				$g = 0;
				
				
				
				if ($r < 0) $r = 0;
				if ($g < 0) $g = 0;
				if ($b < 0) $b = 0;*/
				
				$r = 0;
				$g = 0;
				$b = 0;
				
			
				
			
				foreach ($bubbles as $bubble){
					
					$x_d = (float) abs( $bubble['x'] - $xled * 1000);
					$y_d = (float) abs( $bubble['y'] - $yled * 1000);
					$z_d = (float) abs( $bubble['z'] - $zled * 1000);							
					$distance = sqrt( $x_d*$x_d + $y_d*$y_d + $z_d*$z_d) - 1500;
					$brightness = (exp(-$distance*0.005) + 0.00 )* 16;
					if ($brightness < 0) $brightness = 0;
					if ($brightness > 15) $brightness = 15;

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







