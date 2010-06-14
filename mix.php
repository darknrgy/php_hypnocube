<?PHP
require_once("hypnocube.class.php");
$hypnocube = new HypnoCube();
$hypnocube->login();

$animations = array(
"fadewiper" => 30,
"spit_rotator" => 25, 
"spaz" => 12,
"rotator" => 30,
"rbg_bubbles" => 30);


for (;;){
	
	foreach ($animations as $animation => $timeout){
		
		system ("php hypnocube_$animation.php $timeout");
	}
}