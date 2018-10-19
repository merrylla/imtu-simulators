#!/usr/local/bin/php
<?php

$body='?xx=<?xml version="1.0" encoding="UTF-8"?> <COMMAND><TYPE>EXRCTRFREQ</TYPE><DATE>30-05-2011 12:36:12</DATE><EXTNWCODE>MO</EXTNWCODE><MSISDN>9085551212</MSISDN><PIN>987654321</PIN><LOGINID>userID1</LOGINID><PASSWORD>secretword</PASSWORD><EXTCODE>idt_external_user_code</EXTCODE><EXTREFNUM>idt_reference_number</EXTREFNUM><MSISDN2>9942222</MSISDN2><AMOUNT>100</AMOUNT><LANGUAGE1>EN</LANGUAGE1><LANGUAGE2>FR</LANGUAGE2><SELECTOR>1</SELECTOR></COMMAND>'; 

$url="http://devcall02.ixtelecom.com/ComViva/comvivaSvr.php?ChannelReceiverREQUEST_GATEWAY_CODE=IDTTelGW&REQUEST_GATEWAY_TYPE=EXTGW&LOGIN=IDTTelTH&PASSWORD=IDT2Telecom&SOURCE_TYPE=EXTGW&SERVICE_PORT=190";

$headerA = array('Content-Type' => 'text/xml', "header2" => "parm2");

$ht = new HttpRequest($url, HttpRequest::METH_POST);

//$ht->setUrl($url);
//$ht->setHeaders($headerA);
$ht->addRawPostData($body);

//$ht->setBody($body);
//echo $ht->send();
$body= $ht->send()->getBody();

echo $body;
?>
