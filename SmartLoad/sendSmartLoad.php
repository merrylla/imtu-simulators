<?php

try {
$client = new SoapClient("../wsdl/SMART.wsdl",array(
            "trace"      => TRUE));


	$qryA = array ('CorporateID' => 'IDT',
                       'UserName'    => 'John',
                       'Password'    => 'secret',
                     'DestinationNo' => '9893331234',
                     'Denomination'  => '200');

        $parm1 = new SoapParam($qryA, "SendSmartLoad" );

//	$ret=$client->SendSmartLoad($parm1);
	
	    $msg=new stdClass();
        $msg->CorporateID='IDT';
        $msg->UserName='John';
        $msg->Password= 'secret';
        $msg->DestinationNo = '9893331234';
        $msg->Denomination= '400';
        
    $input[0]=$msg;

        $ret=$client->__soapCall('SendSmartLoad', $input);

	var_dump($ret);

	print $client->__getLastRequestHeaders();
	print $client->__getLastRequest() ."\n";
	print $client->__getLastResponseHeaders();
	print $client->__getLastResponse()."\n";
	}
	catch (SoapFault $ex) {

	if (isset($ex->faultcode)) $errMsg="faultcode: ".$ex->faultcode; 
	if (isset($ex->faultstring)) $errMsg .= " faultstring: ".$ex->faultstring; 
	if (isset($ex->faultactor)) $errMsg .= " faultactor: ".$ex->faultactor; 
	if (isset($ex->detail)) $errMsg .= " detail: ".$ex->detail; 

	error_log("DEBUG: $errMsg \n");

}
?>