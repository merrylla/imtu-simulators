<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));

#include './overLoadedSoapServer.php';
include '../QAlib/wslib1.php';
include '../ezetop/ImtuTopUpClass.php';

$wsdl="http://devcall02.ixtelecom.com/wsdl/BUYPACK.wsdl";

function i_rand($min=0,$max=1000){
/* This random number generator is used to find a pin */
    if ($min>$max) return false;
    $rand= mt_rand($min,$max);
errlog("Inside Prasan.i_rand()= ",$rand);
    return $rand;
}  

function s_rand($numDigits) {
    $chars = '0123456789';
    $randStr = "";
    for ($i=0; $i<$numDigits; $i++) {
        $randInt = rand(0, 9);
        $char1 = $chars[$randInt];
        $randStr .= $char1;
   }
   return $randStr;
} 

function checkAuth($p) {
   $username=$p->distId;
   $rspCode = 0;
   errlog("Inside Claro Super checkAuth userName",$username);
   if ($username != "120514")
      $rspCode = 4;   
   $pwd=$p->distPass;
   //errlog("Inside Claro Super checkAuth pwd",$pwd);
   if (strcmp($pwd, "secret") != 0)
      $rspCode = 5;
   return $rspCode;
}   
    
class SoapHandler {

	function validateOperation($p)
	{
	  errlog("Inside Claro Super validateOperations= ",$p);
	  $resp = checkAuth ($p);
	  $pwd=$p->distPass;
	  $msisdn=$p->msisdn;
	  $pack=$p->idPack;
          $subscriberStat = "Disponible"; // or "No Disponible"
          $msisdnLast3 = substr($msisdn, 8);
          if (strpos($msisdnLast3, "03") == 1) {
             $resp = -3;
	     errlog("validateOperations resp =", $resp);
          } else if (strpos ($msisdnLast3, "04") == 1)
             $resp = 4;
          else if (strpos ($msisdnLast3, "05") == 1)
             $resp = 5;
          else if (strpos ($msisdnLast3, "08") == 1)
             $resp = 8;
          else if (strpos ($msisdnLast3, "11") == 1)
             $resp = 11;
          else if (strpos ($msisdnLast3, "12") == 1)
             $resp = 12;
          else if (strpos ($msisdnLast3, "19") == 1)
             $resp = 19;
          else if (strpos ($msisdnLast3, "20") == 1)
             $resp = 20;
          else $subscriberStat = "Disponible"; // or "No Disponible"
          $rsp = array('idPack' => $pack,
			'name' => "merryll", 
			'responseCode' => $resp,
			'statusSubscriber' => $subscriberStat, /// Disponible,
                        'typeService' => "Combo",
			'value' => 0.00);
	  errlog("Inside Claro Super validateOperation msisdn",$rsp);
	  return array('result' => $rsp);
	}

	function getListPacks($p)
        /*  This is used to let the info on which pack IDs to request */
	{
	  errlog("Inside Claro getListPacks p= ",$p);
	  $username=$p->distId;
	  $password=$p->distPass;
	  $resp = checkAuth ($p);
          $pack1=array('id' => '1',
                      'name' => 'USA1',
		      'typeService' => 'a2',
		      'value' => '7.00');
          $pack2=array('id' => '2',
                      'name' => 'USA2',
		      'typeService' => 'a2',
		      'value' => '10.00');
          $pack3=array('id' => '3',
                      'name' => 'USA3',
		      'typeService' => 'a2',
		      'value' => '15.00');
          return array('Pack' => $pack1,
			'Pack' => $pack2,
			'Pack' => $pack3);
        }

	function buyPack($p)
        /*  This is the topUp function */
	{
	  errlog("Inside buyPack p= ",$p);
	  $resp = checkAuth ($p);
	  $msisdn=$p->msisdn;
	  $idPack=$p->idPack;
	  $topUpNum=$p->transId;
	  errlog("Inside Claro Super ",$topUpNum);
          $xA=array('countryCode' => 'a0',
		    'amount' => 'a1',
		    'currencyCode' => 'a2',
                      'startBal' => 'a3',
		      'respCode' => 'a4');
	  //$statKey = array('app' => 'ClaroCALA', 'key' => $topUpNum);
	  $delayKey = array('app' => 'ClaroCALA', 'key' => $msisdn);
          $rA=array('topUpNum' => 'a5',
		    'newBalance' => 'a6',
		    'oldBalance' => 'a7');
	  $delayTopObj = new TopupObj($delayKey, $xA, $rA);
	  //$statTopObj = new TopupObj($statKey, $xA, $rA);
          //errlog("Inside Claro BuyPak TopUp",$statTopObj);
          errlog("Inside Claro BuyPack delay TopUp",$delayTopObj);
	  /*$massagedInput = array('phoneNumber' => $topUpNum,
				'batch' => $batch,
				'topUpAmount' => $amt,
				'transactionCode' => $rspCode,
				'transactionResponse' => $respDesc,
				'confCode' => $confirmationCode,
				'auditNum' => $auditNo,
				'providerResp' => $provRsp);
          $xArr = array ('a0' =>  $topUpNum,
			'a1' => $batch,
			'a2' => $amt,
			'a3' => $rspCode,
			'a4' => $respDesc,
			'a5' => $confirmationCode,
			'a6' => $auditNo,
			'a7' => $provRsp);
	  insertTransDB($statKey, $xArr); 
	  $statTopObj->validateReqInput($massagedInput);
	  errlog ("INFO ClaroCALA - statTopObj", $statTopObj); */ 
	  $raD = array();
	  if ($delayTopObj->isAbort() == 'false') {
	     $delayTopObj->buildRspData($raD);
             errlog("Inside Claro buyPack did delay if needed",$delayTopObj->re);
             if ((sizeof($delayTopObj->xe)> 0) && 
	         ($resp== 0)) {
                $rspCode = $delayTopObj->xe['respCode'];
                }
             errlog("Inside Claro buyPack setting up reult array",$delayTopObj->xe);
             $idTransNum = s_rand(3);
             if (is_null($rspCode)) {
	   	$rspCode = 0;
	     }
             $rsp = array(
			'idTrans' => $idTransNum,
			'newBalance' => 0,
			'oldBalance' => $delayTopObj->xe['startBal'],
			'responseCode' => $rspCode,
			'value' => $delayTopObj->xe['startBal']);
             errlog("Inside Claro buyPack rsp = ",$rsp);
	  return array('result' => $rsp);
          } // took delay into account  
        else { // abort !!
             errlog("Inside Claro buyPack aborting ",$rsp);
	    }
	} // buyPack end 
	
} //SoapHandler

errlog('Main', $HTTP_RAW_POST_DATA);

//
// notice $server is started differently here...
//
$server = new SoapServer($wsdl, 
                array('uri' => "http://www.simxpins.com/WebServices/",
		      'soap_version' => SOAP_1_2,
		      'trace' => 1));

#$server = new SoapServer(null, array('uri' =>$wsdl));
$server->setClass("SoapHandler");
$server->handle();

?>
