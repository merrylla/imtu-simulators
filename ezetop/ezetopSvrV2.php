<?php

include 'ImtuTopUpClass.php';
include '../QAlib/wslib1.php';

$fn = preg_replace('/\.php/', '', basename(__FILE__));
$wsdl="http://devcall02.ixtelecom.com/ezetop/EzetopV2.wsdl";

class SoapHandler {

function TopUpPhoneAccount($p)
{
    //errlog("INFO: TopUpPhoneAccount\n", $p);

    $a=complex2simpleA($p);

    $AuthenticationID=$p->AuthenticationToken->AuthenticationID;
    $AuthenticationPassword=$p->AuthenticationToken->AuthenticationPassword;
    $MessageID=$p->MessageID;
    $CountryCode=$p->CountryCode;
    $OperatorCode=$p->OperatorCode;
    $PhoneNumber=$p->PhoneNumber;
    $Amount=$p->Amount;
    $TransferType=$p->TransferType;

    $xA=array('AuthenticationID' => 'a0', 
              'AuthenticationPassword' => 'a1',
              'SMSMessagePhoneNumber' => 'a2',
              'SMSMessageText' => 'a3',
              'Amount' => 'a4');

    $rA=array('StatusID' => 'a5',
              'StatusDescription' => 'a6');

    if (strlen($CountryCode) != 2) {
        $rTopUpPhoneAccountStatus= array( 'StatusID' => 42,
             'StatusDescription' => 'The Length of CountryCode must be two characters',
              'ConfirmationID' => 0);
    }//restrict 2 bytes country code
    else $rTopUpPhoneAccountStatus= array( 'StatusID' => 1,
                                          'StatusDescription' => 'ok',
                                          'ConfirmationID' => rand(30000000, 99999999));

    $rTopUpPhoneAccountAmountSent= array('Amount' => $Amount,
                                         'AmountExcludingTax' => $Amount * .9,
                                         'TaxName' => 'GCT',
                                         'TaxAmount' => 25,
                                         'CurrencyCode' => 'JMD'
                                         );

    if (isset($TransferType) && ($TransferType == 'PINS')) {
      $rPIN = substr(str_shuffle(str_repeat("0123456789", 8)), 0, 13);
      $rSerial = substr(str_shuffle(str_repeat("0123456789", 8)), 0, 12);
      $rExpires = '4/21/2066 04:21:00';
      $rCustomerCareNumber = '8008894196';
      $rDisclaimer = 'Purchase at your own risk!';
      $rAdditional = 'Pin: ' . $rPIN . '&#xD;Serial Number: ' . $rSerial . '&#xD;Expiry Date: ' . $rExpires . '&#xD;';

      $rA1=array('MessageID' => $MessageID, 
		 'TopUpPhoneAccountStatus' => $rTopUpPhoneAccountStatus, 
		 'TopUpPhoneAccountAmountSent' => $rTopUpPhoneAccountAmountSent,
		 'CustomerCareNumber' => $rCustomerCareNumber,
		 'TextDisclaimer' => $rDisclaimer,
		 'TextAdditional' => $rAdditional
		 ); 
    } else {
      $rA1=array('MessageID' => $MessageID, 
		 'TopUpPhoneAccountStatus' => $rTopUpPhoneAccountStatus, 
		 'TopUpPhoneAccountAmountSent' => $rTopUpPhoneAccountAmountSent
		 ); 
    }

    if (strlen($CountryCode) != 2) {
        errlog('Sending TopUpPhoneAccount Response:', $rA1);
        return $rA1;
    }

    $key = array('app' => 'ezetop' ,'key' => $PhoneNumber);

    $tpO= new TopupObj($key, $xA, $rA);

    $tpO->validateReqInput($a);

    $rsp=$tpO->buildRspData1($rA1);

    if ($rsp['TopUpPhoneAccountStatus']['StatusID'] != '1' ) $rsp['TopUpPhoneAccountStatus']['ConfirmationID']=0;

   errlog('Sending TopUpPhoneAccount Response:', $rsp);
   return $rsp;

}//TopUpPhoneAccount 

 function GetTargetTopUpAmount($request)
 {
   $request->Amount = 50.0;

   $response = array('MessageID'                  => $request->MessageID,
		     'CountryCode'                => $request->CountryCode,
		     'Amount'                     => $request->Amount * 1.1,
		     'AmountExcludingTax'         => $request->Amount,
		     'TaxName'                    => 'GCT',
		     'TaxAmount'                  => $request->Amount * 0.1,
		     'CurrencyCode'               => 'USD',
		     'GetTargetTopUpAmountStatus' => array( 'StatusID'          => 1,
							    'StatusDescription' => 'OK'));

   return $response;
 }

function GetBalance($p)
{
   $AuthenticationToken=$p->AuthenticationToken;

   $MessageID=$p->MessageID;
   $id=$p->AuthenticationToken->AuthenticationID; 
   $pw=$p->AuthenticationToken->AuthenticationPassword; 

   $rsp=array('MessageID' => $MessageID,
              'Balance' => 28.60
             );

   return $rsp;

}//GetBalance

function GetProductDescriptions($p)
{
  return array();
}

function GetProductList($p)
{
  return array();
}

function GetTopUpTransactionStatus($p)
{
  return array();
}

function IsCountrySupportedByEzeOperator($p)
{
  return array();
}

function SendSMS($p)
{
  return array();
}

function ValidatePhoneAccount($p)
{
  return array();
}

} //SoapHandler

class EzeProxy
{
  public function __call($methodName, $args) {
    $handler = new SoapHandler();

    errlog(__FUNCTION__ . ': ' . print_r($args, true));

    $result = call_user_func_array(array($handler, $methodName),  $args);

    errlog(__FUNCTION__ . ': ' . print_r($result, true));

    return $result;
  }
}


// $msg= "DEBUG: HTTP_RAW_POST_DATA:".mix2str($HTTP_RAW_POST_DATA)."\n";
// errlog('Main', $msg);

$server = new SoapServer($wsdl, array('soap_version' => SOAP_1_2,
				      'uri' => 'http://edts.api.v2/'));
$server->setClass("EzeProxy");
# $func=$server->getFunctions();
# errlog('Main - List of soap methods:', $func);

try {

$server->handle();

}
catch (Exception $e) {

   $errMsg="CatchException";
   //$status= $e->getCode();
   //the return code eg 01 cannot be stored in getCode. hence use getMessage.
   $status= $e->getMessage();

   errlog('Rsp: <Error>', $status."</Error>");
   return array('Error' => $status);
}

?>
