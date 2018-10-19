<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));
$sessKey='IDT-TRANGLO-SESSION';

include '../Remittance/rmttObjBase.php';
include 'Tranglo_data.php';
include '../Remittance/dbClass.php';
include '../QAlib/wslib1.php';

$wsdl="http://devcall02.ixtelecom.com/wsdl/Tranglo_EPin_Reload.wsdl";

class SoapHandler {


function Reload($q)
{
//    errlog("INFO: Reload\n", $q);

    $keyA=array('x_account_number' => $q->destNo);

    $dbO=getDBdata1('IMTU_DATA', 'TopupObj', $keyA);
    if ($dbO == FALSE) {
        errlog("INFO: TRANSFERTO topUpReq - not using test data from DB\n", $keyA);
        $dbO = new TopupObj;
        $dbO->remap();
       }

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

//errlog('Reload', $rsp);
   return $rsp;
}//Reload

function Request_Reload($p)
{
   $rsp=$this->Reload($p);

    $status=$rsp['Status'];
     
    if ($status == '000') $retMsg=Request_ReloadRsp($status);
    else
       $retMsg=errRsp($status);

    errlog('Request_Reload', $rspMsg);
    return array('Request_ReloadResult' => $rspMsg);

}//Request_Reload

function Request_ReloadAmount($p)
{
    $rsp=$this->Reload($p);

    if (!array_key_exists('ProductPrice', $rsp)) $rsp['ProductPrice']=$p->deno;
    if (!array_key_exists('AmountAfterTax', $rsp)) $rsp['AmountAfterTax']=$p->deno * .9;

    $string=Request_ReloadAmountRsp($rsp);
     
    //XSD_ANYXML will create <SOAP-ENV:Body>  any xml msg </SOAP-ENV:Body>
    $anyxml=new SOAPVar($string, XSD_ANYXML);

    errlog('Request_ReloadAmountRsp:', $anyxml);
    return $anyxml; 

} //Request_ReloadAmount

function Transaction_Inquiry($p)
{
    $rcA=sessRetrive($p->transID);
    $count=count($rcA); 

    if ($count==0) $rc='998'; 
    if ($count==1) $rc=$rcA[0];
    if ($count>1) {
       $rc=array_shift($rcA);
       sessStore($p->transID, $rcA);
    }

    errlog('Transaction_InquiryResponse:', $rc);
    return array('Transaction_InquiryResult' => $rc);
}


function Product_Price_Inquiry($p)
{
    $retMsg=Product_Price_InquiryResponse('000', 'USD', '10000');
    return array('Product_Price_InquiryResult' => $retMsg);
}

function EWallet_Inquiry($p)
{
    $retMsg=EWallet_InquiryResponse('000', 'USD', '10000');
    return array('x' => $retMsg);

}

function Ping($p)
{
   return 1;
}

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
