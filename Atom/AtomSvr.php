<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));

include './ImtuTopUpClass.php';
#include '../QAlib/wslib1.php';
include './wslib1.php';

$wsdl="http://devcall02.ixtelecom.com/Atom/FlashLink.wsdl";

function FImakeXML($a)
{
global $FIxml;
$FIxml = "";
foreach( $a as $tag => $value ) {
        FILOG("AtomSvr.php::FImakeXML: tag is: " . $tag . " value is: " . $value . " \n");
        $FIxml = $FIxml . '<ns1:' . $tag . '>' . $value . '</ns1:' . $tag . '>' ;
        } // end foreach

FILOG("VivaBOSvr.php::FImakeXML: outbuff = " . $FIxml . " \n");

return($FIxml);

} // end FImakeXML


#
##################################################
#
class SoapHandler {
#class Atom_topup {
	
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

//- OPERATOR_ID path property is required. This maps to the TargetOperatorId
// field in FlashLink topup requests. e.g. 16 for Aircel India.
//
//        13 = Vodafone India
//        14 = Idea India
//        15 = Reliance India
//        16 = Aircel India
//        17 = BSNL India
//        18 = Uninor India
//        19 = MTS India
//        20 = Airtel India


	function GetAvailableCountries($p)
	{
FILOG("FI: Inside Atom.GetAvailableCountries p= ".print_r($p,true)."\n");

	$ResellerUsername=$p->ResellerUsername;

	   $rsp=array('TransactionAccepted' => 'true',
			'AtomUniqueTransactionId' => $rechargeId,
			'MobileTopupCurrencyCode' => $code,
			'MobileTopupAmount' => $message,
			'IsMobileTopupAmountActual' => 1,
			'Message' => 'FI'
			); 
FILOG("FI: Inside Atom.GetAvailableCountries rsp=\n".print_r($rsp,true)."\n");
#	
	    $xA=array('ResellerUsername' => 'a0'
			);
	
	    $rA=array('CountryId' => 'a1',
	              'CountryCode' => 'a2',
	              'Name' => 'a3',
	              'MobileFormat' => 'a4'
	               );
	
	    $rA1=array('CountryId' => '20',
	              'CountryCode' => 'IND',
	              'Name' => 'India',
	              'MobileFormat' => '91xxxxxxxxxx'
	               );
	
FILOG("FI: Inside GetAvailableCountries\n");
FILOG("AtomSvr.php:GetAvailableCountries: xA= " . print_r($xA,true) . "\n");
FILOG("AtomSvr.php:GetAvailableCountries: rA1= " . print_r($rA1,true) . "\n");
	    $key = array('app' => 'ATOM' ,'key' => $MobileNumber);
	
	    $tpO= new TopupObj($key, $xA, $ACS);
	
FILOG("AtomSvr.php::before calling validateReqInputINOV8: \n");
	    #$tpO->validateReqInputINOV8($p);

	    $tpO->validateReqInput($p);
FILOG("AtomSvr.php::after calling validateReqInputINOV8: \n");
	
	    $rsp=$tpO->buildRspData($rA1);
FILOG("AtomSvr.php:GetAvailableCountries: rsp= " . print_r($rsp,true) . "\n");
	###############################
	
	   errlog('GetAvailableCountries', $rsp);
// return topupServiceBean->object????
//$FIrsp=array( "return" => $rsp );
//   return $FIrsp;
	FImakeXML($rsp);
   return $rsp;
	
	} // end GetAvailableCountries 
	
	function ProcessTransaction($p)
	{

FILOG("FI: Inside Atom.ProcessTransaction p=\n".print_r($p,true)."\n");
	
	$ResellerUsername=$p->ResellerUsername;
	$TerminalKey=$p->TerminalKey;
	$MobileNumber=$p->MobileNumber;
	$TargetCountryCodeId=$p->TargetCountryCodeId;
	$TargetOperatorId=$p->TargetOperatorId;
	$TransactionAmount=$p->TransactionAmount;
	$ResellerUniqueTransactionId=$p->ResellerUniqueTransactionId;
#	
	   $rsp=array('TransactionAccepted' => 'true',
			'AtomUniqueTransactionId' => 'a11',
			'MobileTopupCurrencyCode' => 'USD',
			'MobileTopupAmount' => $TransactionAmount,
			'IsMobileTopupAmountActual' => 1,
			'Message' => 'FI',
                        'StatusCode' => '00'
			); 
FILOG("FI: Inside Atom.ProcessTransaction rsp=\n".print_r($rsp,true)."\n");
#	
// Here is an example of the fields IMTU sends for Atom
// <SOAP-ENV:Body>
// <flashns1:ProcessTransaction>
// <flashns1:ResellerUsername></flashns1:ResellerUsername>
// <flashns1:TerminalKey></flashns1:TerminalKey>
// <flashns1:MobileNumber>919442342801</flashns1:MobileNumber>
// <flashns1:TargetCountryCodeId>20</flashns1:TargetCountryCodeId>
// <flashns1:TargetOperatorId>-1</flashns1:TargetOperatorId>
// <flashns1:TransactionAmount>500</flashns1:TransactionAmount>
// <flashns1:ResellerUniqueTransactionId>601642138487268009</flashns1:ResellerUniqueTransactionId>
// </flashns1:ProcessTransaction>
// </SOAP-ENV:Body></SOAP-ENV:Envelope>^M

	    $xA=array('ResellerUsername' => 'a0', 
	              'TerminalKey' => 'a1',
	              'MobileNumber' => 'a2',
	              'TargetCountryCodeId' => 'a3',
	              'TargetOperatorId' => '-1',
	              'TransactionAmount' => 'a4',
	              'ResellerUniqueTransactionId' => $ResellerUniqueTransactionId
			);
	
	    $rA=array('TransactionAccepted' => 'a5',
	              'AtomUniqueTransactionId' => 'a11',
	              'MobileTopupCurrencyCode' => 'a6',
	              'MobileTopupAmount' => 'a7',
	              'IsMobileTopupAmountActual' => 'a8',
	              'Message' => 'a9',
                      'StatusCode' => 'a10'
	               );
	
	    $rA1=array('TransactionAccepted' => 'true',
	              'AtomUniqueTransactionId' => 'a11',
	              'MobileTopupCurrencyCode' => 'USD',
	              'MobileTopupAmount' => '-1',
	              'IsMobileTopupAmountActual' => '1',
	              //'Message' => 'FI:Atom recharge default message.'
                      'StatusCode' => '00'
	               );
	
FILOG("FI: Inside ProcessTransaction\n");
FILOG("AtomSvr.php:ProcessTransaction: xA= " . print_r($xA,true) . "\n");
FILOG("AtomSvr.php:ProcessTransaction: rA1= " . print_r($rA1,true) . "\n");
	    $key = array('app' => 'ATOM' ,'key' => $MobileNumber);
	
	    $tpO= new TopupObj($key, $xA, $rA);
	
FILOG("AtomSvr.php::before calling validateReqInputINOV8: \n");
	    #$tpO->validateReqInputINOV8($p);

	    $tpO->validateReqInput($p);
FILOG("AtomSvr.php::after calling validateReqInputINOV8: \n");
	
	    $rsp=$tpO->buildRspData($rA1);
FILOG("AtomSvr.php:ProcessTransaction: rsp= " . print_r($rsp,true) . "\n");
	###############################
	
	   errlog('ProcessTransaction', $rsp);
// return topupServiceBean->object????
//$FIrsp=array( "return" => $rsp );
//   return $FIrsp;
	FImakeXML($rsp);
   return $rsp;
	
	} // ProcessTransaction 
	
	function AccountEnquiry($p)
	{
	
	   $rsp=array(  'ExtraDetail' => 'SIM response to Atom AccountEnquiry',
			'RequestAccepted' => 'true',
			'AccountBalance' => '330000',
			'CurrencyCode' => 'USD',
                        'StatusCode' => '00'
			); 

	FImakeXML($rsp);
   return $rsp;
	} // AccountEnquiry
	
	function transactionStatus($p)
	{
FILOG("FI: Inside transactionStatus\n");
	
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

function out_callback($buffer)
{
global $outbuff;
global $FIxml;
global $flag;

$flag = 'FALSE';

if( strpos($buffer,'<ns1:AccountEnquiryResponse/>') !== false ) {
	$outbuff=str_replace('<ns1:AccountEnquiryResponse/>','<ns1:AccountEnquiryResponse><ns1:AccountEnquiryResult>'.$FIxml.'</ns1:AccountEnquiryResult></ns1:AccountEnquiryResponse>',$buffer);
        $outbuff=str_replace('<env:Body>','<env:Header/><env:Body>',$outbuff);
	$outbuff=str_replace('<ns1:','<flashns1:',$outbuff);
	$outbuff=str_replace('</ns1:','</flashns1:',$outbuff);
	$outbuff=str_replace(':ns1=',':flashns1=',$outbuff);

	$outbuff=str_replace('<env:','<SOAP-ENV:',$outbuff);
	$outbuff=str_replace('</env:','</SOAP-ENV:',$outbuff);
	$outbuff=str_replace(':env=',':SOAP-ENV=',$outbuff);
	$flag = 'IN5';
        }
else if( strpos($buffer,'<ns1:ProcessTransactionResponse/>') !== false ) {
	$outbuff=str_replace('<ns1:ProcessTransactionResponse/>','<ns1:ProcessTransactionResponse><ns1:ProcessTransactionResult>'.$FIxml.'</ns1:ProcessTransactionResult></ns1:ProcessTransactionResponse>',$buffer);
        //$outbuff=str_replace('<env:Body>','<env:Header/><env:Body><ns1:ProcessTransactionResponse>',$outbuff);
        $outbuff=str_replace('<env:Body>','<env:Header/><env:Body>',$outbuff);
	$outbuff=str_replace('<ns1:','<flashns1:',$outbuff);
	$outbuff=str_replace('</ns1:','</flashns1:',$outbuff);
	$outbuff=str_replace(':ns1=',':flashns1=',$outbuff);

	$outbuff=str_replace('<env:','<SOAP-ENV:',$outbuff);
	$outbuff=str_replace('</env:','</SOAP-ENV:',$outbuff);
	$outbuff=str_replace(':env=',':SOAP-ENV=',$outbuff);
	$flag = 'IN4';
        }
else if( strpos($buffer,'<ns1:GetAvailableCountriesResponse/>') !== false ) {
	$outbuff=str_replace('<ns1:GetAvailableCountriesResponse/>','<ns1:GetAvailableCountriesResponse><ns1:GetAvailableCountriesResult><ns1:Message>Atom Simulator</ns1:Message><ns1:ExtraDetail></ns1:ExtraDetail><ns1:AvailableCountries><ns1:AvailableCountry>'.$FIxml.'</ns1:AvailableCountry></ns1:AvailableCountries></ns1:GetAvailableCountriesResult></ns1:GetAvailableCountriesResponse>',$buffer);
        //$outbuff=str_replace('<env:Body>','<env:Header/><env:Body><ns1:GetAvailableCountriesResponse>',$outbuff);
        $outbuff=str_replace('<env:Body>','<env:Header/><env:Body>',$outbuff);
	$outbuff=str_replace('<ns1:','<flashns1:',$outbuff);
	$outbuff=str_replace('</ns1:','</flashns1:',$outbuff);
	$outbuff=str_replace(':ns1=',':flashns1=',$outbuff);

	$outbuff=str_replace('<env:','<SOAP-ENV:',$outbuff);
	$outbuff=str_replace('</env:','</SOAP-ENV:',$outbuff);
	$outbuff=str_replace(':env=',':SOAP-ENV=',$outbuff);
	$flag = 'IN1';
        }
else if( strpos($buffer,'<flashns1:GetAvailableCountriesResponse/>') !== false ) {
	$outbuff=str_replace('<flashns1:GetAvailableCountriesResponse/>','<flashns1:AvailableCountries>'.$FIxml.'</flashns1:AvailableCountries></flashns1:GetAvailableCountriesResponse>',$buffer);
        $outbuff=str_replace('<SOAP-ENV:Body>','<SOAP-ENV:Header/><SOAP-ENV:Body><flashns1:GetAvailableCountriesResponse>',$outbuff);
	$flag = 'IN2';
        }
else {
	$outbuff=$buffer;
	$flag = 'IN3';
	} // end if

return($outbuff);

} // end out_callback

try {

FILOG("FI: Inside AtomSvr.php\n");
foreach($func as $method ) {
	FILOG($method . "\n");
	} // end foreach
FILOG("FI: Inside AtomSvr.php HTTP_RAW_POST_DATA=:\n" . $HTTP_RAW_POST_DATA );

//
// edit HTTP_RAW_POST_DATA function name to make php happy
//
$FI_HTTP_RAW_POST_DATA=preg_replace("/".'Atom_topup.Recharge'."/",'Atom_topup_Recharge',$HTTP_RAW_POST_DATA);

FILOG("FI: Inside AtomSvr.php FI_HTTP_RAW_POST_DATA=:\n" . $FI_HTTP_RAW_POST_DATA );
//

ob_start("out_callback");
$server->handle($FI_HTTP_RAW_POST_DATA);
ob_end_flush();

//FILOG("FI: AtomSvr.php: rawRsp =:" . print_r($rawRsp, true) . "\n");

FILOG("FI: Inside AtomSvr.php after handle\n");
FILOG($HTTP_RAW_POST_DATA . "\n");
FILOG("FI: Inside AtomSvr.php outbuff=:" . $outbuff );
FILOG("FI: Inside AtomSvr.php flag=:" . $flag );
//FILOG('List of soap methods: ' . "\n");
FILOG("##################################################################################\n");

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
