<?php

include '../QAlib/soaplib.php';

try{
	
$client = new SoapClient("../wsdl/iSend.wsdl", array(
                                 'soap_version' => SOAP_1_2,
                                      'trace'   => TRUE));

//$rsp=$client->HelloWorld();

$rsqA=array( 'req' => array('AgentID' => 'IDT',
            'AgentPassword' => 'secret',
            'ExternalTransactionID' => 12345,
            'Teller' => 'NY Teller',
            'ValidateOnly' =>  'ture',
            'Amount' => 5000,
            'Account' => '12345676',
            'BillerID' => '12345',
            'PaymentServiceTypeID' => '12345',
            'EntryTimeStamp' => '0712'
           )
           );

//$parm = new SoapParam($rsqA, 'req');
$parm = new SoapParam($rsqA, 'PostSimplePaymentRequest');
//$parm = new SoapParam($rsqA, 'PostSimplePayment');

$rsp=$client->PostSimplePayment($parm);
//$rsp=$client->HelloWorld($parm);

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
