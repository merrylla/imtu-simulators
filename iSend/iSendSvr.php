<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__));
ini_set("log_errors", 1);
ini_set("error_log", "/logs/iSendSvr.log");
include '../Remittance/rmttObjBase.php';
include 'isend_data.php';
include '../Remittance/dbClass.php';
include '../QAlib/wslib.php';

class SoapHandler {

function HelloWorld($p) 
{
//time_sleep_until(time() + $delay);

    $parm= new SoapParam("Ping ok", "topUpServiceResponse"); 
}

function PostSimplePayment($p) 
{
    global $funcName;
    $funcName=__FUNCTION__;
//dump_errorlog("DEBUG: $funcName Received ", $p);

    $q= &$p->req;

    $keyA=array('x_account_number' => $q->Account);

    $dbA=getDBdata1('IMTU_DATA', 'ISpostSimplePayment', $keyA);
    if ($dbA == FALSE) {
error_log("DEBUG: $funcName - no data found from DB\n");
        $dbA = array('x_Account' => '~DONTCARE');
       }


$tid=rand();
$retCode='Processed';
$retMsg='INFO: user initiated';
$delay=0;
if (isset($dbA->r_ReturnCode)) $retCode=$dbA->r_ReturnCode;
if (isset($dbA->delay)) $delay=$dbA->delay;
if (preg_match('/T_NORSP/i',$retCode)) {
error_log("WARN: $funcName received instruction $retCode - exiting in $delay seconds");
if ($delay > 0) time_sleep_until(time() + $delay);
exit(0);
}
dump_errorlog("DEBUG: inq ", $q);
dump_errorlog("DEBUG: dbA ", $dbA);
    $rsp = processRequest($q, $dbA);

$errCode='0';
    
    if (isset($rsp['ErrorCodes'])) $errCode=$rsp['ErrorCodes'];

    if ($errCode == '0') {

      $rsp['ErrorCodes']= '';
      if (!isset($rsp['Status'])) $rsp['Status']= 'Processed';
      if (!isset($rsp['ReceiptText'])) $rsp['ReceiptText']='Thank you for making a payment.';

      if ($q->BillerID == '265' || $q->BillerID == '342' || $q->BillerID == '682') 
      {
            $rsp['ReceiptText']= file_get_contents('./elektraGiftCardInstructions');
	    $rsp['ReceiptText']= str_replace('5045075894599999999',mt_rand(10000000,99999999),$rsp['ReceiptText']);
      }

      if (!isset($rsp['ConfirmationRequired'])) $rsp['ConfirmationRequired']='false';

      if (!isset($rsp['ExternalTransactionID'])) $rsp['ExternalTransactionID']= $q->ExternalTransactionID;
      if (!isset($rsp['TransactionID'])) $rsp['TransactionID']= $tid;
      if (!isset($rsp['TotalAgentFee'])) $rsp['TotalAgentFee']= 0;
      if (!isset($rsp['TotalCustomerFee'])) $rsp['TotalCustomerFee']=0;
      if (!isset($rsp['EntryTimeStamp'])) $rsp['EntryTimeStamp']=date("Y-m-d\TH:i:s");
      if (!isset($rsp['TotalPaymentAmount'])) $rsp['TotalPaymentAmount']= $q->Amount;
      if (!isset($rsp['ExchangeRate'])) $rsp['ExchangeRate']= '1.000';
      if (!isset($rsp['CurrencyCode'])) $rsp['CurrencyCode']='840';
    }
    else {

      unset($rsp['ErrorCodes']);
      
      if (!isset($rsp['Status'])) $rsp['Status']= 'NEW';
      if (isset($rsp['ReceiptText'])) unset($rsp['ReceiptText']);
      $rsp['ConfirmationRequired']='false';
      $rsp['TransactionID']= 0;
      if (isset($rsp['ExternalTransactionID'])) unset($rsp['ExternalTransactionID']);
      $rsp['TotalAgentFee']= 0;
      $rsp['TotalPaymentAmount']=0;
      $rsp['ExchangeRate']= 0;
      $rsp['EntryTimeStamp']=date("Y-m-d\TH:i:s");

      preg_match_all('/\d+/i',$errCode, $errCodeA);
      $errCodeR= array('ErrorCodes' => array('int' => $errCodeA[0]));

      $rsp = array_merge($errCodeR, $rsp);
    }

dump_errorlog("DEBUG: rep ", $rsp);
if ($delay > 0) time_sleep_until(time() + $delay);

    $parm= new SoapParam(array('PostSimplePaymentResult' => $rsp), "PostSimplePaymentResponse");
dump_errorlog("INFO: $funcName sending resp: ", $parm);

    return $parm;

}// PostSimplePayment


function ConfirmPayment($p) {

     global $funcName;
     $funcName=__FUNCTION__;
     $funcNameR=$funcName."Response";
//     dump_errorlog("DEBUG: processing $funcName \n", $funcName);

     $q=&$p->req;

     $dbA = array(
               'x_AgentID' => '~DONTCARE',
               'x_AgentPassword' => '~DONTCARE',
               'x_TransactionID' => '~DONTCARE',
               'x_ExternalTransactionID' => '~DONTCARE',
               'r_Status' => 'Processed',
               'r_ReceiptText' => 'ok'
               );

    $rsp = processRequest($q, $dbA);

    if (!isset($rsp['Status'])) $rsp['Status']= 'Processed';
    if (!isset($rsp['ReceiptText'])) $rsp['ReceiptText']='ok';
    if (!isset($rsp['ConfirmationRequired'])) $rsp['ConfirmationRequired']='false';

    if (!isset($rsp['ExternalTransactionID'])) $rsp['ExternalTransactionID']= $q->ExternalTransactionID;
    if (!isset($rsp['TransactionID'])) $rsp['TransactionID']= $q->TransactionID;
    if (!isset($rsp['TotalAgentFee'])) $rsp['TotalAgentFee']= 1;
    if (!isset($rsp['TotalCustomerFee'])) $rsp['TotalCustomerFee']=2;
    if (!isset($rsp['EntryTimeStamp'])) $rsp['EntryTimeStamp']=date("Y-m-d\TH:i:s");
    if (!isset($rsp['TotalPaymentAmount'])) $rsp['TotalPaymentAmount']= 0;
    if (!isset($rsp['ExchangeRate'])) $rsp['ExchangeRate']= '1.45';
    if (!isset($rsp['CurrencyCode'])) $rsp['CurrencyCode']='EUR';

    $parm= new SoapParam(array('ConfirmPaymentResult' =>$rsp), "$funcNameR");

    return $parm;
    
} // ConfirmPayment

function CancelSimplePayment($p) {

     global $funcName;
     $funcName=__FUNCTION__;
     $funcNameR=$funcName."Response";
     dump_errorlog("DEBUG: processing $funcName \n", $funcName);

     $q=&$p->req;

     $dbA = array(
               'x_AgentID' => '~DONTCARE',
               'x_AgentPassword' => '~DONTCARE',
               'x_ExternalTransactionID' => '~DONTCARE',
               'r_Status' => 'Cancelled',
               'r_ReceiptText' => 'ok'
               );

    $rsp = processRequest($q, $dbA);

    $rsp['ErrorCodes']= '';
    $rsp['ExternalTransactionID']= $q->ExternalTransactionID;
    $rsp['PaymentStatus']= 'Cancelled';


    $parm= new SoapParam(array('CancelSimplePaymentResult' =>$rsp), "$funcNameR");

    return $parm;
    
} // ConfirmPayment

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

error_log("DEBUG: $fn- HTTP_RAW_POST_DATA: $HTTP_RAW_POST_DATA\n");
//$data= file_get_contents('php://input');
//error_log("INPUT stream: $data\n\n");

$server = new SoapServer("../wsdl/iSend.wsdl", array('soap_version' => SOAP_1_2));

$server->setClass("SoapHandler");

//$allFunctions = $server->getFunctions();
//error_log( implode(",",$allFunctions));
//error_log("CALLING SOAP HANDLER\n");

$server->handle($HTTP_RAW_POST_DATA);

?>
