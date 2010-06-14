<?PHP
$timeout = isset($argv[1])? (time() + $argv[1]):0;

//define('DEBUG_ON', TRUE);
require_once("hypnocube.class.php");

echo "program starting...\n";

$hypnocube = new HypnoCube();
$hypnocube->setFPS(20);


$a = 0.00;
$frame = 0;
$switch = 0;
$points = moving_line();
$matrix = get_hypnocube_matrix(array(0,0,0));
for (;;){

	if ($timeout && time() > $timeout ) exit;

	$a += 0.03;
	
	$fps = 10 * (sin(microtime(TRUE) / 3)+ 1) + 7;
	
	$hypnocube->setFPS($fps);
	
	$frame++;
	$points = translate($points);
	
	if ($frame % 5 == 1){
		$points += moving_line();
		
	}
	
	if (mt_rand(1,15) == 1){
		$switch = $switch?0:1;
	}

	

	foreach($points as $point){
		if ($switch) $matrix[$point['x']][$point['y']][$point['z']] = $point['c'];	
		else $matrix[$point['y']][$point['x']][$point['z']] = $point['c'];	
	}
	
	$hypnocube->setMatrix($matrix);	
	$hypnocube->sendFrame();	
	$hypnocube->flipFrame();	
	
	$matrix = decay_matrix($matrix, 0.50);

}	

function line($x, $y, $z, $c){

	for ($i = 0; $i<4; $i++){
		$point['x'] = $x != -1 ? $x : $i;
		$point['y'] = $y != -1 ? $y : $i;
		$point['z'] = $z != -1 ? $z : $i;
		$point['c'] = $c;
		$points[] = $point;
	}	
	return $points;
}

function moving_line(){
	
	$dir = mt_rand(1,4);
	$h = mt_rand(0,3);
	$color = array (mt_rand(0, 15), mt_rand(0, 15), mt_rand(0, 15), );
	
	switch ($dir){
		case 1: 
			$points = line($h, 0, -1, $color);
			break;
		case 2:
			$points = line($h, 3, -1, $color);
			break;
		case 3:
			$points = line( $h, -1, 0, $color);
			break;
		case 4:
			$points = line( $h, -1, 3, $color);	
			break;
	}
	
	foreach ($points as $k => $point){
		
		$points[$k]['dx'] = 0;
		$points[$k]['dy'] = 0;
		$points[$k]['dz'] = 0;
		
		switch ($dir){
			case 1: $points[$k]['dy'] = 1; break;
			case 2: $points[$k]['dy'] = -1; break;
			case 3: $points[$k]['dz'] = 1; break;
			case 4: $points[$k]['dz'] = -1; break;
		}
	
	}
	return $points;
}

function translate($points){
	foreach($points as $k => $point){
		$points[$k]['x'] += $point['dx'];
		$points[$k]['y'] += $point['dy'];
		$points[$k]['z'] += $point['dz'];
	
		if ($points[$k]['x'] > 3 || 
			$points[$k]['x'] < 0 ||
			$points[$k]['y'] > 3 ||
			$points[$k]['y'] < 0 ||
			$points[$k]['z'] > 3 ||
			$points[$k]['z'] < 0) unset ($points[$k]);
	}	
	if (empty($points)) return array();
	return $points;
	
}

function decay_matrix($matrix, $decay = 1){
	for ($x = 0; $x < 4; $x++){
		for ($y = 0; $y < 4; $y++){
			for ($z = 0; $z < 4; $z++){
				//$matrix[$x][$y][$z][0] -= $decay;
				//$matrix[$x][$y][$z][1] -= $decay;
				//$matrix[$x][$y][$z][2] -= $decay;
				
				$matrix[$x][$y][$z][0] *= $decay;
				$matrix[$x][$y][$z][1] *= $decay;
				$matrix[$x][$y][$z][2] *= $decay;
				
				if ($matrix[$x][$y][$z][0] < 0) $matrix[$x][$y][$z][0] = 0;
				if ($matrix[$x][$y][$z][1] < 0) $matrix[$x][$y][$z][1] = 0;
				if ($matrix[$x][$y][$z][2] < 0) $matrix[$x][$y][$z][2] = 0;
			}
		}
	}
	return $matrix;
}





// triangle wave

/*$rf = abs(($a % 60) - 30) - 15;
$bf = abs(( ($a + 7) % 60) - 30) - 15;
$gf = abs(( ($a + 28) % 60) - 30) - 15;

*/

