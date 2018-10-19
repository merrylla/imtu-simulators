<?php
ini_set("log_errors", 1);
include '../Remittance/rmttObjBase.php';
include 'ppndata.php';
include '../Remittance/dbClass.php';
include '../QAlib/wslib.php';

$wsdl="http://devcall02.ixtelecom.com/wsdl/PPN.wsdl";
$fn=preg_replace('/\.php/', '', basename(__FILE__));
ini_set("error_log", "/logs/" . $fn . ".log");

function errlog($modName, $msgAnyType, $eNum=0)
{
     global $fn;

     if (gettype($msgAnyType) == 'string') {
        $errMsg=&$msgAnyType;
     }
     else {
        $errMsg=mix2str($msgAnyType);
     }
     sLog($fn, $modName, $errMsg, $eNum);
}

class SoapHandler {

function PurchasePin($q)
{
    errlog("INFO: PurchasePin top q: ", $q);
    $keyA=array('x_account_number' => $q->skuId."746842061");

    $dbO=getDBdata2('IMTU_DATA', 'TopupObj', $keyA);
    if ($dbO == FALSE) {
        errlog("INFO: TRANSFERTO topUpReq - not using test data from DB\n", $keyA);
        $dbO = new TopupObj;
       }

    $dbO->remap('invoice');

    $rsp= $dbO->setAfterDb($q);

    $delay= $dbO->delay;
    if ($delay>0) {

           errlog("WARN: Delaying resp for ", $delay." seconds");
           time_sleep_until(microtime(true) + $delay);
      }

    if ($dbO->abort == 'true') {

        errlog("WARN: Received abort instruction - exiting", "topUpReq");
        exit(0);
    }


    errlog('Rsp: ', $rsp);
    errlog("INFO: PurchasePin returningq: ", $rsp);
    return $rsp;

}

function PurchaseRtr2($q)
{
    errlog("INFO: PurchaseRtr2 Top: q:", $q);
    $keyA=array('x_account_number' => $q->mobile);

    $dbO=getDBdata1('IMTU_DATA', 'TopupObj', $keyA);
    if ($dbO == FALSE) {
        errlog("INFO: TRANSFERTO topUpReq - not using test data from DB\n", $keyA);
        $dbO = new TopupObj;
// Moved remap here to keep from clobbering response from DB
    $dbO->remap('topup');
       }

    $rsp= $dbO->setAfterDb($q);
// DEBUG CFW 
errlog("DEBUG: dumping RSP\n", $rsp);

    $tid = rand();

     $localCurrencyAmount=$q->amount;
     $salesTaxAmount = $localCurrencyAmount * .1;
     $localCurrencyAmountExcludingTax = $localCurrencyAmount - $localCurrencyAmountExcludingTax;
     $localCurrencyName = "USD";
     $newAccountBalance = 10000;

     $topup = array('topUp' =>  array('localCurrencyAmount' => $localCurrencyAmount,
                    'salesTaxAmount'  => $salesTaxAmount,
                    'localCurrencyAmountExcludingTax' => $localCurrencyAmountExcludingTax,
                    'localCurrencyName' => $localCurrencyName,
                    'newAccountBalance' => $newAccountBalance));

#    $rsp2 = array('PurchaseRtr2Response' => array('orderResponse' => $topup));
    $rsp2 = array('orderResponse' => $topup);


    $delay= $dbO->delay;
    if ($delay>0) {

           errlog("WARN: Delaying resp for ", $delay." seconds");
           time_sleep_until(microtime(true) + $delay);
      }

    if ($dbO->abort == 'true') {

        errlog("WARN: Received abort instruction - exiting", "topUpReq");
        exit(0);
    }

    errlog("INFO: PurchaseRtr2 returningq: ", $rsp);
    return $rsp;

}

} //SoapHandler 


$msg= "DEBUG: HTTP_RAW_POST_DATA:".mix2str($HTTP_RAW_POST_DATA)."\n";
errlog('Main', $msg);

$server = new SoapServer($wsdl, array('soap_version' => SOAP_1_2));


$server->setClass("SoapHandler");

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
