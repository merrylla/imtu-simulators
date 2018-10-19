<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));

include 'ImtuTopUpClass.php';
include '../QAlib/wslib1.php';

$wsdl="http://devcall02.ixtelecom.com/ezetop/ezetop.wsdl";

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

    $rA1=array('MessageID' => $MessageID, 
                'TopUpPhoneAccountStatus' => $rTopUpPhoneAccountStatus, 
                'TopUpPhoneAccountAmountSent' => $rTopUpPhoneAccountAmountSent
              ); 

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

} //SoapHandler


$msg= "DEBUG: HTTP_RAW_POST_DATA:".mix2str($HTTP_RAW_POST_DATA)."\n";
errlog('Main', $msg);

$server = new SoapServer($wsdl, array('soap_version' => SOAP_1_2));
$server->setClass("SoapHandler");
//$func=$server->getFunctions();
//errlog('Main - List of soap methods:', $func);

try {

$server->handle($HTTP_RAW_POST_DATA);

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
