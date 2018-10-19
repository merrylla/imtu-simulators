<?php

$qrydata = new stdClass;
$qrydata->amount= 785.45;
$qrydata->to='John Doe';
$qrydata->remitterlastname='Smith';
$qrydata->remitterfirstname='Firstname';

$input[0]=$qrydata;

$client = new SoapClient("http://lwebdev01.ixtelecom.com/Globe/GlobeGCash.wsdl",
                           array('trace'   => TRUE));

 try {
   $getSessRsp=$client->__soapcall("createsessioncmd", $input);

   $ssId=$getSessRsp->session;

   echo "Received getSessRsp-session <" .  $ssId . ">\n";

   $qrydata->session = $ssId;

   $input[0]=$qrydata;

   $getSessRsp=$client->__soapcall("cashincmd", $input);

//echo("\nDumping request headers:\n" . $client->__getLastRequestHeaders());

// echo("\nDumping request:\n" . $client->__getLastRequest());

//echo("\nDumping response headers:\n" .$client->__getLastResponseHeaders());

var_dump($getSessRsp);


   echo("\nDumping response:\n".$client->__getLastResponse());
   die("exiting!\n");
 } 

 catch (SoapFault $exception) {
    echo $exception;      
  } 

?>

