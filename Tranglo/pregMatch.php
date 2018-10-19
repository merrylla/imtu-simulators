<?php
$action=':'.'123';
//$action=':'.'abc1';
$mA=array();

//$cnt=preg_match('/:(\d+)/', $action, $mA, PREG_OFFSET_CAPTURE, 0);
$cnt=preg_match_all("/:(\d+)/", $action, $mA);
//$cnt=preg_match("/:(4)/", $action, $mA);

echo "match COUNT: ".$cnt."\n";	

$ans=$mA[1];

$type=gettype($ans);
echo "ans type: ".$type."\n";
var_dump($ans);

var_dump($ans[0]);
?>
