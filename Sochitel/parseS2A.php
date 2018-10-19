<?php
function sInput2a($p)
{

$pieces = explode("&", $p);

$eA=array();

foreach ($pieces as $val) {
   $xA = explode("=", $val);
   $eA[$xA[0]]= $xA[1];
}

return $eA;
}

$strE="username=438&password=secret&cmd=sellAirtime&mobileno=26046843259&amount=5&agentTransactionId=101250125101";

$A=sInput2a($strE);

var_dump($A);
?>
