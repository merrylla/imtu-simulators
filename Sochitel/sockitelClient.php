#!/usr/local/bin/php
<?php

$body="username=idttesting&password=idt77777&cmd=sellAirtime&mobileno=2348166666071&amount=1&agentTransactionId=902298067700";

$url="http://devcall02.ixtelecom.com/Sochitel/sockitelSvr.php";

$headerA = array('Content-Type' => 'text/xml', "header2" => "parm2");

$ht = new HttpRequest($url, HttpRequest::METH_POST);

//$ht->setUrl($url);
//$ht->setHeaders($headerA);
$ht->addRawPostData($body);

$ht->setBody($body);
//echo $ht->send();
$body= $ht->send()->getBody();

echo $body;
?>
