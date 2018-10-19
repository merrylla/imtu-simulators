<?php

$qrydata = new stdClass;
//$qrydata->pin= "A38B36625D1A4A3FB671D9910D227697";
//$qrydata->remittanceId=1066571439;

$input[0]=$qrydata;

$client = new SoapClient("http://devcall02.ixtelecom.com/wsdl/IMTU-Globe.wsdl",
                           array('trace'   => TRUE));

 try {
   $getSessRsp=$client->__soapcall("createsessioncmd", $input);

   $ssId=$getSessRsp->session;

   echo "Received getSessRsp-session <" .  $ssId . ">\n";

//   $qrydata->session->SessionId = $ssId;
//
//   $input[0]=$qrydata;

//   $getSessRsp=$client->__soapcall("GetRemittanceByPaymentId", $input);

//echo("\nDumping request headers:\n" . $client->__getLastRequestHeaders());

echo("\nDumping request:\n" . $client->__getLastRequest());

//echo("\nDumping response headers:\n" .$client->__getLastResponseHeaders());

var_dump($getSessRsp);


   echo("\nDumping response:\n".$client->__getLastResponse());
   die("exiting!\n");
 } 

 catch (SoapFault $exception) {
    echo $exception;      
  } 

?>

