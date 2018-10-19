<?php

include '../QAlib/soaplib.php';

try{
	
$client = new SoapClient("../wsdl/iSend.wsdl", array(
                                 'soap_version' => SOAP_1_2,
                                      'trace'   => TRUE));

//$rsp=$client->HelloWorld();

$rsqA=array( 'req' => 
      array('AgentID' => 'IDT',
            'AgentPassword' => 'secret',
            'TransactionID' => 12345,
            'ExternalTransactionID' => '12345676'
           )
           );

$parm = new SoapParam($rsqA, 'ConfirmPaymentRequest');

$rsp=$client->ConfirmPayment($parm);

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
