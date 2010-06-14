<?PHP

//define('DEBUG_ON', TRUE);
require_once("hypnocube.class.php");

echo "program starting...\n";

$hypnocube = new HypnoCube();
$hypnocube->login();
$hypnocube->setFPS(20);


$a = 0.00;
$frame = 0;
$points = array();
$matrix = get_hypnocube_matrix(array(0,0,0));
for (;;){

	$points = translate($points);
	$points = splash($points);
	
	
	if (mt_rand(1,10) == 1){
		$points = array_merge($points, rain_drop());
	}

	foreach($points as $point){
		$matrix[$point['x']][$point['y']][$point['z']] = $point['c'];	
	}
	
	$hypnocube->setMatrix($matrix);	
	$hypnocube->sendFrame();	
	$hypnocube->flipFrame();	
	
	$matrix = decay_matrix($matrix, 0.75);

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

function rain_drop(){
	
	$point['x'] = 3;
	$point['y'] = mt_rand(0,3);
	$point['z'] = mt_rand(0,3);
	$point['dx'] = -1;
	$point['dy'] = 0;
	$point['dz'] = 0;
	$point['c'] = array(0, 0, 15);
	return array ($point);
}

function splash($points){

	foreach($points as $k => $p){
		if ($p['dx'] == 0){
			$points[$k]['c'][0] *= 0.3;
			$points[$k]['c'][1] *= 0.3;
			$points[$k]['c'][2] *= 0.3;			
		}
	
	}
	
	
	$splashes = array();
	foreach($points as $k => $p){
		if ($p['dx'] == -1 && $p['x'] == 0){
			$c = array(15 ,15,  15);
			
			$splashes[] = make_point(0, $p['y'], $p['z'], 0, -1, 0, $c);
			$splashes[] = make_point(0, $p['y'], $p['z'], 0, 1, 0, $c);
			$splashes[] = make_point(0, $p['y'], $p['z'], 0, 0, -1, $c);
			$splashes[] = make_point(0, $p['y'], $p['z'], 0, 0, 1, $c);		
			echo "splashes count " . count($splashes) . "\n";
			
		}
		
	}
	$points = array_merge($points, $splashes);
	return $points;

}

function make_point($x, $y, $z, $dx, $dy, $dz, $c){
	return array ('x' => $x, 'y' => $y, 'z' => $z, 'dx' => $dx, 'dy' => $dy, 'dz' => $dz, 'c' => $c);
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

