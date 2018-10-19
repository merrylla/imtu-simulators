<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__));

include '../ezetop/ImtuTopUpClass.php';
include '../QAlib/wslib1.php';
/*include '../Remittance/rmttObjBase.php';
include '../Remittance/dbClass.php';
include '../QAlib/wslib.php'; 
include 'Smartdata.php'; */

$funcName="";

function sendResp($a, $code='COMPLETE')
{
   global $funcName;
   $jmsg=json_encode($a);
   $ht = new HttpResponse();
   $ht->status($code);
    $ht->setContentType('application/json');
    $ht->setData($jmsg);
 errlog("INFO: sendRsp sending resp back: ", $jmsg);
    $ht->send();
}

function randHexString($length) {
   $myHexString = '';
   $hexNumbs = '0123456789abcdef';
   for ($i=0; $i < $length; $i++) {
       $randNum= rand(0,15);
       $randHexChar = $hexNumbs[$randNum];
       $myHexString = $myHexString . $randHexChar;
   }
   errlog("randHexStringa returning", $myHexString);
   return $myHexString;
}
 
function topUp($p) {

     global $funcName;
     $funcName=__FUNCTION__;
     dump_errorlog("DEBUG: $funcName processing: ", $p);
     errlog("$funcName processing:: ", $p);
       /*$xA = array('msisdn' => $p->targetNum,
		   'userName' => $p->userName,
		   'product' => $p->prodCode); */
     $code = 'FAILED';
     if ($p['userName'] != '1194000541') {
       $ersp=array('status' => $code, 'message' => 'INVALID_PUBLICKEY', 
	'transactionCode' => NULL, 'balance' => NULL, 'price' => NULL,
	'change' => NULL, 'fulfillmentReferenceCode' => NULL);
       errlog("sending invalid public key ", $p['userName']);
       sendResp($ersp, 400);
       }
     elseif (strpos($p['product'], 'TestSMPH') != 0) {
       $ersp=array('status' => $code, 'message' => 'INVALID_PRODUCTCODE', 
	'transactionCode' => NULL, 'balance' => NULL, 'price' => NULL,
	'change' => NULL, 'fulfillmentReferenceCode' => NULL);
       errlog("sending invalid product code", $p['product']);
       sendResp($ersp, 400);
       }
     else {
       $key = array('app' => 'Smart', 'key' => $p['msisdn']);
       $xaD = array('product' => 'a0',
                    'msisdn' => 'a1',
		    'errorMsg' => 'a2');
       $ra = array ('product' => 'a0');
       $tpObj = new TopupObj($key, $xaD, $ra);
       $errorToProduce = $tpObj->xe['errorMsg'];
       $p = array_merge($p, array('errorMsg' => 'a2'));
       switch ($errorToProduce) {
          case 'Authentication' :
          case 'INVALID_HMACHASH' :
           $ersp=array('status' => 'FAILED', 'message' => 'INVALID_HMACHASH', 
	   'transactionCode' => NULL, 'balance' => NULL, 
           'price' => NULL, 'change' => NULL, 
	   'fulfillmentReferenceCode' => NULL); 
          errlog("sending error for this test", $ersp);
          sendResp($ersp, 200);
            break;
          case 'INVALID_PUBLICKEY' :
           $ersp=array('status' => 'FAILED', 'message' => 'INVALID_PUBLICKEY', 
	   'transactionCode' => NULL, 'balance' => NULL, 
           'price' => NULL, 'change' => NULL, 
	   'fulfillmentReferenceCode' => NULL); 
          errlog("sending error for this test", $ersp);
          sendResp($ersp, 200);
            break;
          case 'INSUFFICIENT_BALANCE' :
           $ersp=array('status' => 'FAILED', 'message' => 'INSUFFICIENT_BALANCE', 
	   'transactionCode' => NULL, 'balance' => NULL, 
           'price' => NULL, 'change' => NULL, 
	   'fulfillmentReferenceCode' => NULL); 
          errlog("sending error for this test", $ersp);
          sendResp($ersp, 200);
            break;
          case 'INVALID_PRODUCTCODE' :
           $ersp=array('status' => 'FAILED', 'message' => 'INVALID_PRODUCTCODE', 
	   'transactionCode' => NULL, 'balance' => NULL, 
           'price' => NULL, 'change' => NULL, 
	   'fulfillmentReferenceCode' => NULL); 
          errlog("sending error for this test", $ersp);
          sendResp($ersp, 200);
            break;
          case 'INVALID_REQUESTID' :
           $ersp=array('status' => 'FAILED', 'message' => 'INVALID_REQUESTID', 
	   'transactionCode' => NULL, 'balance' => NULL, 
           'price' => NULL, 'change' => NULL, 
	   'fulfillmentReferenceCode' => NULL); 
          errlog("sending error for this test", $ersp);
          sendResp($ersp, 200);
            break;
          case 'ERROR_FULFILLMENT' :
           $ersp=array('status' => 'FAILED', 'message' => 'ERROR_FULFILLMENT', 
	   'transactionCode' => NULL, 'balance' => NULL, 
           'price' => NULL, 'change' => NULL, 
	   'fulfillmentReferenceCode' => NULL); 
          errlog("sending error for this test", $ersp);
          sendResp($ersp, 200);
            break;
          case 'INVALID_TARGETNUMBER' :
           $ersp=array('status' => 'FAILED', 'message' => 'INVALID_TARGETNUMBER', 
	   'transactionCode' => NULL, 'balance' => NULL, 
           'price' => NULL, 'change' => NULL, 
	   'fulfillmentReferenceCode' => NULL); 
          errlog("sending error for this test", $ersp);
          sendResp($ersp, 200);
            break;
          case 'FILTER_EXCEPTION' :
           $ersp=array('status' => 'FAILED', 'message' => 'FILTER_EXCEPTION',
	   'transactionCode' => NULL, 'balance' => NULL, 
           'price' => NULL, 'change' => NULL, 
	   'fulfillmentReferenceCode' => NULL); 
          errlog("sending error for this test", $ersp);
          sendResp($ersp, 200);
            break;
          case 'TIMEOUT' :
           $ersp=array('status' => 'FAILED', 'message' => 'TIMEOUT', 
	   'transactionCode' => NULL, 'balance' => NULL, 
           'price' => NULL, 'change' => NULL, 
	   'fulfillmentReferenceCode' => NULL); 
          errlog("sending error for this test", $ersp);
          sendResp($ersp, 200);
            break;
          case 'ERROR_UNKNOWN' :
           $ersp=array('status' => 'FAILED', 'message' => 'ERROR_UNKNOWN', 
	   'transactionCode' => NULL, 'balance' => NULL, 
           'price' => NULL, 'change' => NULL, 
	   'fulfillmentReferenceCode' => NULL); 
          errlog("sending error for this test", $ersp);
          sendResp($ersp, 200);
            break;
          case 'None' :     
          case NULL :     
          $tpObj->xe['errorMsg'] = 'a2';
          errlog("about to validate $tpObj->xe", $tpObj->xe);
          errlog("about to validate p", $p);
          $tpObj->validateReqInput($p);
       errlog("validated", $xaD);
       if ($tpObj->isAbort() == 'false') {
	  $tid = rand();
          $rsp=array('status' => 'COMPLETE', 'message' => 'OK', 
	   'transactionCode' => rand(100,999), 'balance' => rand(0,100000), 
           'price' => 1200, 'change' => rand(0,10000), 
	   'fulfillmentReferenceCode' => 12345); //randHexString(16));
          $tpObj->buildRspData($ersp);
          errlog("sending after delay for this test", $rsp);
          sendResp($rsp, 200);
          }
       else { // nothing should be sent
          $ersp=array('status' => $code, 'message' => 'ERROR_FULFILLMENT', 
	   'transactionCode' => NULL, 'balance' => NULL, 'price' => NULL,
	   'change' => NULL, 'fulfillmentReferenceCode' => NULL);
          errlog("aborting for this test", $p['product']);
          //sendResp($ersp, 400);
	} 
           break;
       } // end switch
    } // end else
} // end of topup function

function parseAction($get, $p)
/* This function is used to make sure that all requests are directed
   appropriately to the correct routines. */
{
    errlog("SmartLoadSvrNew parseAction: usr info - get", $get);
    errlog("SmartLoadSvrNew parseAction: usr info - p", $p);

    $userName=$get['pubKey'];
    $reqID = $get['reqid'];
    $prodCode = $get['prod'];
    $targetNum = $get['target'];
    $hash = $get['hash'];
    $values = $reqID . $targetNum . $prodCode . $userName;
    $HMacHash = hash_hmac("sha1", $values, "761c7920f470038d4c8a619c79eddd62", false);
    errlog("HMacHash:  ", $HMacHash);
    if ($hash != $HMacHash) {
       $code='FAILED';
       $ersp=array('status' => $code, 'message' => 'INVALID_HMACHASH', 
	'transactionCode' => NULL, 'balance' => NULL, 'price' => NULL,
	'change' => NULL, 'fulfillmentReferenceCode' => NULL);
       errlog("Caught exception: $msg, code= ", $code);
       sendResp($ersp, 400);
     } else {
       $xA = array('msisdn' => $targetNum,
		   'userName' => $userName,
		   'product' => $prodCode);
       errlog("passed hash test calling topUp: ", $xA);
       topUp($xA);
     } 
}//parseAction


// The following code is the main driver that is run when a request is sent in

try {

$reqRev='';
$getRev='';

//   errlog("INFO: GLOBALS: ", $GLOBALS);

if (!empty($_GET)) {
   errlog("INFO: GET: ", $_GET);
   $getRev=$_GET;
}
if (!empty($HTTP_RAW_POST_DATA)) {
   $reqRev=$HTTP_RAW_POST_DATA;
   errlog("INFO: HTTP_RAW_POST_DATA", $HTTP_RAW_POST_DATA);

}

elseif (!empty($_POST)) {
   errlog("INFO: POST: ", $_POST);
   $reqRev=$_POST;
}

$postdata = file_get_contents("php://input");
   errlog("INFO: input: ", $postdata);

parseAction($getRev, $reqRev);

}//try


catch (Exception $e) {
   $msg=$e->getMessage();
   $code='FAILED';

   if ($code > 1000) $code=400;

   $ersp=array('status' => $code, 'message' => 'ERROR_UNKNOWN', 
	'transactionCode' => NULL, 'balance' => NULL, 'price' => NULL,
	'change' => NULL, 'fulfillmentReferenceCode' => NULL);
   errlog("Caught exception: $msg, code= ", $code);

   sendResp($ersp, 400);
}

?>
