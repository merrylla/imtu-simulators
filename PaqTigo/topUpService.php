<?php

include '../QAlib/soaplib.php';

try{
	
$client = new SoapClient("../wsdl/PaqTigo.wsdl", array(
                                 'soap_version' => SOAP_1_2,
                                      'trace'   => TRUE));

 $rqA =array ('sourceMSISDN' => '9085551212',
                      'destinationMSISDN' => "7224445555",
                      'amount'       => 2000,
                      'PIN'          => '1224444',
                      'location'     => "Piscataway, NJ",
                      'externalTransactionId' => "987654001");
 	
$parm = new SoapParam($rqA, 'topUpServiceRequest');
$rsp=$client->topUpService($parm);

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

print ("DEBUG: $errMsg \n");

}

?>