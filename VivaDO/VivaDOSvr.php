<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));

include './ImtuTopUpClass.php';
#include '../QAlib/wslib1.php';
include './wslib1.php';

#$wsdlORIG="http://devcall02.ixtelecom.com/VivaDO/VivaRecharge.wsdl";
$wsdl="http://devcall02.ixtelecom.com/VivaDO/FI_VivaRecharge.wsdl";

#
##################################################
#
class SoapHandler {
#class viva_topup {
	
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
	
	//function viva_topup_Recharge($p)
	function viva_topup_Recharge($p,$e,$se,$a,$c,$t)
	{
FILOG("FI: Inside viva_topup.RechargeRequest p= ".print_r($p,true)."\n");
FILOG("FI: Inside viva_topup.RechargeRequest e=".print_r($e,true)."\n");
FILOG("FI: Inside viva_topup.RechargeRequest se=".print_r($se,true)."\n");
FILOG("FI: Inside viva_topup.RechargeRequest a=".print_r($a,true)."\n");
FILOG("FI: Inside viva_topup.RechargeRequest c=".print_r($c,true)."\n");
FILOG("FI: Inside viva_topup.RechargeRequest t=".print_r($t,true)."\n");

// $p needs to become an array...
$vivaP = array( 'phoneNumber' => $p,
	'entity' => $e,
	'subentity' => $se,
	'amount' => $a,
	'currencyCode' => $c,
	'transactionId' => $t );

	    errlog("INFO: viva_topup.RechargeRequest\n", $p);
FILOG("FI: Inside viva_topup.RechargeRequest p=\n".print_r($p,true)."\n");
//$p=preg_replace("/".'viva_topup_Recharge'."/",'viva_topup.Recharge',$p);
	
	$phoneNumber=$p;
	$entity=$e;
	$subentity=$se;
	$amount=$a;
	$currencyCode=$c;
	$transactionId=$t;
#	
	   $rsp=array('transactionId' => $transactionId,
			'rechargeId' => $rechargeId,
			'code' => $code,
			'message' => $message
			); 
FILOG("FI: Inside viva_topup.RechargeRequest rsp=\n".print_r($rsp,true)."\n");
#	
	################## FIX BELOW########################
	    $xA=array('phoneNumber' => 'a0', 
	              'entity' => 'a1',
	              'subentity' => 'a2',
	              'amount' => 'a3',
	              'currencyCode' => 'a4',
	              'transactionId' => $transactionId
			);
	
	    $rA=array('transactionId' => 'a5',
	              'rechargeId' => 'a6',
	              'code' => 'a7',
	              'message' => 'a8'
	               );
	
	    $rA1=array('transactionId' => '999.99', 
	               'rechargeId' => 'FI',
			'code' => 'OK',
	               'message' => 'FI:VivaDO recharge default message.' );
	
FILOG("FI: Inside topupRequest\n");
FILOG("VivaDOSvr.php:topupRequest: xA= " . print_r($xA,true) . "\n");
FILOG("VivaDOSvr.php:topupRequest: rA1= " . print_r($rA1,true) . "\n");
	    $key = array('app' => 'VIVADO' ,'key' => $phoneNumber);
	    //$key = array('app' => 'VIVADO' ,'key' => $p);
	
	    $tpO= new TopupObj($key, $xA, $rA);
	
FILOG("VivaDOSvr.php::before calling validateReqInputINOV8: \n");
	    #$tpO->validateReqInputINOV8($p);



	    $tpO->validateReqInput($vivaP);
FILOG("VivaDOSvr.php::after calling validateReqInputINOV8: \n");
	
	    $rsp=$tpO->buildRspData($rA1);
FILOG("VivaDOSvr.php:topupRequest: rsp= " . print_r($rsp,true) . "\n");
	###############################
	
	   errlog('topupRequest', $rsp);
	   //return array('topupRequest' => $rsp);
// return topupServiceBean->object????
//$FIrsp=array( "return" => $rsp );
//   return $FIrsp;
   return $rsp;
	
	} // RechargeRequest 
	
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
	
FILOG("VivaDOSvr.php::checkBalance before calling validateReqInputINOV8: \n");
	    $tpO->validateReqInputINOV8($p);
FILOG("VivaDOSvr.php::checkBalance after calling validateReqInputINOV8: \n");
	
	    $rA1=array('accountBalance' => '99.999', 
			'accountBalanceCurrency' => 'FI',
	               'responseCode' => '00' );

	    $rsp=$tpO->buildRspData($rA1);
#############
	
FILOG("VivaDOSvr.php:checkBalance: rsp= " . print_r($rsp,true) . "\n");
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
FILOG(print_r($func,true));

//
// the viva DO wsdl has '.' in fuction name which php does not like.
// they are changed to '_' to make php happy, and then reverted by out_callback
// to '.' to make IMTU happy...

function out_callback($buffer)
{

//FILOG("FI: VivaDOSvr.php: out_callback: buffer =:" . print_r($buffer, true) . "\n");
$outbuff=str_replace('viva_topup_Recharge','viva_topup.Recharge',$buffer);

//FILOG("FI: VivaDOSvr.php: out_callback: outbuff =:" . print_r($outbuff, true) . "\n");
return($outbuff);

} // end out_callback

try {

FILOG("FI: Inside VivaDOSvr.php\n");
foreach($func as $method ) {
	FILOG($method . "\n");
	} // end foreach
FILOG("FI: Inside VivaDOSvr.php HTTP_RAW_POST_DATA=:\n" . $HTTP_RAW_POST_DATA );

//
// edit HTTP_RAW_POST_DATA function name to make php happy
//
$FI_HTTP_RAW_POST_DATA=preg_replace("/".'viva_topup.Recharge'."/",'viva_topup_Recharge',$HTTP_RAW_POST_DATA);

FILOG("FI: Inside VivaDOSvr.php FI_HTTP_RAW_POST_DATA=:\n" . $FI_HTTP_RAW_POST_DATA );
//
//XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

ob_start("out_callback");
$server->handle($FI_HTTP_RAW_POST_DATA);
ob_end_flush();

//FILOG("FI: VivaDOSvr.php: rawRsp =:" . print_r($rawRsp, true) . "\n");

//XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
FILOG("FI: Inside VivaDOSvr.php after handle\n");
FILOG($HTTP_RAW_POST_DATA . "\n");
//FILOG('List of soap methods: ' . "\n");
FILOG("##################################################################################\n");

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
