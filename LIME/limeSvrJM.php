<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));

include '../ezetop/ImtuTopUpClass.php';
include '../QAlib/wslib1.php';

$wsdl="http://devcall02.ixtelecom.com/LIME/LIME.wsdl";

class SoapHandler {

function rechargeAccount($p)
{
    errlog("INFO: rechargeAccount\n", $p);

    $uid=$p->uid;
    $authKey=$p->authKey;
    $msisdn=$p->msisdn;
    $tenderedAmount=$p->tenderedAmount;
    $rechargeValue=$p->rechargeValue;
    $currencyCode=$p->currencyCode;
    $currencyConversionRate=$p->currencyConversionRate;
    $serverId=$p->serverId;
    $channelType=$p->channelType;
    $locationId=$p->locationId;
    $merchantId=$p->merchantId;
    $terminalId=$p->terminalId;
    $transactionType=$p->transactionType;
    $transactionId=$p->transactionId;

    $xA=array('authKey' => 'a0', 
              'msisdn' => 'a1',
              'rechargeValue' => 'a2',
              'serverId' => 'a3',
              'locationId' => 'a4');

    $rA=array('returnCode' => 'a5',
              'returnDescription' => 'a6',
              'primaryBalance' =>'a7',
              'balanceExpiry' =>'a8');

    $year=date('Y')+2;

    $rA1=array('returnCode' => 0, 
               'returnDescription' => 'SUCCESS',
               'primaryOfferId' => 51,
               'ratingState' => 2,
               'conversionFaceValue' => $rechargeValue*1.9802,
               'conversionRate' => 1.9802,
               'primaryBalance' => 12.34,
               'balanceExpiry' => "$year-05-02 00:00:00"
                ); 

    $key = array('app' => 'LIME' ,'key' => $msisdn);

    $tpO= new TopupObj($key, $xA, $rA);

    $tpO->validateReqInput($p);

    errlog('rechargeAccount about to buildingRspData with rA1: $rA1, tpO:', $tpO);
    $rsp=$tpO->buildRspData($rA1);

   errlog('rechargeAccount', $rsp);
   //return array('rechargeAccountResult' => $rsp);
   return $rsp;

}//rechargeAccount 

function reverseRechargeAccount($p)
{

   $debitValue=$p->debitValue;
   $originCurrencyCode=$p->originCurrencyCode;
   $rechargeTransactionId=$p->rechargeTransactionId;
   $transactionId=$p->transactionId;

   $rsp=array('returnCode' => 0, 
                'returnDescription' => 'ok'); 

   return $rsp;

}//reverseRechargeAccount

} //SoapHandler

if (empty($HTTP_RAW_POST_DATA)) {

    $doc = new DOMDocument();
    $doc->load('./rechargeAccount.xml');
    $HTTP_RAW_POST_DATA= $doc->saveXML();
}

errlog('Main', $HTTP_RAW_POST_DATA);

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
