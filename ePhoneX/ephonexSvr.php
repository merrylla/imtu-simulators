<?php

include '../Remittance/rmttObjBase.php';
include '../Remittance/dbClass.php';
include '../QAlib/wslib.php';
include '../QAlib/soaplib.php';
include 'ephonexSvrData.php';

$fn=basename(__FILE__);


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

// p is string input of XML format
// return Array
function sInput3a($p)
{
$dom = new DOMDocument();
//$dom->preserveWhiteSpace = FALSE;
$dom->loadXML($p);
//$body=$doc->saveXML();

$params = $dom->getElementsByTagName('*');  //find all nodes

$eA=array();

foreach ($params as $node) {
    echo "node: ".$node->getNodePath()."\n";
    if ($node->tagName == 'PhoneUS' ) 
            $eA['PhoneUS']= $node->nodeValue; 
    if ($node->tagName == 'custRechargeRQ' )
            $eA['cmd']= 'custRechargeRQ';       
    if ($node->hasAttributes() ) {
      foreach ($node->attributes as $attr)
         {
              $eA[$attr->name] = $attr->value;
         }
     }
   }
//errlog("DEBUG: sInput3a", $eA);
return $eA;
}

function crResp($msg) {

$rp = &$msg;
$ht = new HttpResponse();
$ht->status(200);
$ht->setContentType('text/xml');
$ht->setData($rp);
errlog("INFO: crResp sending resp back: ", $rp);
$ht->send();

} //function crResp
 
function topUpReq($pA) 
{

    $keyA=array('x_account_number' => $pA['phoneNumber']);

    $dbO=getDBdata1('IMTU_DATA', 'TopupObj', $keyA);
    if ($dbO == FALSE) {
        error_log("INFO: TRANSFERTO topUpReq - not using test data from DB\n");
        $dbO = new TopupObj;
        $dbO->remap();
       }

    $rsp= $dbO->setAfterDb($pA);

    $delay=$dbO->delay;
    if ($delay>0) {

           errlog("WARN: Delaying resp for ", $delay." seconds");
           time_sleep_until(microtime(true) + $delay);
      }

    if ($dbO->abort == 'true') {
        
        errlog("WARN: Received abort instruction - exiting", "topUpReq");
        exit(0);
    }

    crResp($rsp);
}

function parseAction($p) {


$type = gettype($p);

if ($type != 'string') throw new Exception("Internal Error: parseAction - invalid input type <$type>", 901);

    $rspA = sInput3a($p);


$action=$rspA['cmd'];

switch ($action) {
   case "custRechargeRQ": topUpReq($rspA);
            break;
   case "getOpTrxData": pingReq($rspA);
            break;
   case "getBalance": getBal($rspA);
            break;
   case "key": key($rspA);
            break;

   default:
        throw new Exception("Invalid Action <$action>", 901);
}
} //parseAction

try {



if (!empty($HTTP_RAW_POST_DATA)) {
   $reqRev=$HTTP_RAW_POST_DATA;
   errlog("INFO: HTTP_RAW_POST_DATA", $HTTP_RAW_POST_DATA);
   throw new Exception("ERROR: Unexpected http Method used", 002);

}
if (!empty($_POST)) {
   errlog("DEBUG: POST: ", $_POST);
   $reqRev="<?xml version=".array_pop($_POST);
   throw new Exception("ERROR: Unexpected http Method used", 002);
}
if (!empty($_GET)) {
   errlog("DEBUG: GET: ", $_GET);
   $reqRev=array_pop($_GET);
}


if (isset($reqRev) ) parseAction($reqRev);
else
throw new Exception("ERROR: Unexpected http Method used", 002);

}
catch (SoapFault $ex) {
    throw new Exception($ex->faultstring, $ex->faultcode);
}
catch (Exception $e) {
   $msg=$e->getMessage();
   $code=$e->getCode();
   error_log("Caught exception: $msg, code=$code");
   $retMsg="0:$code:$msg";

   crResp(errRsp($code, $msg));
}


?>
