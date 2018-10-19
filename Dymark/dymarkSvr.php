<?php
include '../Remittance/rmttObjBase.php';
include 'dymarkdata.php';
include '../Remittance/dbClass.php';
include '../QAlib/wslib.php';


$wsdl="http://devcall02.ixtelecom.com/wsdl/awsvrecarga.aspx.wsdl";
$fn=preg_replace('/\.php/', '', basename(__FILE__));

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

function Execute($q)
{
    $keyA=array('x_account_number' => $q->Telnum);

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

    errlog('Rsp: <Error>', $rsp."</Error>");
    return array('Error' => $rsp);

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
