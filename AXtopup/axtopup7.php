<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));
ini_set("log_errors", 1);
ini_set("error_log", "/logs/$fn.log");
include '../Remittance/rmttObjBase.php';
include 'imtu_data.php';
include '../Remittance/dbClass.php';
#include '../Remittance/dbClassPayMaster.php';
include '../QAlib/wslib.php';

date_default_timezone_set('America/New_York');

//echo "dumping GLOBALS","<br/>"; 
//var_dump($GLOBALS);


function handleStatus($xrq) {

$ucDateTime=$xrq->getElementsByTagName('UtcDateTime')->item(0)->nodeValue;
$gaid=$xrq->getElementsByTagName('GAID')->item(0)->nodeValue;
$refid=$xrq->getElementsByTagName('ReferenceID')->item(0)->nodeValue;
$cksum=$xrq->getElementsByTagName('Checksum')->item(0)->nodeValue;

$qryA=array('Product' => '313', 'UtcDateTime' => $ucDateTime, 'Checksum' =>$cksum);

$dataBuf[]=array();    //use to store elements retrieved from db
$key= array('r_ReferenceID'=> $refid);
$db=new DBClass();
$db->dbRetrieveEleAll('IMTU_DATA', '*', $key, 'axStatusCmd', $dataBuf);

$fcount=count($dataBuf);
   error_log("DEBUG: handleStatus buffer count $fcount.\n"); 
if ($fcount == 0) throw new Exception("Transaction ref-$refid- NOT FOUND", 13);

$rsp = $dataBuf[0];

$rspA=processRequest($qryA, $rsp);

$retCode= '0';
$msg = "NO ReturnMessage was set";
//processRequest has stripped the "r_" prefix for r_r_ReturnCode
if (isset($rspA['ReturnCode'])) $retCode = $rspA['ReturnCode'];
if (isset($rspA['ReturnMessage'])) $msg = $rspA['ReturnMessage'];

$code = array();
if (preg_match('/S_/i',$retCode)&&preg_match('/-{0,1}[0-9]{1,4}/', $retCode, $code)) {
          $retCode=$code[0];
          $rspA['ReturnCode']=$code[0];
    } // Status_code handling

if ($retCode != '0' ) 
        throw new Exception($msg, $retCode);

crResp('AX', $rspA);

}// handleStatus

function handleBal($xrq) {

$ucDateTime=$xrq->getElementsByTagName('UtcDateTime')->item(0)->nodeValue;
$gaid=$xrq->getElementsByTagName('GAID')->item(0)->nodeValue;
$refid=$xrq->getElementsByTagName('ReferenceID')->item(0)->nodeValue;
$cksum=$xrq->getElementsByTagName('Checksum')->item(0)->nodeValue;

$qryA=array('Product' => '23', 'UtcDateTime' => $ucDateTime, 'Checksum' =>$cksum);

$db=new DBClass();
$dataBuf[]=array();    //use to store elements retrieved from db
$key= array('r_ReferenceID'=> $refid);
$db->dbRetrieveEleAll('IMTU_DATA', '*', $key, 'axBalCmd', $dataBuf);
$fcount=count($dataBuf);
if ($fcount == 0) { 
   //throw new Exception("Transaction ref-$refid- NOT FOUND", 13);
    $rsp = new axBalCmd;
   }
else
$rsp = $dataBuf[0];

/* 5/5/11 the code below is subjected to further understanding of the requirement
$rsp = array('r_ReferenceID' => $refid,
             'r_ReturnCode' => 0,
             'r_ReturnMessage' => 'Success',
             'r_TransactionID' => rand(),
             'r_CurrencyType' => 'USD',
             'r_BusinessAccount' => '1000',
             'r_FeeAccount' => '199');
*/

$rspA=processRequest($qryA, $rsp);

//processRequest has stripped the "r_" prefix for r_r_ReturnCode
if (!isset($rspA['ReturnCode'])) $rspA['ReturnCode']=0;
//if (!isset($rspA['ReturnMessage'])) $rspA['ReturnMessage']='Success';

if (isset($rspA['ReturnCode'])) $retCode = $rspA['ReturnCode'];

if ($retCode !=0 ) throw new Exception("__FUNCTION__ has been set to return fail", $retCode);

if (!isset($rspA['CurrencyType'])) $rspA['CurrencyType'] = "USD";
if (!isset($rspA['BusinessAccount'])) $rspA['BusinessAccount'] = 1000;
if (!isset($rspA['FeeAccount'])) $rspA['FeeAccount'] = 50;
if (!isset($rspA['ReferenceID'])) $rspA['r_ReferenceID'] = $refid;

crResp('AX', $rspA);

}//handleBal

function handleTopUp($xrq) {

$product=$xrq->getElementsByTagName('Product')->item(0)->nodeValue;
$amount=$xrq->getElementsByTagName('Amount')->item(0)->nodeValue;
$racct=$xrq->getElementsByTagName('RecipientAccountNumber')->item(0)->nodeValue;
$refid=$xrq->getElementsByTagName('ReferenceID')->item(0)->nodeValue;
$action=$xrq->getElementsByTagName('Action')->item(0)->nodeValue;
$ucDateTime=$xrq->getElementsByTagName('UtcDateTime')->item(0)->nodeValue;
$gaid=$xrq->getElementsByTagName('GAID')->item(0)->nodeValue;
$curType=$xrq->getElementsByTagName('CurrencyType')->item(0)->nodeValue; 
$cksum=$xrq->getElementsByTagName('Checksum')->item(0)->nodeValue;

$retCode= '0';
$retMsg= 'OK';

if ($action==4 && $amount < 1); //promotional topup
else
if ($action==3 && $amount >= 1);//normal topup
else {
   throw new Exception("Invalid action $action Amount $amount combo", 10); 
};

$db=new DBClass();
$dbElements = array(); //use to store elements to be populated back to db
$dataBuf[]=array();    //use to store elements retrieved from db

$tid=rand();
$xRate=1;
$localCE = 1.8;
$localC = 'GHC';
$mno = 'Vodafone';

$key= array('x_account_number'=> $racct);

$db->dbRetrieveEleAll('IMTU_DATA', '*', $key, 'axTopUp', $dataBuf);

$fcount=count($dataBuf);
if ($fcount>0) {

$imtu = $dataBuf[0];
dump_errorlog("INFO: dump imtu ", $imtu);

if (isset($imtu->x_Product)) 
   if($imtu->x_Product != $product) throw new Exception("Got unexpect unexpected Product value", 7); 

if (isset($imtu->x_Amount))
   if($imtu->x_Amount!= $amount) throw new Exception("Got unexpect unexpected Amount value", 10);    

if (isset($imtu->x_Action))
   if($imtu->x_Action != $action) throw new Exception("Got unexpect unexpected Action value", 10);    

if (isset($imtu->x_CurrencyType))
   if($imtu->x_CurrencyType != $curType) throw new Exception("Got unexpect unexpected CurrencyType value", 10);    

if (isset($imtu->r_ReturnCode)) $retCode = $imtu->r_ReturnCode;
if (isset($imtu->r_ReturnMessage)) $retMsg = $imtu->r_ReturnMessage;
if (isset($imtu->r_ExchangeRate)) $xRate = $imtu->r_ExchangeRate;
if (isset($imtu->r_LocalCurrencyEquivalent)) $localCE = $imtu->r_LocalCurrencyEquivalent;
if (isset($imtu->r_LocalCurrency)) $localC = $imtu->r_LocalCurrency;
if (isset($imtu->r_MNO)) $mno = $imtu->r_MNO;

}//process db instructions 

$x='AX';
$y=array ( "ReturnCode" => $retCode, 
            "ReferenceID" => $refid, 
            "ReturnMessage" => $retMsg, 
            "TransactionID" => $tid, 
            "ExchangeRate" => $xRate, 
            "LocalCurrencyEquivalent" => $localCE,
            "LocalCurrency" => $localC,
            "MNO" => $mno
             );

dump_errorlog("INFO: dump retCode $retCode", $y);

if ($retCode == '0' || preg_match('/T_INVLD/i',$retCode) || preg_match('/T_NORSP/i',$retCode)) {
$dbElements = array(); //use to store elements to be populated back to db

$dbElements['r_ReferenceID'] = $refid; 
$dbElements['r_TransactionID'] = $tid; 
$dbElements['state'] = 'Paid'; 
$dbElements['r_CurrencyType'] = $curType; 
$dbElements['r_TransactionAmount'] = $amount; 
$dbElements['r_RecipientPhoneNumber'] = $racct; 
$dbElements['r_TransactionDataTime'] = date("Y-m-d\TH:i:s"); 

$db->dbUpdateEle('IMTU_DATA', $key, $dbElements);
}

if (preg_match('/T_NORSP/i',$retCode)) {
error_log("WARN: handleTopUp received instruction $retCode - exiting");
//$db->__destruct();
exit(0);
}
else
if (preg_match('/T_INVLD/i',$retCode)) {
   unset($y['ReturnCode']);
dump_errorlog("INFO: dump2 retCode $retCode", $y);
}

$delay=0;
if (isset($imtu->delay )) $delay = $imtu->delay; 
if ($delay>0)
{
   error_log("DEBUG: __FUNCTION__ introducing delay of <$delay> seconds before sending response back.\n"); 
   time_sleep_until(time() + $delay);
};

crResp($x, $y);

} //handleTopUp

function handleAX($reqRev) {


$xrq = new DOMDocument;
$xrq->loadXML($reqRev, LIBXML_NOBLANKS);

$incomingMsg=$xrq->saveXML();

error_log("DEBUG: Rcv Query: $incomingMsg");

//$prodCode=$xrq->getElementsByTagName('Product')->item(0)->nodeValue;
if ($prod=$xrq->getElementsByTagName('Product')->item(0)) error_log("Product Code: <$prod->nodeValue>");
   else
    throw new Exception("Missing Product Field", 8);

switch ($prod->nodeValue) {
   case 26: handleTopUp($xrq);
            break;
   case 23: handleBal($xrq);
            break;
   case 313: handleStatus($xrq);
            break;

   default: 
        throw new Exception("Invalid Product <$prod>", 7); 
}
} //handleAX

function crResp($rtName, $data) {

$doc=new DOMDocument('1.0', 'UTF-8');

$newE= $doc->createElement($rtName);
$rootNode= $doc->appendChild($newE);

foreach ($data as $key => $value) {
$newE=$doc->createElement($key, $value);
$rootNode->appendChild($newE);
}

$rp= $doc->saveXML($doc, LIBXML_NOEMPTYTAG);

$ht = new HttpResponse();
$ht->status(200);
$ht->setContentType('text/xml');
$ht->setData($rp);
$ht->send();

error_log("DEBUG: success resp sent: $rp");
} //crResp

try {

if (!empty($HTTP_RAW_POST_DATA)) {
   $reqRev=$HTTP_RAW_POST_DATA;
}
else
if (!empty($_POST)) {
   $reqRev=$_POST;
} 
else
if (!empty($_GET)) {
   $reqRev=$_GET;
}
else {
   throw new Exception("Found empty request", 8); 
}

if ( isset($_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT'])) {
$url="http://".$_SERVER['REMOTE_ADDR'].":".$_SERVER['REMOTE_PORT'];
}
else {
   throw new Exception("NO Remote Address", 8);
}

//echo "<br/><br/>Received query: <br/>";
//var_dump($reqRev);
//echo "<br/>From: ", $url, "<br/>";

handleAX($reqRev);

}
catch (SoapFault $ex) {
    throw new Exception($ex->faultstring, $ex->faultcode); 
}
catch (Exception $e) {
   $msg=$e->getMessage();
   $code=$e->getCode();
   error_log("Caught exception: $msg, code=$code");
   $errdata=array ( "ReturnCode" => $code, "ReturnMessage" => $msg);
   crResp('AX', $errdata);
}

?>
