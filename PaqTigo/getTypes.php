<?php

$wsdlurl="../wsdl/PaqTigo.wsdl";

$client = new SoapClient($wsdlurl, array('soap_version' => SOAP_1_2));
$typeA = $client->__getTypes();

var_dump($typeA);
?>
