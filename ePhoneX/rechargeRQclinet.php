#!/usr/local/bin/php
<?php
$doc = new DOMDocument();
$doc->load('./custRechargeRQ.xml');
$body=$doc->saveXML();

$url="http://devcall02.ixtelecom.com/ePhoneX/ephonexSvr.php";

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
