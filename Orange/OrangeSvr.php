<?php
ini_set("log_errors", 1);
include '../Remittance/rmttObjBase.php';
include 'Orangedata.php';
include '../Remittance/dbClass.php';
include '../QAlib/wslib.php';

$PsSession = 99324;
$wsdl="http://devcall02.ixtelecom.com/wsdl/Orange.wsdl";
$fn=preg_replace('/\.php/', '', basename(__FILE__));
ini_set("error_log", "/logs/" . $fn . ".log");

function passwdRuleCheck($pw)
{

}

function myErrlog($modName, $msgAnyType, $eNum=0) 
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

function startSession()
{
    global $fn;
    global $PsSession;
    session_id($PsSession);

    if (!session_start() ) {

        error_log( "SESSION START failed. EXITING!!\n");
        throw new SoapFault("SYSTEM ERROR: startSession  unable to start session for $fn", 101);
    }

}


class SoapHandler {

function authHeader($p)
{
    global $fn;
//    myErrlog('authHeader', $p);

}

function createReceivableRequest($q)
{
    global $fn;
//    myErrlog('createReceivableRequest-q', $q); 

      $p= &$q->createReceivableParam;

      $SNwId = $p->SNwId;
      $SNtime = $p->SNwTimeStamp;
      $SNcountry = $p->SCountryCode;
      $SNdurType = $p->DurationType;
      $SNrecValue = (string) $p->ReceivableValueSc;

      $rStatus = '0';

      // remove the comment below for manual testing 4/2/2012
      //$rStatus = '41007';
      $tod = date("Y-m-d\TH:i:sO");

      if ($rStatus != '0') {

         $rMessage = 'Invalid Login/Password';

         $respA=array('return' => array('Status' => $rStatus,
                            'Message' => $rMessage,
                            'ReceivableId' => '0',
                            'ReceivableValueEuro' => '0',
                            'ReceivableEndValidityDate' => $tod
                  ));

         myErrlog('createReceivableRequest Failed', $respA);
         return $respA;

       } //createReceivableRequest Failed

      $rMessage = 'ok';

      $tid= (string) rand();
      $ReceivableId = 'idt'.$tid;

      startSession();
      $tcon= new TransactionContext();

      $tcon->ReceivableId = $ReceivableId;
      $tcon->Amount = $SNrecValue;
      $tcon->IatTrxId = 'Iat'.$tid;
      $tcon->SNwId = $SNwId;
      $tcon->SCountryCode = $SNcountry;
      $tcon->DurationType = $SNdurType;
      $tcon->ReceivableValueSc = $SNrecValue;
      $tcon->Status = $rStatus;
      $tcon->ReturnMessage = $rMessage;
      $tcon->ReceivableEndValidityDate = $tod;

      $tid="tid".$tid;

      $_SESSION[$tid]=$tcon;

      $respA=array('return' => array('Status' => $rStatus,
                            'Message' => $rMessage,
                            'ReceivableId' => $ReceivableId,
                            'ReceivableValueEuro' => $SNrecValue,
                            'ReceivableEndValidityDate' => $tod
                  ));

       myErrlog('createReceivableRequestResponse', $respA);

       return $respA;
}

function burnErr($code, $msg, $p)
{

       $rspA = array('IatTrxId' => rand().'burnErr',
                     'SNwTrxId' => $p->SNwTrxId,
                     'IatTimeStamp' => date("Y-m-d\TH:i:sO"),
                     'Status' => $code,
                     'Message' => $msg,
                     'ReceivableId'=> $p->ReceivableId ); 

       $ns='java:com.wha.iah.pretups.ws';
       $rspSV = new SOAPVar($rspA, SOAP_ENC_OBJECT, 'BurnResult', $ns);

       $rspV=array('return' => $rspSV);
       myErrlog('burnRequestError: ', $rspV);  
       return $rspV;
}

function burnRequest($q)
{

//myErrlog('burnParam', $q);

    $p=&$q->burnParam;
//simulator only
//    $p=&$q;

    $ReceivableId= $p->ReceivableId;

    $matched=preg_match('/[0-9]{1,15}/',$ReceivableId, $ecode);
    if ($matched) 
          $tid="tid".$ecode[0];
    else
       return $this->burnErr(31017, "Invalid ReceivableId - $ReceivableId", $p);

    startSession();

    if (!isset($_SESSION[$tid]))
               return $this->burnErr(34001, "Failed to find transaction ReceivableId <$tid>", $p);

    $tcon = $_SESSION[$tid];

    $keyA=array('x_account_number' => $p->MSISDN2);

    $dbO=getDBdata1('IMTU_DATA', 'TopupObj', $keyA);
    if ($dbO == FALSE) {
        myErrlog('burnRequest.getDBdata1', 'INFO: no data found from DB\n');
        $dbO = new TopupObj;
        $dbO->remap();
       }

//myErrlog('DBINFO', $dbO); 

     $dbO->setAfterDb($p);

   $rspA = processRequest($p, $dbO);

   $delay=$dbO->delay;

    if ($delay>0) {

           myErrlog("burnRequestResp", " delaying for $delay seconds");
           time_sleep_until(microtime(true) + $delay);
      }

  $rspA['IatTrxId'] = $tcon->IatTrxId;
  $tcon->SNwTrxId =$rspA['SNwTrxId'];
  $tcon->Status =$rspA['Status'];
  $tcon->ReturnMessage =$rspA['Message'];
  $tcon->IatTimeStamp=$rspA['IatTimeStamp'];

  $tcon->statusReqAction = $dbO->statusReqAction;
  $tcon->statusReqDelay = $dbO->statusReqDelay;
  $tcon->statusReqRcode = $dbO->statusReqRcode;
  $tcon->statusReqRetry = $dbO->statusReqRetry;
  $tcon->burnReqAction = $dbO->burnReqAction;
  $tcon->burnReqDelay = $dbO->burnReqDelay;
  $tcon->burnReqRetry = $dbO->burnReqRetry;

  $_SESSION[$tid]=$tcon;

  if ($dbO->abort == true) {
     myErrlog('burnRequest ABORTing - transaction context has been saved.', $tcon);
     exit(0);
  } 

  $ns='java:com.wha.iah.pretups.ws';
  $rspSV = new SOAPVar($rspA, SOAP_ENC_OBJECT, 'BurnResult', $ns);

  $rspV=array('return' => $rspSV);

   myErrlog('burnRequestResp', $rspV); 
   return $rspV;

}//burnRequest

function checkStatusRequest($q)
//balance query
{
    global $fn;
    //myErrlog('checkStatusRequest', $q); 

    $p=& $q->checkStatusParam;

    $mnotid = $p->IatTrxId;
    $sntid = $p->SNwTrxId;
    //$snid = $p->SNwId;

    $matched=preg_match('/[0-9]{1,15}/', $mnotid, $ecode);
    if ($matched)
          $tid="tid".$ecode[0];
    else
       return $this->burnErr(31017, "checkStatusRequest Invalid tid - $tid", $p);

    startSession();

//myErrlog('checkStatusRequest.GLOBALS ', $GLOBALS['_SESSION']);

    if (!isset($_SESSION[$tid]))
               return $this->burnErr(34001, "Failed to find transaction Id <$tid>", $p);

    $tcon = $_SESSION[$tid];

//myErrlog('checkStatusRequest.tcon ', $tcon);

    $snid = 'ML1';
    $amount=7.5;

    $delay=$tcon->statusReqDelay;

    if ($delay>0) {

           myErrlog("burnStatusResp ", "delaying for $delay seconds");
           time_sleep_until(microtime(true) + $delay);
     }

     if ($tcon->statusReqAction == 'ABORT') {
        myErrlog('burnStatus ABORTing - transaction context preserved.', $tcon);
        exit(0);
     }


    if ($tcon->statusReqRcode != 0) { 
        if ($tcon->statusReqRetry > 0) { 
            $tcon->statusReqRetry = $tcon->statusReqRetry -1;
            if ($tcon->statusReqRetry == 0) 
                $tcon->statusReqRcode = 0;
            else
                $_SESSION[$tid]=$tcon;
        }
    }

    $rspA= array( 'Status' => $tcon->statusReqRcode,
                 'Level' => 0,
                 'IatReasonCode' => 0,
                 'IatReasonMessage' => $tcon->statusReqAction,
                 //'RNwReasonCode' => 'string12345',
                 //'RNwReasonMessage' => 'RnwCode',
                 'IatReceivedAmount' => $tcon->Amount,
                 'Fees' => '1.0',
                 'ProvRatio' => '1.1764706',
                 'ExchangeRate' => '655.957',
                 'RBonus' => '0.0',
                 //'RPfReceivedAmount' => '8.0',
                 //'RecipientReceivedAmount' => '8.0',
                 'RPfReceivedAmount' => $tcon->Amount,
                 'RecipientReceivedAmount' => $tcon->Amount,
                 'RNwId' => $snid
              );

  $ns='java:com.wha.iah.pretups.ws';
  $rspSV = new SOAPVar($rspA, SOAP_ENC_OBJECT, 'CheckStatusResult', $ns);

  $rspV=array('return' => $rspSV);
myErrlog('checkStatusRequestResponse', $rspV); 
   return $rspV;
} //checkStatusRequest



} //SoapHandler 


$msg= "DEBUG: HTTP_RAW_POST_DATA:".mix2str($HTTP_RAW_POST_DATA)."\n";
myErrlog('Main', $msg); 

$server = new SoapServer($wsdl, array('soap_version' => SOAP_1_2));


$server->setClass("SoapHandler");

try {

$server->handle($HTTP_RAW_POST_DATA);

}
catch (Exception $e) {

   $errMsg="CatchException";
   $status=$e->getCode();

   $errMsg .=" Message: ".$e->getMessage();
   $errMsg .="Code: ".$code=$status;

   myErrlog('CAUGHT AN EXCEPTION: ', $e);

/*
  $rspA=array('Status' => $status, 'Message' => $errMsg);
  $ns='java:com.wha.iah.pretups.ws';
  $rspSV = new SOAPVar($rspA, SOAP_ENC_OBJECT, 'CheckStatusResult', $ns);
  $rspV=array('return' => $rspSV);
   return $retA;
*/
}
?>
