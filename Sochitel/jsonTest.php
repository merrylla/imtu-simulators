<?php
$ct='{"status":"0","statusString":"SCH_norm_4_c","command":"topup","result":{"amount":"5.00USD","amountUser":"5.00USD","amountOperator":"789.5NGN","balance":{"before":"1000USD","after":995},"serial":1431466}}';

$context=json_decode($ct);

var_dump($context);

?>
