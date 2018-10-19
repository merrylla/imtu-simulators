<?php
// 9/14/2011 code completed for DB/customized return handling but not tested yet
//
include '../Remittance/rmttObjBase.php';
include 'imtuGlobe_data.php';
include '../Remittance/dbClass.php';
include '../QAlib/wslib.php';

$rspMsgName="";

class RespMsg {
      public $session_result = 0;
      public $sessionResultCode = "sessionResultCode ok";
      public $session = 0;

     function setRetArg(&$retArg) {
       
        foreach ($retArg as $tag=>$value) {
                    $this->$tag = $value;
               }
     }
}

class SoapHandler {

function resp($retA) {
  //TBD;
}

function createsessioncmd($parameters) {

     global $rspMsgName;
     $rspA = array('session_result' => 0, 'session' => 0);
     $rspMsgName='createsessioncmdResponse';
     $sid = createSession();
     $rspA['session'] = $sid;

     error_log("DEBUG: processing createsessioncmd with open session id = $sid");
//     while (sleep(1));
//     time_sleep_until(time() + 2.0);
//     error_log("DEBUG: the __FUNCTION__ has slept for a second. \n");

     $rspM = new RespMsg;
     $rspM->setRetArg($rspA);
     
     return $rspM;
}

function logincmd ($parameters) {
     global $rspMsgName;
     $rspMsgName='logincmdResponse';

      $sid = $parameters->session;
      $u   = $parameters->user;
      $p   = $parameters->password;

      $ptype = gettype($parameters);
//error_log("DEBUG: logincmd call with sid= $sid,  parameters - $ptype - u= $u, p= $p");

      $params = array ('user' => $u, 'password' =>  $p);
      sessionSetParams($sid, $params);

      $rspA = array ('sessionResultCode' => 0, 'loginResultCode' => 0);

     $rspM = new RespMsg;
     $rspM->setRetArg($rspA);
     return $rspM;
}//logincmd

function topupcmd($p) 
{
    global $funcName;
    $funcName=__FUNCTION__;
dump_errorlog("DEBUG: $funcName Received ", $p);

    $q= &$p;

    $keyA=array('x_account_number' => $q->target);

    $dbA=getDBdata1('IMTU_DATA', 'IMTUGlobeTopUpCmd', $keyA);
    if ($dbA == FALSE) {
error_log("DEBUG: $funcName - no data found from DB\n");
        $dbA = array('x_target' => '~DONTCARE');
       }

$tid=rand();
$retCode='0';
$sessRetCode='0';
$delay=0;
if (isset($dbA->r_topupResultCode)) $retCode=$dbA->r_topupResultCode;
if (isset($dbA->r_sessionResultCode)) $sessRetCode=$dbA->r_sessionResultCode;
if (isset($dbA->delay)) $delay=$dbA->delay;
if (preg_match('/T_NORSP/i',$retCode)) {
error_log("WARN: $funcName received instruction $retCode - exiting in $delay seconds");
time_sleep_until(time() + $delay);
exit(0);
}
//dump_errorlog("DEBUG: inq ", $q);
//dump_errorlog("DEBUG: dbA ", $dbA);
    $rsp = processRequest($q, $dbA);

      if (!isset($rsp['sessionResultCode'])) $rsp['sessionResultCode']= 0;
      if (!isset($rsp['topupResultCode'])) $rsp['topupResultCode']= 0;

      $rsp['topupTransactionCode']= $tid;

dump_errorlog("DEBUG: rep ", $rsp);
//time_sleep_until(time() + $delay);
if ($delay>0) time_sleep_until(time() + $delay);

    $parm= new SoapParam($rsp, "topupcmdResponse");
dump_errorlog("INFO: imtuGlobe - topupcmd - dumping: ", $parm);

    return $parm;

} // topupcmd


function _topupcmd($parameters) {

    global $rspMsgName;
    $rspMsgName='topupcmdResponse';

    $sid = $parameters->session;
    $loginParam=sessionValidation($sid);

    $tid=(string) rand();

    $rsp = array ('r_sessionResultCode' => 0, 'r_topupResultCode' => 0, 'r_topupTransactionCode' => $tid);

    $rspA=processRequest(array(), $rsp);

    $parm= new SoapParam($rspA, "topupcmdResponse");
dump_errorlog("INFO: imtuGlobe - topupcmd - dumping: ", $parm);
    return $parm;

/* old style
     $rspM = new RespMsg;
     $rspM->setRetArg($rspA);
dump_errorlog("INFO: imtuGlobe - topupcmd - dumping: ", $rspM);
     return $rspM;
*/
}

function Anycmd($parameters) {

    global $rspMsgName;
    $rspMsgName='accountbalance_querycmdResponse';

    $sid = $parameters->session;
    $loginParam=sessionValidation($sid);

    $req = array('session' => $sid);
//    $rsp = array ('x_session' => $sid, 'r_sessionResultCode' => 3, 'r_balanceResultCode' => 4, 'r_balanceCode' => 1234);
    $rsp = array ('r_sessionResultCode' => 3, 'r_balanceResultCode' => 4, 'r_balanceCode' => 1234);

    $rspA=processRequest(array(), $rsp);

     $rspM = new RespMsg;
     $rspM->setRetArg($rspA);

    return $rspM;
}

function accountbalance_querycmd($parameters) {

    global $rspMsgName;
     $rspMsgName='accountbalance_querycmdResponse';

    $rspA = array ('sessionResultCode' => 0, 'balanceResultCode' => 0, 'balanceCode' => 1234.56);

    $sid = $parameters->session;

$ptype = gettype($parameters);
error_log("DEBUG: accountbalance_querycmd call with sid= $sid,  parameters - $ptype - value parameters");

    $loginParam=sessionValidation($sid);

     $rspM = new RespMsg;
     $rspM->setRetArg($rspA);

    return $rspM;
}


} //SoapHandler


error_log("INFO: mtuGlobe.php Dumping HTTP_RAW_POST_DATA: $HTTP_RAW_POST_DATA\n");
//$data= file_get_contents('php://input');
//error_log("INPUT stream: $data\n\n");

try {

$server = new SoapServer("../wsdl/IMTU-Globe.wsdl");
//$server = new SoapServer(null, array('uri' => "urn:AnyThingHere", 
//$server = new SoapServer(null, array('uri' => "http://devcall02.ixtelecom.com/wsdl/IMTU-Globe.wsdl",
//$server = new SoapServer(null, array('uri' => "http://devcall02.ixtelecom.com/wsdl/GlobeGCash.wsdl")); 
//$server = new SoapServer("../wsdl/GlobeGCash.wsdl");
//                    'style'    => SOAP_DOCUMENT,
//                    'style'    => SOAP_RPC,
//                    'use'      => SOAP_LITERAL,
//                    'use'      => SOAP_ENCODED
//                    ));


$server->setClass("SoapHandler");

//$allFunctions = $server->getFunctions();
//error_log( implode(",",$allFunctions));

$server->handle($HTTP_RAW_POST_DATA);

}
catch (SoapFault $ex) {
     global $rspMsgName;
     $retM = array($rspMsgName => array ('sessionResultCode' => 9999, 'loginResultCode' => 9998));

error_log("Caught SoapException: $ex->faultcode, $ex->faultstring, $ex->faultactor, $ex->detail, $type, $type failed \n");
              $rmttRspMsg->SetError($ex->faultcode, $ex->faultstring);

      return $retM;
}
?>
