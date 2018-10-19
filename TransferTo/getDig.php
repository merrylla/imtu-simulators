<?php
$retCode='NORPS123SAVB';

$m=preg_match('/[0-9]{1,4}/',$retCode, $ecode);
if ($m)
       $code=$ecode[0];
else
       $code=0;

echo "GET CODE=",$code;
?>
