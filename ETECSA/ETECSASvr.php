<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__));
$startTime = new date("Y-m-d H:i:s");

include 'ImtuTopUpClass.php';
include '../QAlib/wslib1.php';
/*  Written by Merryll Abrahams */

function retrievXmlParm2B($xmlinput, $paramList)
{   
$eA=array();
$dom = new DOMDocument();
if (!$dom->loadXML($xmlinput) ) return $rA;
$xmlparams = $dom->getElementsByTagName('*');  //find all nodes

   
foreach ($xmlparams as $node) {
    errlog("INFO: retrievXmlParm2A node: ", $node->getNodePath());
   foreach($paramList as $param) {

       $nodeName=$node->tagName;
    errlog("INFO: retrievXmlParm2A testing $nodeName & param ", $param);
//        echo $nodeName." ".$param."\n";
        $nodeName=preg_replace("/.*:(.*)/", "$1", $nodeName);
    errlog("INFO: retrievXmlParm2A new nodeName", $nodeName);
      if ($nodeName == $param ) {
            $eA[$param]= $node->nodeValue;
    errlog("INFO: retrievXmlParm2A setting outputA[$param]", $node->nodeValue);
            }
   }
}

return $eA;
}//retrievXmlParm2A

function sendRsp($a, $code=200)
/* This is used every time a JSON response is sent */
{
    $jmsg=json_encode($a);
    $ht = new HttpResponse();
    $ht->status($code);
    $ht->setContentType('application/json');
    $ht->setData($jmsg);
    errlog("INFO: sendRsp sending resp back: ", $jmsg);
    $ht->send();

}

function f_rand($min=0,$max=1,$mul=1000){
/* Randomly getting a floating number between one and zero is used to find
   a fake exchange rate for USD -> MXN where this outcome is subtracted from
   14.. */
    if ($min>$max) return false;
    return mt_rand($min*$mul,$max*$mul)/$mul;
}

function i_rand($min=0,$max=1000){
/* This random number generator is used to find a pin */
    if ($min>$max) return false;
    return mt_rand($min,$max);
}

function s_rand($random_string_length) {
/* This random number generator is used to find a pin */
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789-';
    $string = '';
    for ($i = 0; $i < $random_string_length; $i++) {
      $string .= $chars[rand(0, strlen($chars) -1)];
    }
    return $string;
}

function sendGiftResponse($card, $sName, $phone, $amt, $payer, $cntry, $rName, $rPhone, $rate, $curr, $quoteAmt, $auth, $txNumb, $bal, $stat, $res, $desc)  {
/* This should be called whenever a parameter is found to be invalid. As it 
   stands now, the only time that the simulator looks for that is inside of
   processPinReq */
  $rsp = array ('eGiftcardNumber' => $card, 'senderName' => $sName, 
  'senderPhoneNumber' => $phone, 'baseCurrencyAmount' => $amt, 
  'retailPayer' => $payer, 'beneficiaryCountry' => $cntry, 
  'beneficiaryName' => $rName, 'beneficiaryPhoneNumber' => $rPhone, 
  'fxRate' => $rate, 'quoteCurrency' => $curr, 
  'quoteCurrencyAmount' => $quoteAmt,
  'authorizationNumber' => $auth, 'txNumber' => $txNumb, 'balance' => $bal, 
  'status' => $stat, 'resultCode' => $res, 'description' => $desc);
  errlog ("respo to send", $rsp);
  sendRsp($rsp);
}

function sendStatusResponse($card, $sName, $phone, $amt, $payer, $rName, $rPhone, $cntry, $rPhone, $rate, $curr, $quoteAmt, $auth, $txNumb, $bal, $stat, $res, $desc)  {
/* This should be called whenever a parameter is found to be invalid. As it 
   stands now, the only time that the simulator looks for that is inside of
   processPinReq */
  $rsp = array ('eGiftcardNumber' => $card, 'senderName' => $sName, 
  'senderPhoneNumber' => $phone, 'baseCurrencyAmount' => $amt, 
  'retailPayer' => $payer, 
  'beneficiaryName' => $rName, 'beneficiaryPhoneNumber' => $rPhone, 
  'beneficiaryCountry' => $cntry, 'beneficiaryPhoneNumber' => $rPhone, 
  'fxRate' => $rate, 'quoteCurrency' => $curr, 
  'quoteCurrencyAmount' => $quoteAmt,
  'authorizationNumber' => $auth, 'txNumber' => $txNumb, 'balance' => $bal, 
  'status' => $stat, 'resultCode' => $res, 'description' => $desc);
  errlog ("status to send", $rsp);
  sendRsp($rsp);
}

function sendGiftRedeemedError() {
/* Since nothing in the simulator tells the DB that a gift is redeemed,
   I don't see how this should be called.  But, we do cancel pins and 
   perhaps they are cancelled when they are redeemed.  So, if the system
   receives a request to cancel a Pin after a a pin has been cancelled,
   this function will be called */
  $rsp = new SimpleXMLElement('<quippiApi/>');
  $rsp->addChild('operationStatus', 5);
  $rsp->addChild('discriptiion', 'Giftcard has been redeemed and may not be invalidated'); 
  errlog ("xml to send", $rsp->asXML());
  sendRsp($rsp->asXML());
}

function sendPinError() {
/* This subroutine is called from getPinStatus when there is no record in
   the database for that order.  Since the getPinStatus routine has Pin
   as a parameter, it could be that the Pin is not recognized.  But, we do 
   not save records in the DB based on Pin so this really does not make sense */
  $rsp = new SimpleXMLElement('<quippiApi/>');
  $rsp->addChild('operationStatus', 6);
  $rsp->addChild('discriptiion', 'PIN not found'); 
  errlog ("xml to send", $rsp->asXML());
  sendRsp($rsp->asXML());
}

function showCredit($ap)
/*  If the signature is correct then we figure out the rate and put it into 
the DB as well as send it back.  We put it into the DB so taht when someone
asks for a particular rate, we know that Quippi sent the that rate.*/
{
    errlog("INFO - showCredit", $ap);
    $code = $ap['Code'];
    $data = $ap['Data'];
    $outcome = $ap['Success'];
    $countryFull  = ''; 
    $quoteCurr = '';
    $desc = '';
    $rate='';
    $quoteAmt = '';
    $amt = $ap['baseCurrencyAmount'];
    errlog("INFO - getXchgRate: curr=", $curr);
    if (($curr != "USD") && ($curr != "GBP")  && ($curr != "EUR")) {
           errlog("INFO - getXchgRate test for invalid base passed", $curr);
          sendRate(null, "", "", 0, null, 0, 0, "", "INVALID BASE CURRENCY", "100");
          }
//sendRate($time, $countryFull, $ctryCode, $rate, $curr, $amt, $quoteAmt,
 //		  $quoteCurr, $desc, $result)
    else if (($country == 'Guatemala') || ($country == 'GT')) {
         errlog ("INFO - getXchgRate test for invalid base fell thru", $curr);
        $countryFull = 'GUATEMALA';
        $country = 'GT';
        $quoteCurr = 'GTQ';
        $desc = null;
        $result = 0;
	if ($curr == 'USD') {
           $rate = 7.5;
	} else if ($curr == 'GBP') {
	   $rate = 11.2;
	} else if ($curr == 'EUR') $rate = 8.0;
	$quoteAmt = $rate * $amt;
    } else if ($country == 'Honduras') {
        $countryFull = 'HONDURAS';
        $quoteCurr = 'HNL';
	if ($curr == 'USD') {
           $rate = 21.5;
	} else if ($curr == 'GBP') {
	   $rate = 30.9;
	} else if ($curr == 'EUR') $rate = 22.25;
	$quoteAmt = $rate * $amt;
    } else if ($country == 'ElSalvador') {
	$country = 'EL SALVADOR';
        $quoteCurr = 'SVC';
        $desc = null;
        $result = 0;
	if ($curr == 'USD') {
           $rate = 8.6;
	} else if ($curr == 'GBP') {
	   $rate = 13;
	} else if ($curr == 'EUR') $rate = 9.5;
	$quoteAmt = $rate * $amt;
    } 
    $day = date ('D M d H:i:s');
         errlog ("INFO - getXchgRate sending amt", $amt);
         errlog ("INFO - getXchgRate sending curr", $quoteAmt);
    $day .= " CST " . date('Y');
    $desc = null;
    $amount = $amt;
    $result = 0;
    sendRate($day, $countryFull, $country, $rate, $amount, $rate*$amt, $quoteCurr, null, "0");
} // end of getXchgRate 

function sendRate($time, $countryFull, $ctryCode, $rate, $amt, $quoteAmt, $quoteCurr, $desc, $result)
/*  This should be called from getXchgRate after deciphering whether the 
    Accept HTTP request header specified an XML response or a JSON response.
    Since I have not figured out how to do that yet, we only send JSON for now. 
*/
{
  $rsp = new SimpleXMLElement('<quippiApi/>');
  $rsp->addChild('operationStatus', 1);
  $rsp->addChild('discriptiion', 'Unspecified error');
  errlog ("xml to send", $rsp->asXML());
  $rsp = array ('fxRateDate' => $time,
  		'countryName' => $countryFull,
  		'countryCode' => $ctryCode,
  		'fxRate' => $rate,
  		'baseCurrencyAmount' => $amt,
  		'quoteCurrencyAmount' => $quoteAmt,
  		'quoteCurrency' => $quoteCurr,
  		'description' => $desc,
  		'resultCode' => $result);
  errlog ("rsp to send", $rsp);
  sendRsp($rsp);
}

function changeMasterPwd($ap)  
{
  errlog("INFO changeMasterPwd- ", $ap);
  $mess = "Success operation";
  $fail = false;
  if (isset($ap['new_password'])) {
       $newPwd= $ap['new_password'];
       $len = strlen($newPwd);
       if ($len< 8) {
          $fail = true;
       } elseif ($len > 15) {
          $fail = true;
       } else {
          $upper = false;
          $lower = false;
          $number=false;
          $symbol=false;
          for ( $i = 0; $i <= $len; $i++ ) {
            $char = substr( $newPwd, $i, 1 );
            if ($char >= 'a') && ($char < '{') {
               $lower = true;
            } 
            if ($char >= 'A') && ($char < '[') {
               $upper = true;
            } 
            if ($char >= '0') && ($char < ':') {
               $number = true;
            } 
            if (($char >= '!') && ($char < '0')) || (($char >= ':') && ($char < 'A')) ||
               (($char >= '[') && ($char < 'a')) || (char > 'z') {
               $symbol = true;
            } 
          }
          if (($upper == false) || ($lower == false) || ($number == false) || ($symbol == false)) {
            $fail = true;
          } 
  if ($fail == true) {
     $rsp = array ('Msg' => "Unknown validation error",
  		'Success' => false);
  } else {
     $rsp = array ('Msg' => "Success Operation",
  		'Success' => true);
  }
  errlog ("changeMasterPwd: rsp to send", $rsp);
  sendRsp($rsp);
}

function cancelGiftCard($ap)
/*  When the user sends a request to cancel a pin, this routine will be called.
    It first looks in the DB to make sure that a record is there for that order
    and if it is found, change the status in the Db abd send out the appropriate
    response.  If the record is not found an error response is sent out.  */
{
    errlog("INFO cancelGiftCard - ", $ap);
    $card = ""; 
    if (isset($ap['eGiftcardNumber'])) {
       $card = $ap['eGiftcardNumber'];
    }
    errlog("INFO : cancelGiftCard - card", $card);
    $quoteAmt = 0.0;
    if (isset($ap['quoteCurrencyAmount'])) {
       $quoteAmt = $ap['quoteCurrencyAmount'];
    }
    $payer = "";
    if (isset($ap['retailPayer'])) {
       $payer = $ap['retailPayer'];
    }
    $store = "";
    if (isset($ap['store'])) {
       $store = $ap['store'];
    }
    errlog("INFO : cancelGiftCard - store", $store);
    $teller = 0;
    if (isset($ap['tellerStation'])) {
       $teller = $ap['tellerStation'];
    }
    $txNum = "";
    if (isset($ap['txNumber'])) {
       $txNum = $ap['txNumber'];
    }
    $date = "";
    if (isset($ap['txRequestDate'])) {
       $date = $ap['txRequestDate'];
    }
    errlog("INFO : cancelGiftCard - date", $date);
    $code = "";
    if (isset($ap['txCode'])) {
       $code = $ap['txCode'];
    }
    if (strlen($card) != 0) {
       $xmld = getDB('MoneyAccess', $card);
       $dbKey = array('app'=>'MoneyAccess', 'key' => $card);
       if (isset($xmld)) {
       $xa = array('sendName' => 'a0',
	  	  'senderPhone' => 'a1',
	  	  'baseCurrencyAmount' => 'a2',
	  	  'retailPayer' => 'a3',
	  	  'beneficiaryCountry' => 'a4',
	  	  'beneficiaryName' => 'a5',
	  	  'beneficiaryPhone' => 'a6',
	  	  'store' => 'a7',
	  	  'tellerStation' => 'a8',
	  	  'txNumber' => 'a9',
	  	  'txDate' => 'a10',
	  	  'txCode' => 'a11');
       $ra = array('fxRate' => 'a12',
	  	  'quoteCurrency' => 'a13',
	  	  'quoteCurrencyAmount' => 'a14',
	  	  'authorization' => 'a15',
                  'balance' => 'a16',
                  'status' => 'a17');
       $ca=array_merge($xa,$ra, array('delay'=>'delay', 'abort'=>'abort'));
       errlog("INFO : cancelGiftCard - ca", $ca);
       errlog("INFO : cancelGiftCard - xmld", $xmld);
       $xaNew = array('<a16>0.00</a16><a17>VOID</a17>');
       $xA=retrievXmlParm2A($xmld, $ca);
       $xA[a16] = 0.00;
       $xA[a17] = 'VOID';
       updateTransDBRec($card, $xA, 'MoneyAccess');
       errlog("INFO : cancelGiftCard - xA from xml", $xA);
        foreach($xa as $key => $val) {
                 errlog("INFO cancelPin - xA key: $key  val: ", $val);
              if (array_key_exists($val, $xA))  {
                 errlog("INFO cancelPIN - found array key: $val for key:", $key);
                 if (isset($xA[$val])) {
                    errlog("INFO cancelPin - setting xe[$key] to: ", $xA[$val]);
                    $xe[$key]=$xA[$val];
                    } // end if
                 } // end if array_key_exists
         } // end foreach loop

            sendGiftResponse($card, $xe['sendName'], $xe['senderPhone'],
	         $xe['baseCurrencyAmount'], $xe['retailPayer'], 
		 $xe['beneficiaryCountry'], $xe['beneficiaryName'],
		 $xe['beneficiaryPhone'], $xe['fxRate'], $xe['quoteCurrency'],
		 $xe['quoteCurrencyAmount'], $xe['authorization'],
		 null,0.00, 'VOID', 0, null); 
//Delete the record so it won't be found again.
          //       deleteTransDBRec($dbKey); 
          } else {
            sendGiftResponse($card, "", null, null, "", "", "", "", null, null,
		 null, null, null, null, null, 0.00, -1, 400, 
	      "ALREADY VOIDED", null, "REQUEST DENIED", null);
          }
   } // card was given
} // end of cancelGiftCard

function newServiceRequest($ap) 
/*  This routine can be called at any time when a new service is requested
    for a phone from ETECSA . */
{
    errlog("INFO newServiceRequest - ", $ap);
    $id, $amount, $currency, $service, $servType = "";
    $responseCode = '01';
    if (isset($ap['id'])) {
       $requestID = $ap['id'];
    } else {
      $responseCode = '11';
      $mess="No operation id specified";
    }
    if (isset($ap['amount'])) {
       $amount = $ap['amount'];
    } else {
      $responseCode = '12';
      $mess="No operation amount specified";
    }
    if (isset($ap['currency'])) {
       $currency = $ap['currency'];
    } else {
      $responseCode = '13';
      $mess="No operation currency specified";
    }
    if (isset($ap['service'])) {
       $phone = $ap['service'];
    } else {
      $responseCode = '16';
      $mess="No service specified";
    }
    if (isset($ap['service_type'])) {
       $servType = $ap['service_type'];
    } else {
      $mess="No service type specified";
      $responseCode = '14';
    }
    errlog("INFO newServiceRequest id ", $requestID);
    if ($responseCode != '01') {  // in this case there was an erroneous input value
       $rsp = array ("res_operation_id" => i_rand(1, 50000),
			"Message" => $mess,
//$mess should be set each time responseCode is set to something 
			"Code" => $responseCode,
			"Success" => "FALSE");
        errlog("INFO newServiceRequest error response:", $rsp);
    } else { // all is well .. put a record in the DB for this
    $phKey = array ('app' => 'ETECSASvr', 'key' => $phone);
    $idKey = array ('app' => 'ETECSASvr', 'key' => $requestID);
    $xA = array('id' => 'a0',
                'amount' => 'a1',
		'currency' => 'a2',
		'servType' => 'a3',
		'phone' => 'a4',
		'operator' => 'a5',
		'duration' => 'a6');
    $rA = array ('status' => 'a7',
		 'begin_time' => 'a8',
		 'end_time' => 'a9',
		 'testName' => 'a10');
    $tpObjPh = new TopupObj($phKey, $xA, $rA);
    $tpObjId = new TopupObj($idKey, $xA, $rA);
    //massagedInput contains values for the topup rec
    $endTime = new date("Y-m-d H:i:s");
    $duration = 
    $massagedInput = array('id' => $requestID,
                'amount' => $amount,
		'currency' => $currency,
		'servType' => $servType,
		'phone' => $phone,
		'operator' => 'IDT',
		'operationID' => 'a6',
		'begin_time' => $startTime,
		'end_time' => $endTime,
		'duration' => 'a7');
			    //finish this TODO
    $tpObj->validateReqInput($massagedInput);
        errlog("INFO newServiceRequest topup result:", $tpObj->re);
    $tpObj2->buildRspData($massagedInput);
    
    $cardNum = i_rand(1,50000);
    errlog("INFO createGiftCard- key:", $key);
    //    errlog("INFO createGiftCard- extFx with len $extFx", strlen($extFx));
    errlog("INFO createGiftCard- opratrId", $opId);
//make sure that a record was put into the DB for this amount
    if ( (strlen($payer)< 2) || (($payer!= "REGRAPHARMA") && ($payer != "MAX") 
        && ($payer != "2330") && (substr($payer, 0, 9) != "MCDONALDS"))) {
       errlog("INFO createGiftCard - payer", $payer);
       sendGiftResponse(null, "", null, null, "", "", "", "", null, null, null,               null, null, null, null, 0.00, -1, 200, 
	      "INVALID MERCHANT/RETAIL PAYER", null, "REQUEST DENIED", null);
    } else if  ($base < 1.0)  { 
      errlog("INFO createGiftCard - base Currency Amount", $base);
      sendGiftResponse(null, "", null, null, "", "", "", "", null, null, null,               null, null, null, null, 0.00, -1, 100, 
	     "INVALID BASE CURRENCY AMOUNT FOR MERCHANT/RETAIL PAYER", null, 
	     "REQUEST DENIED", null);
    } else {
      $quoteCurr = 'GTQ';
// should check that the rate is the same. 
      if ($recipCntry == 'Guatemala') {
          $quoteCurr = 'GTQ';
      } else if ($recipCntry == 'El Salvadore') {
          $quoteCurr = 'USD';
      } else $quoteCurr = 'HNL';
      $delayKey = array('app' => 'MoneyAccess', 'key' => $mobile);  
      $statKey = array('app' => 'MoneyAccess', 'key' => $cardNum);
      $xA = array('sendName' => 'a0',
	  	  'senderPhone' => 'a1',
	  	  'baseCurrencyAmount' => 'a2',
	  	  'retailPayer' => 'a3',
	  	  'beneficiaryCountry' => 'a4',
	  	  'beneficiaryName' => 'a5',
	  	  'beneficiaryPhone' => 'a6',
	  	  'fxRate' => 'a7',
	  	  'quoteCurrencyAmount' => 'a8',
	  	  'quoteCurrency' => 'a9');
      $rA = array('store' => 'a10',
	  	  'tellerStation' => 'a11',
	  	  'txNumber' => 'a12',
	  	  'txDate' => 'a13',
	  	  'txCode' => 'a14',
	  	  'authorization' => 'a15');
      $ra = array('redeemerId' => 'a0');
      $delayTopObj = new TopupObj($delayKey, $xA, $rA);
      $statTopObj = new TopupObj($statKey, $xA, $rA);
      $auth = s_rand(36);
      $massagedInput = array('sendName' => $sendName,
	  	  'senderPhone' => $mobile,
	  	  'baseCurrencyAmount' => $base,
	  	  'retailPayer' => $payer,
	  	  'beneficiaryCountry' => $recipCntry,
	  	  'beneficiaryName' => $recipName,
	  	  'beneficiaryPhone' => $recipPhone,
      		  'store' => $store, 
	  	  'tellerStation' => $teller,
	  	  'txNumber' => $txNumb,
	  	  'txDate' => $txDate,
	  	  'txCode' => $txCode ,
	  	  'fxRate' => $fxRate,
	  	  'quoteCurrency' => $quoteCurr, 
	  	  'quoteCurrencyAmount' => $quoteAmt, 
	  	  'authorization' => $auth);
      $xArr = array ('a0' => $sendName,
		     'a1' => $mobile,
		     'a2' => $base,
		     'a3' => $payer,
		     'a4' => $recipCntry,
		     'a5' => $recipName,
		     'a6' => $recipPhone,
		     'a7' => $store,
		     'a8' => $teller,
		     'a9' => $txNumb,
		     'a10' => $txDate,
		     'a11' => $txCode,
		     'a12' => $fxRate,
		     'a13' => $quoteCurr,
		     'a14' => $quoteAmt,
		     'a15' => $auth,
                     'a16' => $quoteAmt,
		     'a17' => 'PURCHASED',);
      insertTransDB($statKey, $xArr);
      $statTopObj->validateReqInput($massagedInput);
      $delayTopObj->buildRspData($massagedInput);
      errlog("INFO createGiftCard - topup result:", $statTopObj->re);
      $result = $statTopObj->re;
      $statusString='Success';
// here is where we can look for the delay or abort
      errlog("INFO createGiftCard - we have the correct quote Amt", $quoteAmt);
      $raD = array();
      if ($delayTopObj->isAbort() == 'false') {
         $delayTopObj->buildRspData($raD); 
         sendGiftResponse($cardNum, $sendName, $mobile, $base, $payer, 
             $recipCntry, $recipName, $recipPhone, $fxRate, $quoteCurr, $quoteAmt,
	     $auth, $txNumb, $quoteAmt, "PURCHASED", 0, null);
      } 
   } // end of else after making sure that all params are set	
} //end of  createGiftCard

function parseAction($get, $p)
/* This function is used to make sure that all requests are directed
   appropriately to the correct routines. */
{
    errlog("parseAction: usr info - ", $get);

    $cmd=$get['cmd'];

       switch ($cmd) {

        case 'credentials':
	   changeMasterPwd($p);
           break;
        case 'new':  // for status inquiry
	   newServiceRequest($p);
           break;
        case 'get':  // return detailed info on an operation
	   findOperationInfo($p);
           break;
        case 'merchantpackages': 
	   findListOfProducts($p);
           break;
        case 'void': 
           cancelGiftCard($p);
           break;
        case 'validate':
           validateService($p);
           break;
        default:
            throw new Exception("Invalid URL $cmd", 400);
     } // end switch
}//parseAction

// The following code is the main driver that is run when a request is sent in

try {

$reqRev='';
$getRev='';

   errlog("INFO: GLOBALS: ", $GLOBALS);

if (!empty($_GET)) {
   errlog("INFO: GET: ", $_GET);
   $getRev=$_GET;
}
if (!empty($HTTP_RAW_POST_DATA)) {
   $reqRev=$HTTP_RAW_POST_DATA;
   errlog("INFO: HTTP_RAW_POST_DATA", $HTTP_RAW_POST_DATA);

}
elseif (!empty($_POST)) {
   //errlog("INFO: POST: ", $_POST);
   $reqRev=$_POST;
}


parseAction($getRev, $reqRev);

}//try
catch (Exception $e) {
   $msg=$e->getMessage();
   $code=$e->getCode();

   if ($code > 1000) $code=400;
   
   $ersp=array('status' => $code, 'message' => $msg);
   errlog("Caught exception: $msg, code= ", $code);

   sendRsp($ersp, 400);
}
?>
