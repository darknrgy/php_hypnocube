<?PHP
//define('DEBUG_ON', TRUE);
require_once("hypnocube.class.php");

echo "program starting...\n";

$hypnocube = new HypnoCube();
$hypnocube->setFPS(20);

$x = 0;
$y = 0;
$z = 0;
$r = 0;
$g = 0;
$b = 0;
$a = (float) 0;
$stars = array();
for (;;){

	$x+=1;
	if ($x > 3) {
		$x = 0;
		$a+=$aa;
		$aa += 0.05;
		
		$rf = sin($a) * 16;
		$gf = sin($a+2) * 16;
		$bf = sin($a+4) * 16;
		
		$r = (int) $rf;
		$g = (int) $gf;
		$b = (int) $bf;
		
		
		
		if ($r < 0) $r = 0;
		if ($g < 0) $g = 0;
		if ($b < 0) $b = 0;
		
		echo "$r,$g,$b\n";
		
		
		
		
		
	}
	
	if (mt_rand(1,10) == 1){
		$stars[] = array (0, mt_rand(0,3), mt_rand(0,3),  15 ,15, 15);
	}
	
	for ($y = 0; $y < 4; $y++){
		for ($z = 0; $z < 4; $z++){
			$matrix[$x][$y][$z] = array ($r, $g, $b);			
		}
	
	}
	$hypnocube->setMatrix($matrix);
	
	foreach ($stars as $key => $star){
	
		$hypnocube->setPixel($star[0], $star[1], $star[2],$star[3],$star[4],$star[5]);
		$stars[$key][0] = $stars[$key][0] + 1;
		if ($stars[$key][0] > 3) unset ($stars[$key]);	
	}
	
	
	
	$hypnocube->sendFrame();	
	$hypnocube->flipFrame();
	
	$hypnocube->poll();   
	
	
	
}

