<?php

$amount="20.00";
//$amountOperator=round($amount*5510.1234, 2);
$amountOperator=number_format($amount*5510.01, 2, '.', '');

echo $amountOperator."\n";
?>
