<?php

$qrydata = new stdClass;
$qrydata->transaction=99887766;

$input[0]=$qrydata;

$client = new SoapClient("http://172.17.10.185/Globe/GlobeGCash.wsdl",
                           array('trace'   => TRUE));

 try {
   $getSessRsp=$client->__soapcall("createsessioncmd", $input);

   $ssId=$getSessRsp->session;

   echo "Received getSessRsp-session <" .  $ssId . ">\n";

   $qrydata->session = $ssId;

   $input[0]=$qrydata;

   $getSessRsp=$client->__soapcall("commitcashcmd", $input);

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

