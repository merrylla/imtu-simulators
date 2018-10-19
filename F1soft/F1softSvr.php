<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));

include '../QAlib/ImtuTopUpClass.php';
include '../QAlib/wslib2.php';

function mkBillPayResponse($rqid, $status='0', $msisdn='9803181254', $amount='12.34', $msg='Transaction Successful', $atid='121215', $vtid='1009220')
{
  $rsp="<BillPayResponse xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'
               xmlns:xsd='http://www.w3.org/2001/XMLSchema'>
               <RequestId>$rqid</RequestId>
               <AgentTransId>$atid</AgentTransId>
               <VendorTransId>$vtid</VendorTransId>
               <MobileNo>$msisdn</MobileNo>
               <Amount>$amount</Amount>
               <ResultCode>$status</ResultCode>
               <ResultDescription>$msg</ResultDescription>
               </BillPayResponse>";

   return $rsp;
}

function mkErrResponse($rqid, $status, $msg, $msisdn='', $amount='', $atid='', $vtid='')
{
  return mkBillPayResponse($rqid, $status, $msisdn, $amount, $msg, $atid, $vtid);
}

function ckPasswd($pw, $md5pw)
{
  $x=md5($pw);

  if ($x === $md5pw) return '0';  

  return '403';

}

function dotopup($p)
{
   $funcName=__FUNCTION__;
    errlog("INFO: $funcName - ", $p);

    $requestid=$p['requestid'];
    $clientusername=$p['clientusername'];
    $password=$p['password'];
    $agenttransid=$p['agenttransid'];
    $mobileno=$p['mobileno'];
    $amount=$p['amount'];

     $xA = array('password' => 'a0', 'agenttransid' => 'a1', 'amount' => 'a2');
     $rA = array('status' => 'a3', 'msg' => 'a4', 'status2' => 'a5');

     $key = array('app' => 'F1soft' ,'key' => $mobileno);
     $tpO= new TopupObj1($key);
     $tpO->TopupSetup($xA, $rA);

     if (isset($tpO->re['password']) ) {
         $r=ckPasswd($tpO->re['password'], $password); 
         if ($r != '0') 
           return mkErrResponse($requestid, $r, 'AUTHORIZATION_FAILED'); 
     }

     $tpO->validateReqInput($p);

     $tid= rand(100000, 200000);

     if (isset($tpO->re['status2'])) {
         errlog("DEBUG: topUp saving context status", $tpO->re['status2']);
         $context = array('requestid' => $requestid, 
                           'mobileno' => $mobileno, 
                           'amount' => $amount, 
                           'agenttransid' => $agenttransid,
                           'msg' => $tpO->re['msg'],
                           'vtid' => $tid, 
                            'status' => $tpO->re['status2']); 

         $jdata=json_encode($context);
         $tpO->saveContext($requestid, $jdata);
     }
     $nA=array();
     $tpO->buildRspData($nA);

     $status='0';
     $msg='ok';
     if (isset($tpO->re['status'])) {
        $status=$tpO->re['status'];
        $msg==$tpO->re['msg'];  

// CFW R16.20 - now must include confirmation id
//	if ($status != '0') $tid='';
     }

     $rsp=mkBillPayResponse($requestid, $status, $mobileno, $amount, $msg, $agenttransid, $tid);
     crResp($rsp);
}

function ckstatus($p)
{
   $funcName=__FUNCTION__;
    //errlog("INFO: $funcName - ", $p);

    $requestid=$p['requestid'];
    $clientusername=$p['clientusername'];
    $password=$p['password'];

    $key = array('app' => 'F1soft' ,'key' => $requestid);
    $tpO= new TopupObj1($key);
    $xt= $tpO->retrieveContext();


     if ($xt !='') {

        $ct= (array) json_decode($xt);

        $status=$ct['status'];
        $amount=$ct['amount'];
        $mobileno=$ct['mobileno'];
        $atid=$ct['agenttransid'];
        $tid=$ct['vtid'];
        $msg=$ct['msg'];

        $rsp=mkBillPayResponse($requestid, $status, $mobileno, $amount, $atid, $tid);
     }
     else {

        $rsp=mkErrResponse($requestid, '98', 'Transaction Not Found');
     }

    crResp($rsp);
}//ckstatus

function parseAction($get, $p)
{
//    errlog("parseAction: usr info - ", $get);

    $cmd=$get['cmd'];

    switch ($cmd) {

        case 'paypointpay':
           dotopup($get);
           break;
        case 'ncellbillpay':
// R17.0.3 now uses GET
//          dotopup($p);
	   dotopup($get);
           break;
        case 'ncellbillstatus':
           ckstatus($p);
           break;

        default:
           throw new Exception("Invalid URL $cmd", 400);
    }//switch
}//parseAction

try {

$reqRev='';
$getRev='';

   errlog("INFO: GLOBALS: ", $GLOBALS);

if (!empty($_GET)) {
   errlog("INFO: GET: ", $_GET);
   $getRev=$_GET;
}
if (!empty($HTTP_RAW_POST_DATA)) {
   errlog("ERROR: unexpected HTTP method used", $HTTP_RAW_POST_DATA);
   //throw exception code

}
elseif (!empty($_POST)) {
   errlog("INFO: POST: ", $_POST);
   $reqRev=$_POST;
}


parseAction($getRev, $reqRev);


}//try
catch (SoapFault $ex) {
    throw new Exception($ex->faultstring, $ex->faultcode);
}
catch (Exception $e) {
   $msg=$e->getMessage();
   $code=$e->getCode();
   error_log("Caught exception: $msg, code=$code");
   $retMsg="0:$code:$msg";

   crResp($retMsg);
}

?>



