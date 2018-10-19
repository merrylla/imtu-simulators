<?php
ini_set("log_errors", 1);
include '../Remittance/rmttObjBase.php';
include 'paqtigo_data.php';
include '../Remittance/dbClass.php';
include '../QAlib/wslib.php';

$fn=preg_replace('/\.php/', '', basename(__FILE__));
ini_set("error_log", "/logs/" . $fn . ".log");

function getDBdata($dbTable, $obj, $keyA)
{
$db=new DBClass();
$dbElements = array(); //use to store elements to be populated back to db
$dataBuf[]=array();    //use to store elements retrieved from db

//$key= array('x_account_number'=> $racct);

$db->dbRetrieveEleAll($dbTable, '*', $keyA, $obj, $dataBuf);

$fcount=count($dataBuf);
if ($fcount>0) {

   $dbA = $dataBuf[0];
   $dbA->remap();
dump_errorlog("INFO: getDBdata dumping: ", $dbA);
   return $dbA;
   }
return FALSE;
}//getDBdata

class SoapHandler {

function topUpService($p) {

     global $funcName;
     $funcName=__FUNCTION__;
//dump_errorlog("DEBUG: $funcName Received: ", $p);

$racct=$p->destinationMSISDN;
$amount=$p->amount;
$refId=$p->externalTransactionId;

$keyA= array('x_account_number'=> $racct);

$dbA=getDBdata('IMTU_DATA', 'PaqTopUpData', $keyA);
if ($dbA == FALSE) {
//error_log("DEBUG: topUpService - no data found from DB\n");
        $dbA = array('x_sourceMSISDN' => '~DONTCARE');
    }
//dump_errorlog("DEBUG: dbA Message: ", $dbA);

$retCode='0';
$retMsg='ERROR: user initiated';
$delay=0;
if (isset($dbA->r_ReturnCode)) $retCode=$dbA->r_ReturnCode;
if (isset($dbA->delay)) $delay=$dbA->delay;
if (preg_match('/T_NORSP/i',$retCode)) {
error_log("WARN: $funcName received instruction $retCode - exiting in $delay seconds");
time_sleep_until(time() + $delay);
exit(0);
}

if (isset($dbA->r_returnString)) $retMsg=$dbA->r_returnString;
if ($retCode != '0') throw new SoapFault($retCode, $retMsg); 

    $rsp= processRequest($p, $dbA);

    if (!isset($rsp['amountSource'])) $rsp['amountSource']= $amount;
    if (!isset($rsp['amountTarget'])) $rsp['amountTarget']= $amount;
    $rsp['comMovements']= NULL;
    if (!isset($rsp['returnString'])) $rsp['returnString']= "TopUp Exitoso!/nREF: F1DF3F5F_id";
    if (!isset($rsp['sourceCommissionCredit'])) $rsp['sourceCommissionCredit']= 0;
    if (!isset($rsp['sourceCommissionDebit'])) $rsp['sourceCommissionDebit']= 0;
    if (!isset($rsp['targetCommissionCredit'])) $rsp['targetCommissionCredit']= 0;
    if (!isset($rsp['targetCommissionDebit'])) $rsp['targetCommissionDebit']= 0;
    if (!isset($rsp['transactionId'])) $rsp['transactionId']= rand();

    $rspA=array('return' => $rsp);

time_sleep_until(time() + $delay);

    $parm= new SoapParam($rspA, "topUpServiceResponse");
dump_errorlog("DEBUG: Response Message: ", $parm);
    return $parm;
     

} //topUpService

function balanceInquire($p) {

    global $funcName;
    $funcName=__FUNCTION__;
//dump_errorlog("DEBUG: $funcName Received ", $p);

    $keyA=array('x_account_number' => $p->agentId);
    $balance = 100001;

    $dbA=getDBdata('IMTU_DATA', 'PaqBalData', $keyA);
    if ($dbA == FALSE) {
error_log("DEBUG: balanceInquire - no data found from DB\n");
        $dbA = array('x_agentId' => '~DONTCARE');
       }

$retCode='0';
$retMsg='ERROR: user initiated';
$delay=0;
if (isset($dbA->r_ReturnCode)) $retCode=$dbA->r_ReturnCode;
if (isset($dbA->delay)) $delay=$dbA->delay;
if (preg_match('/T_NORSP/i',$retCode)) {
error_log("WARN: $funcName received instruction $retCode - exiting in $delay seconds");
time_sleep_until(time() + $delay);
exit(0);
}
    $rsp = processRequest($p, $dbA);

    if (!isset($rsp['credit'])) $rsp['credit']=$balance;
    if (!isset($rsp['currencyId'])) $rsp['currencyId']=10;
    if (!isset($rsp['name'])) $rsp['name']='Dolares';
    if (!isset($rsp['symbol'])) $rsp['symbol']='$';
    if (!isset($rsp['dueDate'])) $rsp['dueDate']=date("Y-m-d\TH:i:s");
    if (!isset($rsp['returnString'])) $rsp['returnString']="Su saldo es: $$balance";

    $rspA = array('return' => array('credit' => $rsp['credit'],
                        'currency' => array('currencyId' => $rsp['currencyId'],
                                            'name' => $rsp['name'],
                                            'symbol' => $rsp['symbol']),
                                    'dueDate'  => $rsp['dueDate'],
                                    'returnString' => $rsp['returnString']));
 
time_sleep_until(time() + $delay);

    $parm= new SoapParam($rspA, "balanceInquireResponse");
dump_errorlog("INFO: $funcName sending resp: ", $parm);

    return $parm;

}// balanceInquire

function SampleFunctionCall($p) {

     global $funcName;
     $funcName=__FUNCTION__;
     $funcNameR=$funcName."Response";
     dump_errorlog("DEBUG: processing $funcName \n", $funcName);

    $tid = rand();

     $dbA = array('x_CorporateID' => '~DONTCARE',
               'x_UserName' => '~DONTCARE',
               'x_Password' => '~DONTCARE',
               'x_y' => '~DONTCARE',
               'r_z' => $tid);

    $rspA = processRequest($p, $dbA);
    $parm= new SoapParam($rspA, "$funcNameR");

    return $parm;
    
} // SampleFunctionCall

} //SoapHandler 

$fn=basename(__FILE__);
error_log("DEBUG: $fn- HTTP_RAW_POST_DATA: $HTTP_RAW_POST_DATA\n");
//$data= file_get_contents('php://input');
//error_log("INPUT stream: $data\n\n");

$server = new SoapServer("../wsdl/PaqTigo.wsdl", array('soap_version' => SOAP_1_1));

$server->setClass("SoapHandler");

//$allFunctions = $server->getFunctions();
//error_log( implode(",",$allFunctions));
//error_log("CALLING SOAP HANDLER\n");

$server->handle($HTTP_RAW_POST_DATA);

?>
