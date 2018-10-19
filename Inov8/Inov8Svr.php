<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));

include './ImtuTopUpClass.php';
#include '../QAlib/wslib1.php';
include './wslib1.php';

$wsdl="http://devcall02.ixtelecom.com/Inov8/Inov8.wsdl";

#
##################################################
#
class SoapHandler {
	
	function validateMobileNumber($p)
	{
	
FILOG("FI: Inside validateMobileNumber \n");
	   errlog("INFO: validateMobileNumber\n", $p);
	
	   $username=$p->username;
	   $password=$p->password;
	   $recipientMSISDN=$p->recipientMSISDN;
	   $transactionId=$p->transactionID;
	
	   $rsp=array('username' => $username,
			'password' => $password,
			'recipientMSISDN' => $recipientMSISDN,
			'transactionID' => $transactionId,
	                'denominations' => "1,2.50,5.00",
			'responseCode' => '00'); 
	
	   return $rsp;
	}//validateMobileNumber
	
	function topupRequest($p)
	{
	    errlog("INFO: topupRequest\n", $p);
FILOG('FI: Inside topupRequest $p=:'. print_r($p,true)."\n");
	
	   $username=$p->topupServiceBean->username;
	   $password=$p->topupServiceBean->password;
	   $recipientMSISDN=$p->topupServiceBean->recipientMSISDN;
	   $senderMSISDN=$p->topupServiceBean->senderMSISDN;
	   $transactionId=$p->topupServiceBean->transactionID;
	   $paymentOption=$p->topupServiceBean->paymentOption;
#	
	   $rsp=array('username' => $username,
			'password' => $password,
			'recipientMSISDN' => $recipientMSISDN,
			'senderMSISDN' => $senderMSISDN,
			'transactionID' => $transactionId,
#	                'paymentOption' => $paymentOption,
	                'paymentOption' => 1,
			'responseCode' => '00'); 
#	
	################## FIX BELOW########################
	    $xA=array('username' => 'a0', 
	              'password' => 'a1',
	              'recipientMSISDN' => 'a2',
	              'senderMSISDN' => 'a3',
	              //'transactionID' => 'a4',
	              //'paymentOption' => 'a5'
			);
	
	    $rA=array('username' => 'a0', 
	              'password' => 'a1',
	              'recipientMSISDN' => 'a2',
	              'senderMSISDN' => 'a3',
	              'transactionID' => 'a4',
		      'paymentOption' => 'a5',
	    	      'responseCode' => 'a6',
	              'authCode' => 'a7',
	              'transactionCode' =>'a8' );
	
	    $rA1=array('responseCode' => '00', 
#	               'authCode' => '8509',
			'transactionID' => $transactionId,
	               'transactionCode' => 'TRANSCODE' );
	
FILOG("FI: Inside topupRequest\n");
FILOG("Inov8Svr.php:topupRequest: xA= " . print_r($xA,true) . "\n");
FILOG("Inov8Svr.php:topupRequest: rA1= " . print_r($rA1,true) . "\n");
	    $key = array('app' => 'INOV8' ,'key' => $senderMSISDN);
	
	    $tpO= new TopupObj($key, $xA, $rA);
	
FILOG("Inov8Svr.php::before calling validateReqInputINOV8: \n");
	    $tpO->validateReqInputINOV8($p);
FILOG("Inov8Svr.php::after calling validateReqInputINOV8: \n");
	
	    $rsp=$tpO->buildRspData($rA1);
FILOG("Inov8Svr.php:topupRequest: rsp= " . print_r($rsp,true) . "\n");
	###############################
	
	   errlog('topupRequest', $rsp);
	   //return array('topupRequest' => $rsp);
// return topupServiceBean->object????
$FIrsp=array( "return" => $rsp );
   return $FIrsp;
//   return $rsp;
	
	}//topupRequest 
	
	function checkBalance($p)
	{
	
FILOG("FI: Inside checkBalance\n");
	   $username=$p->topupServiceBean->username;
	   $password=$p->topupServiceBean->password;
	##############FIX#########################3
	    $xA=array('username' => 'a0', 
	              'password' => 'a1',
			);
	
	    $rA=array('accountBalance' => 'a2', 
	              'accountBalanceCurrency' => 'a3',
	              'responseCode' => 'a4',
	               );
	    $key = array('app' => 'INOV8' ,'key' => $username);
	
	    $tpO= new TopupObj($key, $xA, $rA);
	
FILOG("Inov8Svr.php::checkBalance before calling validateReqInputINOV8: \n");
	    $tpO->validateReqInputINOV8($p);
FILOG("Inov8Svr.php::checkBalance after calling validateReqInputINOV8: \n");
	
	    $rA1=array('accountBalance' => '99.999', 
			'accountBalanceCurrency' => 'FI',
	               'responseCode' => '00' );

	    $rsp=$tpO->buildRspData($rA1);
#############
	
FILOG("Inov8Svr.php:checkBalance: rsp= " . print_r($rsp,true) . "\n");
	   $FIrsp=array( "return" => $rsp );
	   return $FIrsp;
	
	}//checkBalance
	
	function transactionStatus($p)
	{
FILOG("FI: Inside transactionStatus\n");
	
	##############FIX#########################3
	   $debitValue=$p->debitValue;
	   $originCurrencyCode=$p->originCurrencyCode;
	   $rechargeTransactionId=$p->rechargeTransactionId;
	   $transactionId=$p->transactionID;
	
	   $rsp=array('returnCode' => 0, 
	                'returnDescription' => 'ok'); 
	
	   return $rsp;
	}//transactionStatus
	
} //SoapHandler

if (empty($HTTP_RAW_POST_DATA)) {

    $doc = new DOMDocument();
    $doc->load('./rechargeAccount.xml');
    $HTTP_RAW_POST_DATA= $doc->saveXML();
}

errlog('Main', $HTTP_RAW_POST_DATA);

$server = new SoapServer($wsdl, array('soap_version' => SOAP_1_2));
$server->setClass("SoapHandler");
$func=$server->getFunctions();
//errlog('Main - List of soap methods:', $func);

function out_callback($buffer)
{
global $outbuff;

$outbuff=str_replace('webservice.server.iserv.inov8.com','webservice.server.allpay.inov8.com',$buffer);

$outbuff=str_replace(':ns1',':inov8ns1',$outbuff);
$outbuff=str_replace('<ns1','<inov8ns1',$outbuff);
$outbuff=str_replace('</ns1','</inov8ns1',$outbuff);

return($outbuff);

} // end out_callback

try {

FILOG("FI: Inside Inov8.php\n");
$FI_HTTP_RAW_POST_DATA=preg_replace("/".'webservice.server.allpay.inov8.com'."/",'webservice.server.iserv.inov8.com',$HTTP_RAW_POST_DATA);


$FI_HTTP_RAW_POST_DATA=str_replace(':inov8ns1',':ns1',$FI_HTTP_RAW_POST_DATA);
$FI_HTTP_RAW_POST_DATA=str_replace('<inov8ns1','<ns1',$FI_HTTP_RAW_POST_DATA);
$FI_HTTP_RAW_POST_DATA=str_replace('</inov8ns1','</ns1',$FI_HTTP_RAW_POST_DATA);

###$FI_HTTP_RAW_POST_DATA=$HTTP_RAW_POST_DATA;
FILOG('HTTP_RAW_POST_DATA='.$HTTP_RAW_POST_DATA . "\n");
FILOG('FI_HTTP_RAW_POST_DATA='.$FI_HTTP_RAW_POST_DATA . "\n");

ob_start("out_callback");
$server->handle($FI_HTTP_RAW_POST_DATA);
ob_end_flush();

FILOG("Inov8: Outbuff=".$outbuff);

FILOG("FI: Inside Inov8.php after handle\n");
FILOG('List of soap methods: ' . "\n");
foreach($func as $method ) {
	FILOG($method . "\n");
	} // end foreach

#$server->topupRequest($HTTP_RAW_POST_DATA);

}
catch (Exception $e) {

FILOG("FI: Inside catch Exception\n");
   $errMsg="CatchException";
   //$status= $e->getCode();
   //the return code eg 01 cannot be stored in getCode. hence use getMessage.
   $status= $e->getMessage();

   errlog('Rsp: <Error>', $status."</Error>");
   return array('Error' => $status);
}

?>
