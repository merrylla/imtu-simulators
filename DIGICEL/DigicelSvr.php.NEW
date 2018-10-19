<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));

include './ImtuTopUpClass.php';
#include '../QAlib/wslib1.php';
include './wslib1.php';

#$wsdl="http://devcall02.ixtelecom.com/Digicel/SIMExpress.wsdl";
$wsdl="http://devcall02.ixtelecom.com/DIGICEL/DigicelSV-DTS.wsdl";

$loginName='testarg0';
$password='testarg1';

// courtesy of 0.015 solutions...

function FImakeXML($a)
{
global $FIxml;
$FIxml = "";
foreach( $a as $tag => $value ) {
        FILOG("DigicelSvr.php::FImakeXML: tag is: " . $tag . " value is: " . $value . " \n");
        $FIxml = $FIxml . '<' . $tag . '>' . $value . '</' . $tag . '>' ;
        } // end foreach

FILOG("DigicelSvr.php::FImakeXML: outbuff = " . $FIxml . " \n");

return($FIxml);

} // end FImakeXML

#
##################################################
#
class SoapHandler {
	
	function OLDlogin($p)
	{
	global $loginName;
	global $password;
FILOG("FI: Inside Digicel.login p= ".print_r($p,true)."\n");
	$rsp=array('responseCode' => '0',
		'loginKey' => 'FIloginkey'); 
	$key = array('app' => 'DIGICEL' ,'key' => '%');
	$tpO= new TopupObj($key, $xA, $rA);
	if( $p->arg0 == $loginName ) {
		$rsp = array('responseCode' => '0',
                        'loginKey' => 'aWR0X2R0czEzODU0NzMxODc1OTY');
		}
	else {
		$rsp = array('responseCode' => '404');
		} //end if
	FImakeXML($rsp);
	return $rsp;
	} // end login

//====
	function login($p)
	{
FILOG("FI: Inside Digicel.login p= ".print_r($p,true)."\n");

	//check for: username, password
        $xA=array('arg0' => 'a0',              // username
                'arg1' => 'a1'                 // password
                );
	//return: 
        $rA=array('responseCode' => 'a2',
              'loginKey' => 'a3'
               );

        $rA1=array('responseCode' => '0',
              'loginKey' => 'aWR0X2R0czEzODU0NzMxODc1OTY='
               );
	$key = array('app' => 'DIGICEL' ,'key' => $p->arg0);

FILOG("FI: Inside Digicel.login key= ".print_r($key,true)."\n");

	$tpO= new TopupObj($key, $xA, $rA);

	$tpO->validateReqInput($p);

	$rsp=$tpO->buildRspData($rA1);

	if( $rsp['responseCode'] != '0' ) {
		unset($rsp['loginKey']);
		}
	FImakeXML($rsp);
FILOG("FI: Inside Digicel.login rsp= ".print_r($rsp,true)."\n");
	return $rsp;
	} // end login

	function logout($p)
	{
FILOG("FI: Inside Digicel.logout p= ".print_r($p,true)."\n");

	//check for: loginKey
        $xA=array('arg0' => 'a0'              // loginKey
                );
	//return: 
        $rA=array('return' => 'a1' 		// responseCode
               );

        $rA1=array('return' => '0'
               );

	$key = array('app' => 'DIGICEL' ,'key' => $p->arg0);

FILOG("FI: Inside Digicel.logout key= ".print_r($key,true)."\n");

	$tpO= new TopupObj($key, $xA, $rA);

	$tpO->validateReqInput($p);

	$rsp=$tpO->buildRspData($rA1);

	if( $rsp['responseCode'] != '0' ) {
		unset($rsp['loginKey']);
		}
FILOG("FI: Inside Digicel.logout rsp= ".print_r($rsp,true)."\n");
	FImakeXML($rsp);
	return $rsp;
	} // end logout
//====
	function rechargeMSISDN($p)
	{
	//global $loginName;
	//global $password;
FILOG("FI: Inside Digicel.rechargeMSISDN p= ".print_r($p,true)."\n");

	//check for; msisdn, amount,currency  
        $xA=array('arg3' => 'a0',               //msisdn
                'arg4' => 'a1',                 // amount
                'arg5' => 'a2',                 // currency
                );
	//return: 
        $rA=array('responseCode' => 'a3',
              'clientDate' => $p->arg2,
              'currency' => 'a4',
              'MSISDN' => 'a5',
              'newAccountBalance' => 'a6',
              'rechargeExpiryDate' => 'a7',
              'timezone' => 'a8',
              'transactionAmount' => 'a9',
              'transactionID' => $p->arg1,
              'transactionTimestamp' => date('U')
              );

	$key = array('app' => 'DIGICEL' ,'key' => $p->arg3);
	$tpO= new TopupObj($key, $xA, $rA);

	$tpO->validateReqInput($p);

// CFW check if response was in DB else default with SUNNY DAY!!!
if (!isset($tpO->re['responseCode']))
{	
        $rA=array('responseCode' => '0',
              'clientDate' => $p->arg2,
              'currency' => $p->arg5,
              'MSISDN' => $p->arg3,
              'newAccountBalance' => $p->arg4,
              'rechargeExpiryDate' => '2017-08-21T00:00:00-06:00',
              'timezone' => '-6',
              'transactionAmount' => $p->arg4,
              'transactionID' => $p->arg1,
              'transactionTimestamp' => date('U')
               );
}

	$rsp=$tpO->buildRspData($rA);

	FImakeXML($rsp);
	return $rsp;
	} // end rechargeMSISDN
//====
	function validateMSISDN($p)
	{
	//global $loginName;
	//global $password;
FILOG("FI: Inside Digicel.rechargeMSISDN p= ".print_r($p,true)."\n");

	//check for; msisdn, amount,currency  
        $xA=array('arg3' => 'a0',               //msisdn
                'arg4' => 'a1',                 // amount
                'arg5' => 'a2',                 // currency
                );
	//return: 
        $rA=array('responseCode' => 'a3',
              'accountBalance' => 'a6',
              'clientDate' => $p->arg2,
              'currency' => 'a4',
              'MSISDN' => 'a5',
              'rechargeExpiryDate' => 'a7',
              'timezone' => 'a8',
              'transactionAmount' => 'a9',
              'transactionID' => $p->arg1,
              'transactionTimestamp' => date('U')
               );

FILOG("FI: Inside Digicel.validateMSISDN rA= ".print_r($rA,true)."\n");
	$key = array('app' => 'DIGICEL' ,'key' => $p->arg3);
	$tpO= new TopupObj($key, $xA, $rA);

	$tpO->validateReqInput($p);

	$rsp=$tpO->buildRspData($rA1);

FILOG("FI: Inside Digicel.validateMSISDN rsp= ".print_r($rsp,true)."\n");
	FImakeXML($rsp);
	return $rsp;
	} // end validateMSISDN
//====

	function clientAccountBalance($p)
	{
FILOG("FI: Inside Digicel.clientAccountBalance p= ".print_r($p,true)."\n");

	//check for; loginKey
        $xA=array('arg0' => 'a0'               //loginKey
                );

	//return: 
        $rA=array('responseCode' => 'a1',
              'accountBalance' => 'a2',
              'timestamp' => date('U'),
              'timezone' => 'a3'
               );

        $rA1=array('responseCode' => '0',
              'accountBalance' => '329',
              'timestamp' => date('U'),
              'timezone' => '-6'
               );

	$key = array('app' => 'DIGICEL' ,'key' => $p->arg0);
	$tpO= new TopupObj($key, $xA, $rA);

	$tpO->validateReqInput($p);

	$rsp=$tpO->buildRspData($rA1);
FILOG("FI: Inside Digicel.clientAccountBalance rsp= ".print_r($rsp,true)."\n");

	if( $rsp['responseCode'] != '0' ) {
		unset($rsp['accountBalance']);
		}

	FImakeXML($rsp);
	return $rsp;
	} // end clientAccountBalance

} //SoapHandler

if (empty($HTTP_RAW_POST_DATA)) {

    $doc = new DOMDocument();
    $doc->load('./rechargeAccount.xml');
    $HTTP_RAW_POST_DATA= $doc->saveXML();
}

errlog('Main', $HTTP_RAW_POST_DATA);

//
// notice $server is started differently here...
//
$server = new SoapServer($wsdl, array('soap_version' => SOAP_1_2));
#$server = new SoapServer(null, array('uri' =>$wsdl));
$server->setClass("SoapHandler");
$func=$server->getFunctions();
FILOG(print_r($func,true));

//
// the viva DO wsdl has '.' in fuction name which php does not like.
// they are changed to '_' to make php happy, and then reverted by out_callback
// to '.' to make IMTU happy...

function out_callback($buffer)
{
global $FIresp;
global $FIxml;

//return($buffer);

$start=strpos($buffer,"<SOAP-ENV:Body>");
$end=strpos($buffer,"</SOAP-ENV:Body>");

//login
if( strpos($buffer,'<ns1:loginResponse/>') !== false ) {
	$lRR = '<ns2:loginResponse xmlns:ns2="http://server.dtsws/"><return>'.$FIxml.'</return></ns2:loginResponse>';
 	$outbuff= substr($buffer,0,$start) . '<SOAP-ENV:Body>' . $lRR . substr($buffer,$end);
        }
//rechargeMSISDN
else if( strpos($buffer,'<ns1:rechargeMSISDNResponse/>') !== false ) {
	$rMR = '<ns2:rechargeMSISDNResponse xmlns:ns2="http://server.dtsws/"><return>'.$FIxml.'</return></ns2:rechargeMSISDNResponse>';
 	$outbuff= substr($buffer,0,$start) . '<SOAP-ENV:Body>' . $rMR . substr($buffer,$end);
        }
//clientAccountBalance
else if( strpos($buffer,'<ns1:clientAccountBalanceResponse/>') !== false ) {
	$cABR = '<ns2:clientAccountBalanceResponse xmlns:ns2="http://server.dtsws/"><return>'.$FIxml.'</return></ns2:clientAccountBalanceResponse>';
 	$outbuff= substr($buffer,0,$start) . '<SOAP-ENV:Body>' . $cABR . substr($buffer,$end);
        }
//validateMSISDNResponse
else if( strpos($buffer,'<ns1:validateMSISDNResponse/>') !== false ) {
	$cABR = '<ns2:validateMSISDNResponse xmlns:ns2="http://server.dtsws/"><return>'.$FIxml.'</return></ns2:validateMSISDNResponse>';
 	$outbuff= substr($buffer,0,$start) . '<SOAP-ENV:Body>' . $cABR . substr($buffer,$end);
        }
else {
	$outbuff = $buffer;
	}

$FIresp = $outbuff;
return($outbuff);

} // end out_callback

try {

FILOG("FI: Inside DigicelSvr.php\n");
foreach($func as $method ) {
	FILOG($method . "\n");
	} // end foreach
FILOG("FI: Inside DigicelSvr.php HTTP_RAW_POST_DATA=:\n" . $HTTP_RAW_POST_DATA );

//
// edit HTTP_RAW_POST_DATA function name to make php happy
//
$FI_HTTP_RAW_POST_DATA=preg_replace("/".'viva_topup.Recharge'."/",'viva_topup_Recharge',$HTTP_RAW_POST_DATA);

FILOG("FI: Inside DigicelSvr.php FI_HTTP_RAW_POST_DATA=:\n" . $FI_HTTP_RAW_POST_DATA );
//
//XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

ob_start("out_callback");
$server->handle($FI_HTTP_RAW_POST_DATA);
ob_end_flush();

FILOG("FI: DigicelSvr.php: FIresp =:" . print_r($FIresp, true) . "\n");
//FILOG("FI: DigicelSvr.php: rawRsp =:" . print_r($rawRsp, true) . "\n");

//XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
FILOG("FI: Inside DigicelSvr.php after handle\n");
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
