<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));

include './ImtuTopUpClass.php';
#include '../QAlib/wslib1.php';
include './wslib1.php';

#$wsdlORIG="http://devcall02.ixtelecom.com/VivaDO/VivaRecharge.wsdl";
#$wsdl="http://devcall02.ixtelecom.com/VivaBO/Trilogy.wsdl";
$wsdl="http://devcall02.ixtelecom.com/VivaBO/NuevaTel.wsdl";

#
##################################################
#

// courtesy of 0.015 solutions...

function FImakeXML($a)
{
global $FIxml;
$FIxml = "";
foreach( $a as $tag => $value ) {
	FILOG("VivaBOSvr.php::FImakeXML: tag is: " . $tag . " value is: " . $value . " \n");
	$FIxml = $FIxml . '<' . $tag . '>' . $value . '</' . $tag . '>' ;
	} // ednd foreach

FILOG("VivaBOSvr.php::FImakeXML: outbuff = " . $FIxml . " \n");

return($FIxml);

} // end FImakeXML

class SoapHandler {
	
	// topUpReverse might need to be modified when transactionId is used...
	function topUpReverse($p)
	{
	FILOG("FI: Inside VivaBO.SoapHandler.topUpReverse p= ".print_r($p,true)."\n");

	$transId = $p->transId;
	$transactionId = $p->transactionId;
	$bankId = $p->bankId;
	$agentId = $p->agentId;
	$comment = $p->comment;

	$xA=array('transId' => $transId, 
	        'transactionId' => 'a0',
	        'bankId' => 'a1',
	        'agentId' => 'a2',
	        'comment' => 'a3'
               	);
	
	
	$rA=array('transId' => $transId,
              'returnCode' => 'a6',
              'returnMsg' => 'a7'
               );
	
	$rA1=array('transId' => $transId, 
		'returnCode' => '-343',
		'returnMsg' => 'FI:VivaBO topUpReverse default message.'
		);
	
FILOG("FI: Inside topUpReverse\n");
FILOG("VivaBOSvr.php:topUpReverse: xA= " . print_r($xA,true) . "\n");
FILOG("VivaBOSvr.php:topUpReverse: rA1= " . print_r($rA1,true) . "\n");
	$key = array('app' => 'VIVABO' ,'key' => $subscripterId);
	
	$tpO= new TopupObj($key, $xA, $rA);
	
FILOG("VivaBOSvr.php::before calling validateReqInputINOV8: \n");

	$tpO->validateReqInput($p);
FILOG("VivaBOSvr.php::after calling validateReqInput: \n");
	
	$rsp=$tpO->buildRspData($rA1);

	FImakeXML($rsp);

FILOG("VivaBOSvr.php:topUpReverse: rsp= " . print_r($rsp,true) . "\n");
	###############################
	
	FImakeXML($rsp);
   	return $rsp;

	} // topUpReverse 
	
	function topUpCheck($p)
	{
	FILOG("FI: Inside VivaBO.SoapHandler.topUpCheck p= ".print_r($p,true)."\n");

	$transId = $p->transId;
	$subscripterId = $p->subscripterId;
	$bankId = $p->bankId;
	$agentId = $p->agentId;
	$amount = $p->amount;
	$currency = $p->currency;

	$xA=array('transId' => $transId, 
	        'subscripterId' => 'a0',
	        'bankId' => 'a1',
	        'agentId' => 'a2',
	        'amount' => 'a3',
	        'currency' => 'a4'
               	);
	
	
	$rA=array('transId' => $transId,
              'returnCode' => 'a6',
              'returnMsg' => 'a7',
              'balance' => 'a8'
               );
	
	$rA1=array('transId' => $transId, 
		'returnCode' => '-340',
		'returnMsg' => 'FI:VivaBO topUpCheck default message.',
		'balance' => '0' );
	
FILOG("FI: Inside topUpCheck\n");
FILOG("VivaBOSvr.php:topUpCheck: xA= " . print_r($xA,true) . "\n");
FILOG("VivaBOSvr.php:topUpCheck: rA1= " . print_r($rA1,true) . "\n");
	$key = array('app' => 'VIVABO' ,'key' => $subscripterId);
	
	$tpO= new TopupObj($key, $xA, $rA);
	
FILOG("VivaBOSvr.php::before calling validateReqInputINOV8: \n");

	$tpO->validateReqInput($p);
FILOG("VivaBOSvr.php::after calling validateReqInput: \n");
	
	$rsp=$tpO->buildRspData($rA1);

	FImakeXML($rsp);

FILOG("VivaBOSvr.php:topUpCheck: rsp= " . print_r($rsp,true) . "\n");
	###############################
	
	FImakeXML($rsp);
   	return $rsp;

	} // topUpCheck 
	
	function getBalance($p)
	{
	FILOG("FI: Inside VivaBO.SoapHandler.getBalance p= ".print_r($p,true)."\n");

	$transId = $p->transId;
	$subscripterId = $p->subscripterId;
	$bankId = $p->bankId;
	$agentId = $p->agentId;
	$amount = $p->amount;
	$currency = $p->currency;

	$xA=array('transId' => $transId, 
	        'subscripterId' => 'a0',
	        'bankId' => 'a1',
	        'agentId' => 'a2',
	        'amount' => 'a3',
	        'currency' => 'a4'
               	);
	
	
	$rA=array('transId' => $transId,
              'returnCode' => 'a6',
              'returnMsg' => 'a7',
              'balance' => 'a8'
               );
	
	$rA1=array('transId' => $transId, 
		'returnCode' => '-341',
		'returnMsg' => 'FI:VivaBO getBalance default message.',
		'balance' => '0' );
	
FILOG("FI: Inside getBalance\n");
FILOG("VivaBOSvr.php:getBalance: xA= " . print_r($xA,true) . "\n");
FILOG("VivaBOSvr.php:getBalance: rA1= " . print_r($rA1,true) . "\n");
	$key = array('app' => 'VIVABO' ,'key' => $subscripterId);
	
	$tpO= new TopupObj($key, $xA, $rA);
	
FILOG("VivaBOSvr.php::before calling validateReqInputINOV8: \n");

	$tpO->validateReqInput($p);
FILOG("VivaBOSvr.php::after calling validateReqInput: \n");
	
	$rsp=$tpO->buildRspData($rA1);

	FImakeXML($rsp);

FILOG("VivaBOSvr.php:getBalance: rsp= " . print_r($rsp,true) . "\n");
	###############################
	
	errlog('topupRequest', $rsp);

	FImakeXML($rsp);
   	return $rsp;

	} // getBalance 
	
	function topUp($p)
	{
	FILOG("FI: Inside VivaBO.SoapHandler.topUp p= ".print_r($p,true)."\n");

	$transId = $p->transId;
	$subscripterId = $p->subscripterId;
	$bankId = $p->bankId;
	$agentId = $p->agentId;
	$amount = $p->amount;
	$currency = $p->currency;

	$xA=array('transId' => $transId, 
	        'subscripterId' => 'a0',
	        'bankId' => 'a1',
	        'agentId' => 'a2',
	        'amount' => 'a3',
	        'currency' => 'a4'
               	);
	
	
	$rA=array('transId' => $transId,
              'returnCode' => 'a6',
              'returnMsg' => 'a7',
              'balance' => 'a8'
               );
	
	$rA1=array('transId' => $transId, 
		'returnCode' => '-342',
		'returnMsg' => 'FI:VivaBO recharge default message.',
		'balance' => '0' );
	
FILOG("FI: Inside topupRequest\n");
FILOG("VivaBOSvr.php:topupRequest: xA= " . print_r($xA,true) . "\n");
FILOG("VivaBOSvr.php:topupRequest: rA1= " . print_r($rA1,true) . "\n");
	$key = array('app' => 'VIVABO' ,'key' => $subscripterId);
	
	$tpO= new TopupObj($key, $xA, $rA);
	
FILOG("VivaBOSvr.php::before calling validateReqInputINOV8: \n");

	$tpO->validateReqInput($p);
FILOG("VivaBOSvr.php::after calling validateReqInput: \n");
	
	$rsp=$tpO->buildRspData($rA1);

	FImakeXML($rsp);

FILOG("VivaBOSvr.php:topupRequest: rsp= " . print_r($rsp,true) . "\n");
	###############################
	
	errlog('topupRequest', $rsp);

	FImakeXML($rsp);
   	return $rsp;
	
	} // topUp 
	
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
// VivaBO NuevaTel...
//

function out_callback($buffer)
{
global $outbuff;
global $FIxml;

$outbuff=str_replace('Security SOAP-ENV:mustUnderstand="0"','Security SOAP-ENV:mustUnderstand="1".Recharge',$buffer);

//getBalance
if( strpos($outbuff,'<ns1:getBalanceResponse/>') !== false ) {
	$outbuff=str_replace('<ns1:getBalanceResponse/>',$FIxml.'</return></ns1:getBalanceResponse>',$outbuff);
	$outbuff=str_replace('<SOAP-ENV:Body>','<SOAP-ENV:Header/><SOAP-ENV:Body><ns1:getBalanceResponse><return>',$outbuff);
	}
else if( strpos($outbuff,'<ns1:topUpResponse/>') !== false ) {
//topUp
	$outbuff=str_replace('<ns1:topUpResponse/>',$FIxml.'</return></ns1:topUpResponse>',$outbuff);
	$outbuff=str_replace('<SOAP-ENV:Body>','<SOAP-ENV:Header/><SOAP-ENV:Body><ns1:topUpResponse><return>',$outbuff);
	}
else if( strpos($outbuff,'<ns1:topUpReverseResponse/>') !== false ) {
//topUpReverse
	$outbuff=str_replace('<ns1:topUpReverseResponse/>',$FIxml.'</return></ns1:topUpReverseResponse>',$outbuff);
	$outbuff=str_replace('<SOAP-ENV:Body>','<SOAP-ENV:Header/><SOAP-ENV:Body><ns1:topUpReverseResponse><return>',$outbuff);
	}
else if( strpos($outbuff,'<ns1:topUpCheckResponse/>') !== false ) {
//topUpCheck
	$outbuff=str_replace('<ns1:topUpCheckResponse/>',$FIxml.'</return></ns1:topUpCheckResponse>',$outbuff);
	$outbuff=str_replace('<SOAP-ENV:Body>','<SOAP-ENV:Header/><SOAP-ENV:Body><ns1:topUpCheckResponse><return>',$outbuff);
	} // end if

return($outbuff);

} // end out_callback

try {

FILOG("FI: Inside VivaBOSvr.php\n");
foreach($func as $method ) {
	FILOG($method . "\n");
	} // end foreach
FILOG("FI: Inside VivaBOSvr.php HTTP_RAW_POST_DATA=:\n" . $HTTP_RAW_POST_DATA );

//
// edit HTTP_RAW_POST_DATA function name to make php happy
//
$FI_HTTP_RAW_POST_DATA=preg_replace("/".'Security SOAP-ENV:mustUnderstand="1"'."/",'Security SOAP-ENV:mustUnderstand="0"',$HTTP_RAW_POST_DATA);

//FILOG("FI: Inside VivaBOSvr.php FI_HTTP_RAW_POST_DATA=:\n" . $FI_HTTP_RAW_POST_DATA );
//
//XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

ob_start("out_callback");
$server->handle($FI_HTTP_RAW_POST_DATA);
ob_end_flush();
FILOG("FI: VivaBOSvr.php: out_callback result: outbuff =:" . print_r($outbuff, true) . "\n");

//FILOG("FI: VivaBOSvr.php: rawRsp =:" . print_r($rawRsp, true) . "\n");

//XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
FILOG("FI: Inside VivaBOSvr.php after handle\n");
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
