<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));
$sessKey='IDT-VNPT-SESSION';

include '../Remittance/rmttObjBase.php';
include 'vnpt_data.php';
include '../Remittance/dbClass.php';
include '../QAlib/wslib1.php';

$wsdl="http://devcall02.ixtelecom.com/VNPT/vnptTopupInterface.wsdl";

class SoapHandler {

function topup($q, $acKey)
{
//    errlog("INFO: topup\n", $q);

    $keyA=array('x_account_number' => $acKey);

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

//errlog('Reload', $rsp);
   return $rsp;
}//topup

function partnerDirectTopup($p)
{
  global $HTTP_RAW_POST_DATA;
  $p=parseSoapBody($HTTP_RAW_POST_DATA);

  $token=$p['token'];
  $rA=sessRetrive($token);
  if (empty($rA)) {

     $errRsp = array(    'errorCode' => '-10',
                      'errorMessage' => 'invalid token',
                        'epayTransID'=> '',
                   'merchantBalance' => 0,
                         'merchantID'=> $p['username'] 
                    );
   errlog("partnerDirectTopupResponse: invalid token", $rA);
   return $rsp;

  }  

//    errlog("partnerDirectTopup", $p);
   $msisdn=$p['targetAccount'];
   $rsp=$this->topup($p, $msisdn);

   errlog("partnerDirectTopupResponse:", $rsp);
   return $rsp;
    
}//partnerDirectTopup

function signInAsPartner($p)
{
  global $HTTP_RAW_POST_DATA;
  $p=parseSoapBody($HTTP_RAW_POST_DATA);

  $username=$p['username'];
  $password=$p['password'];
//  errlog("signInAsPartner", $p);

  $token='vnpt-token.'.rand(); 

  $date=date("Y-m-d\TH:i:sO");
  $sessA=array($date);
  sessStore($token, $sessA);

  $rsp=array('errorCode' => 0,
             'errorMessage' => 'success',
             'token' => $token
             );

// uncomment for manual testing
// $rsp=array('errorCode' => -3,
//             'errorMessage' => 'invalid username',
//             'token' => ''
//             );

   return $rsp;

} //signInAsPartner


function queryBalance($p)
{
   $username=$p->username;
   $requestID=$p->requestID;
   $token=$p->token;

   $rsp=array('dataValue' => 10000,
              'errorCode' => 0,
              'errorMessage' => 'success',
              'transID' => $requestID); 

    return array('BalanceResult' => $rsp);

}//queryBalance

} //SoapHandler


$msg= "DEBUG: HTTP_RAW_POST_DATA: ".$HTTP_RAW_POST_DATA."\n";
errlog('Main', $msg);

$server = new SoapServer($wsdl, array(//default: 'soap_version' => SOAP_1_1,
                                      'style' => SOAP_RPC,
                                      'use' => SOAP_ENCODED
                          ));
$server->setClass("SoapHandler");

try {

$server->handle();

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
