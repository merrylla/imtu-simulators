<?php

include '../QAlib/soaplib.php';

$client = new SoapClient("../wsdl/PaqTigo.wsdl", array(
                                 'soap_version' => SOAP_1_2,
                                      'trace'   => TRUE));

$msg= new stdClass;
//$msg->sourceMSISDN= "987111222";
$msg->destinationMSISDN="7224445555";
$msg->amount=2000;
$msg->PIN="7224445555";
$msg->location="Piscataway, NJ";
$msg->externalTransactionId="987654001";

$rqA = array('sourceMSISDN' => "987111222", 
        'destinationMSISDN' => "7224445555",
        'amount'            => 2000,
        'PIN'               => "7224445555",
        'location'          => "Piscataway, NJ",
    'externalTransactionId' => "987654001");

$param = new soapparam($rqA, 'topUpService');

/*
$msg = array ('topUpServiceRequest'
            => array ('sourceMSISDN' => '9085551212',
                      'destinationMSISDN' => "7224445555",
                      'amount'       => 2000,
                      'PIN'          => '1224444',
                      'location'     => "Piscataway, NJ",
                      'externalTransactionId' => "987654001") );

$rsp=$client->topUpService($xml);
*/

$input[0] = $msg;

//$rsp=$client->__getFunctions();
//var_dump($rsp);


try {

//$rsp=$client->__soapcall('topUpService', $input, array('soapaction' => "urn:topUpService"));
//$rsp=$client->__soapcall('topUpService', $input );
$rsp=$client->topUpService($param);

echo "Dumping topUpService call results.";
var_dump($rsp);
print $client->__getLastRequestHeaders() ."\n";
print $client->__getLastRequest() ."\n";
print $client->__getLastResponseHeaders()."\n";
print $client->__getLastResponse()."\n";
}
catch (SoapFault $ex) {

if (isset($ex->faultcode)) $errMsg="SoapFault<faultcode> ".$ex->faultcode;
if (isset($ex->faultstring)) $errMsg .= "<faultstring> ".$ex->faultstring;
if (isset($ex->faultactor)) $errMsg .= " faultactor: ".$ex->faultactor;
if (isset($ex->detail)) $errMsg .= " detail: ".$ex->detail;

error_log("DEBUG: $errMsg \n");

}

?>
