<?PHP

//define('DEBUG_ON', TRUE);
require_once("hypnocube.class.php");

echo "program starting...\n";

$hypnocube = new HypnoCube();
$hypnocube->setFPS(30);
$hypnocube->login();

$a = 0.00;
$frame = 0;

// set initial values
$rotate_speed['x'] = 0.01;//0.3;//
$rotate_speed['y'] = 0.001;//0.021111;//0.05;
$rotate_speed['z'] = 0.05;//0.01;//0.02;
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
for (;;){

	$frame ++;
	
	$a += 0.1;
		
	//$rf = sin($a);
	//$gf = sin($a+2);
	//$bf = sin($a+4);
	
	/*if (mt_rand(1,20) == 1){
		
		switch (mt_rand(0,2)){
			case 0: $rff = 1; $bff = 0; $gff = 0; break;
			case 1: $rff = 0; $bff = 1; $gff = 0; break;
			case 2: $rff = 0; $bff = 0; $gff = 1; break;
			
		}
	}*/
	
	$rf = ($rff * 0.05 + $rf * 0.95);
	$bf = ($bff * 0.05 + $bf * 0.95);
	$gf = ($gff * 0.05 + $gf * 0.95);
	
	
	
	$rot['x'] += $rotate_speed['x'];
	$rot['y'] += $rotate_speed['y'];
	$rot['z'] += $rotate_speed['z'];
	
	
	
	$p = rotate($p_r, $rot);
	$p['c'] = $p_r['c'];
	
	for ($xled = 0; $xled < 4; $xled++){
		for ($yled = 0; $yled < 4; $yled++){
			for ($zled = 0; $zled < 4; $zled++){
			
				if ($frame == 2){
					$matrix[$xled][$yled][$zled] = array (0,0,0);
				}
			
				$r = $matrix[$xled][$yled][$zled][0] * 0.999;
				$g = $matrix[$xled][$yled][$zled][1] * 0.999;
				$b = $matrix[$xled][$yled][$zled][2] * 0.999;
				
				
				$distance = abs($p['x'] * ($xled - 1.5) * 1000 + $p['y'] * ($yled - 1.5) * 1000 + $p['z'] * ($zled - 1.5) * 1000) / 
								sqrt( $p['x'] * $p['x'] + $p['y'] * $p['y'] + $p['z'] * $p['z']) -500;
				
				/*
				$brightness = (exp(-abs($distance)*0.004) + 0.00 )* 16;
				if ($brightness < 0) $brightness = 0;
				if ($brightness > 15) $brightness = 15;
								
				if ($distance < 0){
					$g = 15;
					$b = $brightness;
				}else{
					$b = 15;
					$g = $brightness;
				}*/
								
								
				$brightness = (exp(-$distance*0.005) + 0.00 )* 16;
				if ($brightness < 0) $brightness = 0;
				if ($brightness > 15.999) $brightness = 15.999;		

				if ($rf > 0 && ($rf * $brightness) > $r )$r = $rf * $brightness;
				if ($bf > 0 && ($bf * $brightness) > $b)$b = $bf * $brightness;
				if ($gf > 0 && ($gf * $brightness) > $g)$g = $gf * $brightness;
				
				$matrix[$xled][$yled][$zled] = array ((int) $r, (int) $g, (int) $b);
			}
		}
	}
	
	echo "$r, $g, $b\n";
	
	
	
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



