<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));

include '../Remittance/rmttObjBase.php';
include '../Remittance/dbClass.php';
include 'wslib1.php';
include '../QAlib/soaplib.php';
include 'trfTo_data.php';

$sessName='IDT-TRFTO-SESSION';
$IDTtrfToSession=99124;
session_name($sessName);
$password='o53ixg2ca';

date_default_timezone_set('America/New_York');

function dateformat() { return date("d-m-Y H:i:s");}

function md5Check($login, $password, $key, $cksum) 
{

   if (md5("$login$password$key") != $cksum ) { 

      errlog("ERROR md5Check: $login $password $key - ", $cksum); 
      throw new Exception("Incorrect login-password-key combo", 923); 
   }
   ckKey($login, $key);
}

//used in the topup cmd
function saveTransaction($login, $tObj, $flag)
{
    errlog("INFO: Saving context flag:", $flag);
    $key=$tObj->r_authentication_key;
    $tid=$tObj->r_transactionid;

    $obj=new TrfInfoData;
    $obj->cpFrTp($tObj);

    $k=$login."KEY".$key;
    $t=$login."TID".$tid;
errlog("DEBUG: Storing into session - $k", $t);

    switch ($flag) {
      case 'key':
        $_SESSION[$k]=$tid;
        break;
      case 'tranaction':
        $_SESSION[$t]=$obj;
        break;
      default:
        $_SESSION[$k]=$tid;
        $_SESSION[$t]=$obj;
    }
}

function startSession()
{
    global $IDTtrfToSession;
    session_id($IDTtrfToSession);

    if (!session_start() ) {

        errlog( "SESSION START failed. EXITING!!\n");
        throw new Exception("SYSTEM ERROR: unable to start session for $login", 999);  
    }

}

function ckKey($login, $key)
{
   startSession();

   if (!array_key_exists($login, $_SESSION)) {
        errlog( "INFO: Login not found. Creating it!!", $login);
        $_SESSION[$login]=$key;
   }
   else {
        $oldkey = $_SESSION[$login];
errlog("DEBUG: key encoding for $login - oldKey<$oldkey> key", $key);
//a old requirement that we no longer need - per Jack 2/26/2013
//        if ($key < $oldkey) 
//            throw new Exception("ERROR: wrong key encoding for $login - oldKey<$oldkey> key<$key>", 923); 

        $_SESSION[$login] = $key;
   }

} //ckKey

function popReqToRsp(&$tuData, $p)
{
       global $password;

       $tuData->r_transactionid=0;

       if (isset($tuData->Password)) $password=$tuData->Password;
       md5Check($p['login'], $password, $p['key'], $p['md5']);

       $tuData->r_status_code='1';
       if (!isset($tuData->r_error_code)) 
            $tuData->r_error_code='0';
       if (!isset($tuData->r_error_txt))
            $tuData->r_error_txt='Transaction successful';
       if (!isset($tuData->r_originating_currency))
            $tuData->r_originating_currency = 'USD';
       if (!isset($tuData->r_destination_currency))
            $tuData->r_destination_currency = 'IDR';

       $tuData->r_authentication_key=$p['key'];
       if (!isset($p['send_sms']) ) $p['send_sms']='yes'; 
       $tuData->r_sms_sent = $p['send_sms'];


       if (isset($p['cid1']))
           $tuData->r_cid1=$p['cid1'];
       if (isset($p['cid2']))
           $tuData->r_cid2=$p['cid2'];
       if (isset($p['cid3']))
           $tuData->r_cid3=$p['cid3'];

       $tuData->r_operator='IDT QA Finance';

       if (!isset($tuData->r_country))
           $tuData->r_country='Indonesia';

       if (empty($p['msisdn']))  {
           errlog("ERROR popReqToRsp: Missing ANI (MSISDN) throwing exception- ", 919); 
           throw new Exception("ERROR: MISSING ANI (msisdn)", 919);
       }
       if (!isset($p['destination_msisdn']) || !isset($p['product']) ) {

           $tuData->r_status_code='0';
           $tuData->r_error_code='919';
           $tuData->r_error_txt='All needed argument not received';
        }
       else {
           $tuData->r_msisdn=$p['msisdn'];
           $tuData->r_destination_msisdn=$p['destination_msisdn'];
           $tuData->r_input_value=$p['product'];
           if (!isset($tuData->r_validated_input_value)) 
                  $tuData->r_validated_input_value=$p['product'];
           if (!isset($tuData->r_output_value))
                   $tuData->r_output_value=$p['product'];
           $tuData->r_topup_sent_amount=$p['product'];

       }

       if ($tuData->r_error_code !='0') $tuData->r_transactionid=rand();
       errlog("INFO popReqToRsp: finished ", "popReqToRsp"); 
           
}


// x is xml string input
// return DomNode
function xmls2a($p)
{

	$xmlA=array();
	
        if (!$dom = simplexml_load_string($p)) { 
           errlog("INFO throwing exception: unable to parse XML", "/n"); 
           throw new Exception("Unable to parse XML input", 500);
        }
        foreach($dom as $member=>$data) {
//   	   $dtype=gettype($data);
// 	   dump_errorlog("DOM obj: $member - datatype - $dtype - ", $data);
              $xmlA[$member]=(string) $data;
        }
 
        return $xmlA;
}


//Construct the Array data structure
function sendResp($Retobj) 
{
   $rsp= processRequest(array(), $Retobj);
   $rspA= array('FixedAndMobile' => $rsp);
   crResp($rspA, false);
}


function get_id_from_key($p)
{
     global $password;

     $funcName=__FUNCTION__;
     errlog("DEBUG: processing $funcName", "\n");

     $login=$p['login'];
     $key=$p['key'];

     md5Check($login, $password, $key, $p['md5']);

     $key=$p['from_key'];
     $k=$login."KEY".$key;
errlog("DEBUG: Retriving from session - for key: ", $k);

     if (isset($_SESSION[$k])) { 
        $tid=$_SESSION[$k];
        $errCode=0;
        $errTxt='Transaction successful';
     }
     else {
        $tid=0;
        $errCode=219;
        $errTxt='Key does not exist';
     }

     $rspA=array('FixedAndMobile' => array( 'status_code' => 0,
                                            'error_code'  => $errCode,
                                            'error_txt'   => $errTxt,
                                            'id'    => $tid,
                                            'from_key' => $p['from_key'],
                                            'authentication_key' => $p['key'] ));

     return crResp($rspA, false);

} //get_id_from_key

function trans_info($p)
{
     global $password;

     $funcName=__FUNCTION__;
     errlog("DEBUG: processing $funcName", "\n");
  
     $login=$p['login'];

     md5Check($login, $password, $p['key'], $p['md5']);

     $tid=$p['transactionid'];
     $t=$login."TID".$tid;
errlog("DEBUG: Retriving from session - for transaction: ", $t);

     if (isset($_SESSION[$t])) {
        $tObj=$_SESSION[$t];
     }
     else {
        $tObj=new TrfInfoData;
        $tObj->setTRAstatus(401, "transactionid not found");
     }

    $rsp= processRequest(array(), $tObj);
    crResp(array("FixedAndMobile" => $rsp), false);

} //get_id_from_key


function pingReq($p)
{
     global $password;

     $funcName=__FUNCTION__;
     errlog("DEBUG: processing ", $funcName);

     md5Check($p['login'], $password, $p['key'], $p['md5']);

     $rspA=array('FixedAndMobile' => array( 'status_code' => 0,
                                            'error_code'  => 0,
                                            'error_txt'   => 'Transaction successful',
                                            'info_tax'    => 'pong',
                                            'originating_currency' => 'USD',
                                            'authentication_key' => $p['key'] ));

     return crResp($rspA, false);
}

function topUpReq($p)
{

     $funcName=__FUNCTION__;
     errlog("DEBUG: processing ", $funcName);
     errlog("DEBUG: processing $funcName dumpping input: ", $p);

     //error 919 = all needed arg not received
     if (!isset($p['msisdn'])) {
        errlog("INFO: topUpReq - throwing exception for missing MSISDN", 919);
        throw new Exception("Missing MSISDN parameter", 919);
     }
     //should be same as: AgentId=9999
     // error 104 => msisdn in blacklist
     if ($p['msisdn'] != '9999' ) {
        errlog("INFO: topUpReq - throwing exception for MSISDN not", 9999);
        throw new Exception("Incorrect MSISDN(ANI) value", 104);
     }
     $retCode='0';
     $retMsg='Tranaction Success';

     $keyA=array('x_account_number' => $p['destination_msisdn']);

    $dbObj=getDBdata1('IMTU_DATA', 'TrfTopUpData', $keyA);
    if ($dbObj == FALSE) {
        errlog("INFO: TRANSFERTO topUpReq - not using test data from DB with key", $keyA);
        $dbObj = new TrfTopUpData;
        $dbObj->remap();
       }

    popReqToRsp($dbObj, $p);
    errlog("DEBUG: topUpReq - finished popReqToRsp dbObj", $dbObj);



    $rsp= processRequest($p, $dbObj);
    errlog("DEBUG: topUpReq - finished processRequest", "/n");

$delay=0;
if (isset($dbObj->r_error_code)) $retCode=$dbObj->r_error_code;
if (isset($dbObj->delay)) $delay=$dbObj->delay;
$matched=preg_match('/[0-9]{1,4}/',$retCode, $ecode);
if ($matched) 
       $dbObj->r_error_code=$ecode[0];
else
       $dbObj->r_error_code=0;

if (preg_match('/SAVB/i',$retCode)) { 
    saveTransaction($p['login'], $dbObj, 'keyNtrans');
    errlog("DEBUG: topUpReq - finished saveTransaction", "keyNtrans");
 } else
if (preg_match('/SAVK/i',$retCode)) {
    saveTransaction($p['login'], $dbObj, 'key');
    errlog("DEBUG: topUpReq - finished saveTransaction", "key");
}
else
if (preg_match('/SAVT/i',$retCode)) {
    saveTransaction($p['login'], $dbObj, 'transaction');
    errlog("DEBUG: topUpReq - finished saveTransaction", "transaction");
}


if (preg_match('/NORSP/i',$retCode)) {
    errlog("WARN: $funcName received instruction $retCode - exiting in n seconds where n=", $delay);
    if ($delay>0) time_sleep_until(microtime(true) + $delay);
    exit(0);
}
      if ($delay>0) {

           errlog("WARN: Delaying resp for n seconds where n= ", $delay);
           time_sleep_until(microtime(true) + $delay);
           errlog("WARN: Delayed resp for n seconds where n= ", $delay);
      }
      crResp(array("FixedAndMobile" => $rsp), false);
  
}//topUpReq 

function parseAction($p) {

$rspA = array();

errlog("DEBUG: parseAction input param - ", $p);
$xA=xmls2a($p);

$action=$xA['action'];

switch ($action) {
   case "topup": topUpReq($xA);
            break;
   case "ping": pingReq($xA);
            break;
   case "key": key($xA);
            break;
   case "trans_info": trans_info($xA);
            break;
   case "get_id_from_key": get_id_from_key($xA);
            break;

   default:  {
           errlog("WARN: throwing exception Invalid action= ", $action);
           throw new Exception("Invalid Action <$action>", 901); 
        }
}
} //parseAction

try {
   errlog("INFO: polling for input", $fn);

   if (!empty($HTTP_RAW_POST_DATA)) {
      $reqRev=$HTTP_RAW_POST_DATA;
      errlog("INFO: $fn: HTTP_RAW_POST_DATA", $HTTP_RAW_POST_DATA);
   
      }
   if (!empty($_POST)) {
      errlog("DEBUG: POST: ", $_POST);
      $reqRevP=array_pop($_POST);
      } 

//echo "<br/><br/>Received query: <br/>";
//var_dump($reqRev);
//echo "<br/>From: ", $url, "<br/>";

   if (isset($reqRev) ) parseAction($reqRev);
   else { 
      errlog("INFO: throwing new Exception inside try", 919);
      throw new Exception("ERROR: Missing Transfer XML", 919);
      errlog("INFO: threw new Exception inside try", 919);
      }
   }
catch (SoapFault $ex) { // matches to try after finishing parseAction
   errlog("INFO: throwing new Exception inside catch", $ex_faultcode);
    throw new Exception($ex->faultstring, $ex->faultcode); 
   errlog("INFO: threw new Exception inside catch", $ex_faultcode);
   }
catch (Exception $e) { // catch the Exception that is not a SoapFault
   errlog("INFO: Caught Exception ", $e);
   $msg=$e->getMessage();
   $code=$e->getCode();
   errlog("Caught exception: $msg, code=", $code);
   $errA=array('FixedAndMobile' => array('status_code' => 1,
                                      'error_code'  => $code,
                                      'error_txt'   => $msg   )
              );
   errlog("INFO: end of catch routine for ", $e);
   crResp($errA, false);
}

?>
