<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__));

include '../ezetop/ImtuTopUpClass.php';
include '../QAlib/wslib1.php';
$wsdl = "http://devcall02.ixtelecom.com/wsdl/Claro_CALA.wsdl";

/* this was taken from Prasan and it is getting changed over according
   to the wsdl for Cubacel */

date_default_timezone_set('America/New_York');

function randStr($len) {
  $ret = '';
  $dig = '0123456789';
  $d = rand(1,10);
  $newChar = $dig[$d];
  $ret = $newChar;
  for ($i=1; $i<$len; $i++) {
      $d = rand(0,10);
      $newChar = $dig[$d];
      $ret .= $newChar;
  }
  return $ret;
}

class SoapHandler {

/* This method gets the balance of the account specified external entity. 
   In the service parameter name specified service on request . For example, 
   if you want to get the balance of the account used by the external entity 
   o sell electronic recharges made âhrough the topup method , this p
   parameter must indicate topup .  */
   function balanceEnquiry($p) {
     errlog("INFO: balanceEnquiry p = ", $p);
     $user = $p->distId;
     $pwd = $p->distPass;
     $service = $p->service;
      $rspA = array('result' => array('responseCode' => 0,
                    'currentBalance' => 1234.56,
                    'newBalance' => 10.00,
                     'operAmount' => 50.00));
      errlog("balanceEnquiry response = ", $rspA);
      return $rspA;
   } //balanceEnquiry

   function getTransaction($p) {
/* This method obtains the detail of a specific transaction */
     errlog("INFO: getTransaction p = ", $p);
     $user = $p->distId;
     $pwd = $p->distPass;
     $transId = $p->transId;
     errlog("getTransaction transId = ", $transId);
      $rspA = array('msisdn' => $user,
                    'amount' => 1234.56,
                    'Date' => new DateTime('NOW'),
		    'transId' => $transId,
		    'responseCode' => 0);
      return $rspA;
   } //getTransaction

   function validateSubscriber($p) {
     errlog("INFO: validateSubscriber p = ", $p);
     $msisdn = $p->msisdn;
     errlog("validateSubscriber msisdn = ", $msisdn);
      $rspA = array('result' =>
                  array('responseCode' => '0'));
     errlog("validateSubscriber response = ", $rspA);
      return $rspA;
   } //validateSubscriber

/*  This method performs an electronic recharge for the subscriber */
   function topup($p) {
     $rspA = array();
     errlog("topup p = ", $p);
     $user = $p->distId;
     $pwd = $p->distPass;
     $transId = $p->transId;
     $msisdn = $p->msisdn;
     $amt = $p->amount;
     errlog("topup msisdn= ", $msisdn);
     // new key for topup
     $keyForKey = $msisdn . $amt;
     $key=array('app' => 'ClaroCALA', 'key' => $keyForKey);
     $orderId = randStr(7);
     $xA = array('msisdn' => 'a0',
		  'transId' => 'a1',
		  'amt' => 'a2',
		  'user' => 'a3',
		  'pwd' => 'a4',
		  'responseCode' => 'a5');
     errlog("topup xa = ", $xA);
     $rA = array('transId' => 'a1',
		'amt' => 'a2',
		'user' => 'a3',
		'pwd' => 'a4',
		'responseCode' => 'a5');
     errlog("topup rA = ", $rA);
     $xArr = array('a0' => $msisdn,
		'a1' => $transId,
		'a2' => $amt,
		'a3' => $user,
		'a4' => $pwd,
		'a5' => '0');
     errlog("topup xArr = ", $xArr);
     $tpObj= new TopupObj($key, $xA, $rA);
     $rspA = array();
     if ($tpObj->re[responseCode]) {
        $rspCd = $tpObj->re[responseCode];
        $rspA = array('result' => array('responseCode' => $rspCd));
        errlog ("INFO topup error response :", $rspA);
        } else {
     $massagedInput = array('msisdn' => $msisdn,
			    'transId' => $transId,
			    'amt' => $amt,
			    'user' => $user,
			    'pwd' => $pwd,
			    'responseCode' => '0');
     errlog ("INFO topup about to validate with:", $tpObj->re);
     $tpObj->validateReqInput($massagedInput);
     errlog ("INFO topup finished validate :", $tpObj->re);
     errlog ("INFO topup delay:", $tpObj->delay);
     $rA = array(); 
     $rsp = $tpObj->buildRspData($xArr);
     //  Inserted for Dom Riggi on December 20, 2017 for testing of PSF
     if (strcmp($msisdn, '79995555') == 0) {
        sleep (130);
     } 
 //  Inserted for Sandy R. for testing CallBack 
     if (strcmp($msisdn, '79994444') == 0) {
        sleep (60);
     }
     $rspA = array('result' => array('msisdn' => $msisdn,
                                     'responseCode' => '0',
                                     'transId' => $transId));
     errlog ("INFO topup response :", $rspA);
     }
   return $rspA;
   }

} //SoapHandler

errlog('Main', $HTTP_RAW_POST_DATA);
//$data= file_get_contents('php://input');
//errlog("INPUT stream: $data\n\n");

$saleServer = new SoapServer($wsdl, 
       array('uri' => "http://172.16.194.252:28081/ESBWS/TOPUPS",
	     'soap_version' => SOAP_1_2,
	     'trace' => 1));

$saleServer->setClass("SoapHandler");
$saleServer->handle();
errlog('handled: ', $HTTP_RAW_POST_DATA);

?>
 
