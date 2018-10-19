<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__));
$partnerKey='idt-quippi-session';

include '../ezetop/ImtuTopUpClass.php';
include '../QAlib/wslib1.php';

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

function sendJSONRsp($a, $code=200)
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

function sendRsp($a, $code=200)
/* This is used every time a JSON response is sent */
{
    $ht = new HttpResponse();
    $ht->status($code);
    $ht->setContentType('application/xml');
    $ht->setData($a);
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

function sendUnspecifiedError() {
/* Since the user should always query for the rate before specifying that 
   thaey want to create a Pin with a specified rate, the system will come  
   back with this error if the DB finds that the rate speicified is not the
   rate quoted by Quippi for that amount */
  $rsp = new SimpleXMLElement('<quippiApi/>');
  $rsp->addChild('operationStatus', 1);
  $rsp->addChild('discriptiion', 'Unspecified error');
  errlog ("xml to send", $rsp->asXML());
  sendRsp($rsp->asXML());
}

function sendAuthError() {
/* We only check that the signature is correct when sending the exchange
   rate request.  The signature wil be checked everytime in the real Quippi
   web service but since this one is fake, we can do what we want, to some 
   extent.  So, this will only be called from getXchgRate  */
  $rsp = new SimpleXMLElement('<quippiApi/>');
  $rsp->addChild('operationStatus', 2);
  $rsp->addChild('discriptiion', 'Authentication error (the signature is not valid)');
  errlog ("xml to send", $rsp->asXML());
  sendRsp($rsp->asXML());
}

function sendDBError() {
/* This should be called whenever a DB error is noticed.  However, we only
   call it when we notice a DB error inside of the cancelPin routine */
  $rsp = new SimpleXMLElement('<quippiApi/>');
  $rsp->addChild('operationStatus', 3);
  $rsp->addChild('discriptiion', 'Database originated error');
  errlog ("xml to send", $rsp->asXML());
  sendRsp($rsp->asXML());
}

function sendInvalidParmError() {
/* This should be called whenever a parameter is found to be invalid. As it 
   stands now, the only time that the simulator looks for that is inside of
   processPinReq */
  $rsp = new SimpleXMLElement('<quippiApi/>');
  $rsp->addChild('operationStatus', 4);
  $rsp->addChild('discriptiion', 'Invalid parameter(s). One or more of the sent parameters is not valid.'); 
  errlog ("xml to send", $rsp->asXML());
  sendRsp($rsp->asXML());
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

function getXchgRate($ap)
/*  If the signature is correct then we figure out the rate and put it into 
the DB as well as send it back.  We put it into the DB so taht when someone
asks for a particular rate, we know that Quippi sent the that rate.*/
{
    errlog("INFO - getXchgRate", $ap);
    $partnerSecret='IDT-QUIPPI-SESSION used for signing the request';
    //parse_str($ap, $p);
    $URLString='/product/v1/getQuippiFx';
    //$time=time();
    errlog("INFO getXchgRate: usr info - ", $ap);
    $key = $ap['partnerKey'];
    $time = $ap['timestamp'];
    $timeString = 'timestamp=' . $time;
    $SHAString = $URLString . $timeString;
    $signatureIn = $ap['signature'];
    $denom = $ap['sendingAmount'];
    $amtString='sendingamount=' . $denom;
    $partnerString = 'partnerkey=' . $key; 
    $SHAString = "$URLString" .$amtString.$partnerString.$timeString
		 .$partnerSecret;
    errlog("INFO getXchgRate- SHA String:", $SHAString);
    $sigOut = strtolower(sha1($SHAString));
    errlog("INFO getXchgRate- signature to be:", strtolower(sha1($SHAString)));
    //if (strcasecmp($signatureIn,  strtolower(sha1($SHAString))) !=0) {
     //  errlog("INFO signature test did not pass. Should be $sigOut was ", $signatureIn);
      // sendAuthError();
    /*} else */
    if ( (!isset($denom)) || (!isset($key))) {
        errlog("INFO getXchgRate- denom:", $denom);
        errlog("INFO getXchgRate- key:", $key);
	sendInvalidParmError();
    } else if  ((!isset($time)) || (!isset($signatureIn))) { 
        errlog("INFO getXchgRate- time:", $time);
        errlog("INFO getXchgRate- signatureIn:", $signatureIn);
	sendInvalidParmError();
    } else if (($denom == "5.00") || ($denom== "25.00") || ($denom == "50.00") || ($denom == "100.00")) {  
         errlog("INFO signature test passed. ", $signatureIn);
         $key = array('app' => 'Quippi', 'key' => $denom);
         $rate = 14 - f_rand();
         $xA=array('a0' => $time,
	        'a1' => $denom, // in USD
                'a2' => $rate);
         $xmld = getDB('Quippi', $denom);  
         if (isset($xmld)) {
            deleteTransDBRec($key);
         }
         insertTransDB($key, $xA); //Add a record into the DB for this Pin request 
         //sendXMLrate($denom);
         errlog("INFO about to send rate via xml", $rate);
         sendRate($denom, $rate);
       } else {
         sendUnspecifiedError();
       }
} // end of getXchgRate 

function sendRate($denomUS, $rate) 
/*  This should be called from getXchgRate after deciphering whether the 
    Accept HTTP request header specified an XML response or a JSON response.
    Since I have not figured out how to do that yet, we only send JSON for now. 
*/
{
  $rsp = new SimpleXMLElement('<quippiApi/>');
  $rsp->addChild('operationStatus', 0);
  $rsp->addChild('denomUSD', $denomUS);
  $rsp->addChild('fxRate', $rate);
  $rsp->addChild('targetAmount', round($rate*$denomUS,2));
  errlog ("xml to send", $rsp->asXML());
  sendRsp($rsp->asXML());
}

function getPinStatus ($ap) 
/*  This routine can be called at any time.  It is used to look into the DB 
    to see the status of a Pin.  There are 3 possible statuses: ACTIVE, 
    INVALID and REDEEMED.  However, since there is no way to know when a
    card has been redeemed, the end resulting status is either ACTIVE or
    INVALID. */
{
    errlog("INFO getPinStatus - ", $ap);
    $key = $ap['partnerKey'];
    errlog("INFO getPinStatus - key:", $key);
    $time = $ap['timestamp'];
    errlog("INFO getPinStatus - timestamp", $time);
    $signatureIn = $ap['signature'];
    errlog("INFO getPinStatus - signature", $signatureIn);
    $orderID = $ap['partnerOrderId'];
    errlog("INFO getPinStatus - orderID", $orderID);
    $pin= "";
    if (isset($ap['pin'])) {
       $pin = $ap['pin'];
    }
    $pan = "";
    if (isset($ap['pan'])) {
       $pan = $ap['pan'];
    }
    errlog("INFO getPinStatus - pin ", $pin);
    errlog("INFO getPinStatus - pan ", $pan);
    if ( (!isset($time)) || (!isset($key))) {
	sendInvalidParmError();
    } else if ( (!isset($signatureIn)) || (!isset($orderID))) {
	sendInvalidParmError();
    } else {
      $key = array ('app' => 'Quippi', 'key' => $orderID);
      //$xmld = getDB('Quippi', $orderID);  
      //if (isset($xmld)) 
// should check that the rate is the same. 
        $xa=array('redeemerId' => 'a0',
	      'amount' => 'a1', // in USD
              'rate' => 'a2',
	      'time' => 'a3',
	      'senderFirstName' => 'a4',
	      'senderLastName' => 'a5',
	      'senderMobile' => 'a6',
	      'recipientFirstName' => 'a7',
	      'recipientLastName' => 'a8',
	      'status' => 'a9',
	      'pin' => 'a10');
        $ra = array();
        $tpObj = new TopupObj($key, $xa, $ra);
        if ((strlen($pin) > 0) && ($tpObj->xe['pin'] != $pin)) {
            sendPinError();
        } else if (isset($tpObj->xe['senderFirstName'])) {
          sendXMLStatusResponse($tpObj->xe['pin'], $tpObj->xe['pin']*2, $tpObj->xe['redeemerId'], $tpObj->xe['amount'], 
	        'MXN', //$tpObj->xe[rate], 
		$tpObj->xe['status'], round($tpObj->xe[amount]*$tpObj->xe[rate],2),
		$tpObj->xe['senderFirstName'], $tpObj->xe['senderLastName'], $tpObj->xe['senderMobile'], 
		$tpObj->xe['recipientFirstName'], $tpObj->xe['recipientLastName'], $tpObj->xe['pin']*3);
        } else { // if there was no matching record for that orderID 
          sendInvalidParmError();
        }
   }  // end of else where all params were sent correcltly 
}  // end of getPinStatus 

function cancelPin($ap)
/*  When the user sends a request to cancel a pin, this routine will be called.
    It first looks in the DB to make sure that a record is there for that order
    and if it is found, change the status in the Db abd send out the appropriate
    response.  If the record is not found an error response is sent out.  */
{
    errlog("INFO cancelPin- ", $ap);
    errlog("INFO : cancelPin usr info - ", $p);
    $key = $ap['partnerKey'];
    errlog("INFO cancelPin - key:", $key);
    $time = $ap['timestamp'];
    errlog("INFO cancelPin - timestamp", $time);
    $signatureIn = $ap['signature'];
    errlog("INFO cancelPin - signature", $signatureIn);
    $orderID = $ap['partnerOrderId'];
    errlog("INFO cancelPin - orderID", $orderID);
    $pin = $ap['pin'];
    errlog("INFO cancelPin - pin ", $pin);
    $pan = $ap['pan'];
    errlog("INFO cancelPin - pan ", $pan);
    if ( (!isset($time)) || (!isset($signatureIn))) {
	sendInvalidParmError();
    } else if  ((!isset($key)) || (!isset($orderID))) { 
        errlog("INFO cancelPin - key or orderID not set", $ap);
	sendInvalidParmError();
    } else if  ((!isset($pin)) || (!isset($pan))) { 
	sendInvalidParmError();
    } else {
      $xmld = getDB('Quippi', $orderID);  
      if (isset($xmld)) {
// should check that the rate is the same. 
        $xa = array('redeemerId' => 'a0',
		    'amount' => 'a1',
		    'rate' => 'a2',
		    'time' => 'a3',
		    'senderFirstName' => 'a4',
		    'senderLastName' => 'a5',
		    'senderMobile' => 'a6',
		    'recipientFirstName' => 'a7',
		    'recipientLastName' => 'a8',
		    'status' => 'a9',
		    'pin' => 'a10');
        $ra = array();
        $xA = array();
        $xe = array();
	$ca=array_merge($xa,$ra, array('delay'=>'delay', 'abort'=>'abort'));
        errlog("INFO cancelPin - ca", $ca);
        errlog("INFO cancelPin - xml", $xmld);
        $xA=retrievXmlParm2A($xmld, $ca);
        errlog("INFO cancelPin - xA from xml", $xA);
	foreach($xa as $key => $val) {
    	         //errlog("INFO cancelPin - xA key: $key  val: ", $val);
              if (array_key_exists($val, $xA))  {
    	         //errlog("INFO cancelPIN - found array key: $val for key:", $key);
                 if (isset($xA[$val])) {
    	            //errlog("INFO cancelPin - setting xe[$key] to: ", $xA[$val]);
                    $xe[$key]=$xA[$val];
          	    } // end if
                 } // end if array_key_exists
         } // end foreach loop
         if (($xe['status'] == 'ACTIVE') && ($pin == $xe['pin'])) {
            sendXMLCancelResponse($pin, $pan, $xe[redeemerId], $xe[amount], 'USD', 
		$xe[senderFirstName], $xe['senderLastName'], 
		$xe['recipientFirstName'], $xe['recipientLastName'], $pin*3);
           $xANew=array('a0' => $xe[redeemerId],
	      'a1' => $xe[amount], // in USD
              'a2' => $xe[rate],
	      'a3' => $xe[time],
	      'a4' => $xe[senderFirstName],
	      'a5' => $xe[senderLastName],
	      'a6' => $xe[recipientFirstName],
	      'a7' => $xe[recipientLastName],
	      'a8' => 'INVALID',
	      'a9' => $pin);
            updateTransDBRec($orderID, $xANew); //Add a record into the DB for this Pin request
          } else if ($pin != $xe['pin']) { 
	           // sendInvalidParmError();
	            sendAuthError(); 
	  } else {// status was not ACTIVE
              sendGiftRedeemedError();
          }
       } else { // no DB record found for this orderID
         sendDBError();
       }
    } // found the record for that order
} // end of cancelPin


//function updateTransDBRec($k, $xmlArr)
/* When a pin is cancelled a record in the DB needs to be updated with the 
   correct status.  Since there was no other routine t update a DB record and
   the update is very specific to the data that is already there and the 
   specific situation where a Pin is being cancelled for Quippi, it seemed
   that this routine belonged here instead of with the other DB 
   manipulation routines in ImtuTopUpClass. */
/*{
        errlog("Top of updateTransDBRec with key=", $k);
        errlog("Top of updateTransDBRec with xmlArr=", $xmlArr);
        $dbhandle=new SQLite3('/u/debit/apps/htdocs/QAdata/db/QAImtu.db', SQLITE3_OPEN_READWRITE);
        if (!$dbhandle) {
           errlog("WARN: updateTransDBRec connection failed", $dbhandle);
        return FALSE;
        }
        $xmlStr = "<qryparams>";
        errlog("Initialized xmlStr=", $xmlStr);
        foreach ($xmlArr as $key => $val) {
                $myString = sprintf("INFO: inserting TransDB key= '%s' + val='%s'", $key, $val);
                $xmlStr .= "<" . $key . ">" . $val . "</" . $key . ">";
                errlog($myString, $xmlStr);
                }
        $xmlStr .= "<delay>0</delay><abort>false</abort></qryparams>";
        $stmt = "UPDATE xmlData SET xml='" . $xmlStr . "' WHERE app='Quippi' and key='" . $k . "'";
//        $stmt .= $xmlStr . "', date('now'), NULL)";
        $ok = $dbhandle->exec($stmt);
        if (!$ok) {
            errlog("ERROR: Cannot update Trans into DB using: ", $stmt);
        } else {
            errlog("INFO: updated Trans into DB", $stmt);
        }
        sqlite_close($dbhandle);
} */ // end of updateTransDBRec

function sendJSONCancelResponse($pin, $pan, $redeemerId, $amount, $curr, 
		$senderFirstName, $senderLastName, 
		$recipientFirstName, $recipientLastName, $orderID)
{
  $rsp=array('operationStatus' => 0,
	     'pin' => $pin,
	     'pan' => $pan,
	     'redeemerId' => $redeemerId,
             'amount' => $amount,
  	     'currency' => $curr,
	     'senderFirstName' => $senderFirstName,
	     'senderLastName' => $senderLastName,
	     'recipientFirstName' => $recipientFirstName,
	     'recipientLastName' => $recipientLastName,
	     'quippiOrderId' => $orderID);
  sendRsp($rsp); 
}

function sendXMLCancelResponse($pin, $pan, $redeemerId, $amount, $curr, 
		$senderFirstName, $senderLastName, 
		$recipientFirstName, $recipientLastName, $orderID)
{
  $rsp = new SimpleXMLElement('<quippiApi/>');
  $rsp->addChild('operationStatus', 0);
  $rsp->addChild('pin', $pin);
  $rsp->addChild('pan', $pan);
  $rsp->addChild('redeemerId', $redeemerId);
  $rsp->addChild('amount', $amount);
  $rsp->addChild('currency', $curr);
  $rsp->addChild('senderFirstName', $senderFirstName);
  $rsp->addChild('senderLastName', $senderLastName);
  $rsp->addChild('recipientFirstName', $recipientFirstName);
  $rsp->addChild('recipientLastName', $recipientLastName);
  $rsp->addChild('quippiOrderId', $orderID);
  errlog ("xml to send", $rsp->asXML());
  sendRsp($rsp->asXML());
}

function sendJSONStatusResponse($pin, $pan, $redeemerId, $amount, $curr,
		$rate, $status, $GCAmountInMXN,  
		$senderFirstName, $senderLastName, 
		$recipientFirstName, $recipientLastName, $orderID)
/* When a pin is cancelled and the state of the system is ready for that 
   this routine is called to send a valid response that confirms that the 
   cancellation was done. */
{
  $rsp=array('operationStatus' => 0,
	     'pin' => $pin,
	     'pan' => $pan,
	     'giftCardStatus' => $status,
	     'redeemerId' => $redeemerId,
             'sendingAmount' => $amount,
  	     'currency' => $curr,
	     'fxRate' => $rate,
	     'targetAmount' => $GCAmountInMXN * 100,
	     'senderFirstName' => $senderFirstName,
	     'senderLastName' => $senderLastName,
	     'recipientFirstName' => $recipientFirstName,
	     'recipientLastName' => $recipientLastName,
	     'quippiOrderId' => $orderID);
  sendRsp($rsp); 
}

function sendXMLStatusResponse($pin, $pan, $redeemerId, $amount, $curr,
	//	$rate, 
		$status, $GCAmountInMXN,  
		$senderFirstName, $senderLastName, $senderMobile, 
		$recipientFirstName, $recipientLastName, $orderID)
/* When a pin is cancelled and the state of the system is ready for that 
   this routine is called to send a valid response that confirms that the 
   cancellation was done. */
{
  $rsp = new SimpleXMLElement('<quippiApi/>');
  $rsp->addChild('operationStatus', 0);
  $rsp->addChild('pin', $pin);
  $rsp->addChild('pan', $pan);
  $rsp->addChild('giftCardStatus', $status);
  $rsp->addChild('redeemerId', $redeemerId);
  $rsp->addChild('amount', $amount);
  $rsp->addChild('currency', $curr);
  //$rsp->addChild('fxRate', $rate);
  $rsp->addChild('targetAmount', $GCAmountInMXN*100);
  $rsp->addChild('senderFirstName', $senderFirstName);
  $rsp->addChild('senderLastName', $senderLastName);
  $rsp->addChild('senderMobile', $senderMobile);
  $rsp->addChild('recipientFirstName', $recipientFirstName);
  $rsp->addChild('recipientLastName', $recipientLastName);
  $rsp->addChild('quippiOrderId', $orderID);
  errlog ("xml to send", $rsp->asXML());
  sendRsp($rsp->asXML());
}

function processPinReq($ap)
/* This routine is called to start up a new order after a request was sent
   to discover the current exchange rate. So, this routine verifies that
   there is a record added to the DB for the amount in question.  It could 
   be improved by checking the date as well....  */
{
    errlog("INFO - processPinReq input array: :q!", $ap);
    //parse_str($ap, $p);
    $key = "";
    if (isset($ap['partnerKey'])) {
       $key = $ap['partnerKey'];
    }
    $signatureIn = "";
    if (isset($ap['signature'])) {
       $signatureIn = $ap['signature'];
    }
    errlog("INFO processPinReq - key:", $key);
    $time = "";
    if (isset($ap['timestamp'])) {
       $time = $ap['timestamp'];
    }
    $orderID = "";
    if (isset($ap['partnerOrderId'])) {
       $orderID = $ap['partnerOrderId'];
    }
    $redeemer = "";
    if (isset($ap['redeemerId'])) {
       $redeemer = $ap['redeemerId'];
    }
    $sendAmt = "";
    if (isset($ap['sendingAmount'])) {
       $sendAmt = $ap['sendingAmount'];
    }
    $mobile = "";
    if (isset($ap['senderMobile'])) {
       $mobile = $ap['senderMobile'];
    }
    $sFirst= "";
    if (isset($ap['senderFirstName'])) {
       $sFirst= $ap['senderFirstName'];
    }
    $sLast= "";
    if (isset($ap['senderLastName'])) {
       $sLast= $ap['senderLastName']; 
    }
    $rFirst= "";
    if (isset($ap['recipientFirstName'])) {
       $rFirst = $ap['recipientFirstName'];
    }
    $rLast = "";
    if (isset($ap['recipientLastName'])) {
       $rLast = $ap['recipientLastName'];
    }
    //    errlog("INFO processPinReq - extFx with len $extFx", strlen($extFx));
    $termId = "";
    if (isset($ap['terminalID'])) {
       $termId = $ap['terminalID'];
    }
    $opId = "";
    if (isset($ap['operatorID'])) {
       $opId = $ap['operatorID'];
    }
    $extFx = "";
    if (isset($ap['externalFx'])) {
       $extFx = $ap['externalFx'];
    }
    errlog("INFO processPinReq - opratrId", $opId);
//make sure that a record was put into the DB for this amount
    if ( (strlen($redeemer)< 2) || (strlen($sFirst)<2)) {
        errlog("INFO processPinReq - redeemer", $redeemer);
        errlog("INFO processPinReq - sFirst", $sFrist);
	sendInvalidParmError();
    } else if  ((strlen($sLast)<2) || (strlen($rFirst)<2)) { 
        errlog("INFO processPinReq - rFirst", $rFirst);
        errlog("INFO processPinReq - sLast", $sLast);
	sendInvalidParmError();
    } else if ( (strlen($rLast)<2) || (strlen($mobile)<2)) { 
        errlog("INFO processPinReq - rLast", $rLast);
        errlog("INFO processPinReq - mobile", $mobile);
	sendInvalidParmError();
    } else if ( (strlen($key)<2) || (strlen($time)<2)) { 
        errlog("INFO processPinReq - time", $time);
        errlog("INFO processPinReq - key", $key);
	sendInvalidParmError();
    } else if ( (strlen($signatureIn)<2) || (strlen($orderID)<2)) { 
        errlog("INFO processPinReq - sign", $signatureIn);
        errlog("INFO processPinReq - oID", $orderID);
	sendInvalidParmError();
    } else if (strlen($sendAmt)<2)  {
	sendInvalidParmError();
    } else {
      $key = array('app' => 'Quippi', 'key' => $sendAmt);  
// should check that the rate is the same. 
      $xa = array('redeemerId' => 'a0',
	 	  'amount' => 'a1',
		  'rate' => 'a2');
      $ra = array('redeemerId' => 'a0');
      $tpObj = new TopupObj($key, $xa, $ra);
      $extFx = $tpObj->xe['rate'];
      $rateInput = array('rate' => $extFx, 'amount' => $sendAmt);
      errlog("INFO processPinReq - set input array", $rateInput);
      //$tpObj->validateReqInput($rateInput);
      if ($extFx == $tpObj->xe['rate']) {
         $statusString='Success';
         $result = $tpObj->re; 
// here is where we can look for the delay or abort
      errlog("INFO processPinReq - we have the correct rate", $rateInput);
      errlog("INFO processPinReq - sendAmt", $sendAmt);
      errlog("INFO processPinReq - redeemerId", $redeemerId);
          
         if (($sendAmt == "5.00") || ($sendAmt == "25.00") || ($sendAmt == "50.00") || ($sendAmt == "100.00") || 
(($sendAmt == "150.00") && ($redeemerId=="coppel")) ||
(($sendAmt == "150.00") && ($redeemerId=="office-depot-mexico")) ||
(($sendAmt == "200.00") && ($redeemerId=="office-depot-mexico")) ||
(($sendAmt == "200.00") && ($redeemerId=='coppel')) ||
(($sendAmt == "250.00") && ($redeemerId=='office-depot-mexico')) ||
(($sendAmt == "250.00") && ($redeemerId=='coppel'))) 
         {
             $key = array('app' => 'Quippi', 'key' => $orderID);
             $keyD = array('app' => 'Quippi', 'key' => $mobile);
             $xaD = array('redeemerId' => 'a0',
			'externalFx' => 'a1',
			'sendingAmount' => 'a2',
			'senderFirstName' => 'a3',
			'senderLastName' => 'a4',
			'recipientFirstName' => 'a5',
			'recipientLastName' => 'a6');
             $raD = array();
             $tpDelayObj = new TopupObj($keyD, $xaD, $ra);
             $tpDelayObj->validateReqInput($ap);
             $pin = i_rand(1,50000);
             if ($tpDelayObj->isAbort() == 'false') {
	     $xA=array('a0' => $redeemer,
	      'a1' => $sendAmt, // in USD
              'a2' => $extFx,
	      'a3' => $time,
	      'a4' => $sFirst,
	      'a5' => $sLast,
	      'a6' => $mobile,
	      'a7' => $rFirst,
	      'a8' => $rLast,
	      'a9' => 'ACTIVE',
	      'a10' => $pin);
             insertTransDB($key, $xA); //Add a record into the DB for this Pin request 
             $tpDelayObj->buildRspData($raD); 
             sendXMLCreateResponse($redeemer,$extFx, $sendAmt, $sFirst, $sLast, $rFirst, $rLast, $mobile, $pin);
      } } else {
        sendUnspecifiedError();
      } 
	 } else { // found wrong amount record in the DB
             errlog("INFO processPinReq - rate expected: $extFx, rate in DB", $tpObj->xe['rate']);
	     sendInvalidParmError();
         } 
   } // end of else after making sure that all params are set	
} //end of  processPinReq  

function sendXMLCreateResponse($redeemer, $rate, $Amt, $sFirst, $sLast, $rFirst, $rLast,
				$mobile, $pin)
/* This routine sends a response to the createPin request when the request
   was preceded by the appropriate request for the current exchange rate. */
{
  $rsp = new SimpleXMLElement('<quippiApi/>');
  $rsp->addChild('operationStatus', 0);
  $rsp->addChild('retailer', 'Walmart');
  $rsp->addChild('fxRate', $rate);
  $rsp->addChild('targetAmount', round($rate * $Amt,2));
  $rsp->addChild('sendingAmount', $Amt);
  $rsp->addChild('currency', 'MXN');
  $rsp->addChild('pin', $pin);
  $rsp->addChild('pan', $pin*2);
  $rsp->addChild('redeemerId', $redeemer);
  $rsp->addChild('senderFirstName', $sFirst);
  $rsp->addChild('senderLastName', $sLast);
  $rsp->addChild('senderMobile', $mobile);
  $rsp->addChild('recipientiFirstName', $rFirst);
  $rsp->addChild('recipientiLastName', $rLast);
  $rsp->addChild('quippiOrderId', $pin*3);
  errlog ("xml to send", $rsp->asXML());
  sendRsp($rsp->asXML());
}

function sendJSONCreateResponse($redeemer, $rate, $Amt, $sFirst, $sLast, $rFirst, $rLast,
				$mobile, $pin)
/* This routine sends a response to the createPin request when the request
   was preceded by the appropriate request for the current exchange rate. */
{
  $rsp=array('operationStatus' => 0,
             'retailer' => 'Walmart',
  	     'fxRate' => $rate,
  	     'sendingAmount' => round($rate*$Amt,2),
	     'currency' => 'MXN',
	     'PIN' => $pin,
	     'PAN' => $pin*2,
	     'redeemerId' => $redeemer,
	     'senderFirstName' => $sFirst,
	     'senderLastName' => $sLast,
	     'senderMobile' => $mobile,
	     'recipientFirstName' => $rFirst,
	     'recipientLastName' => $rLast,
	     'quippiOrderId' => $pin*3);
  sendRsp($rsp); 
}

function parseAction($get, $p)
/* This function is used to make sure that all requests are directed
   appropriately to the correct routines. */
{
    errlog("parseAction: usr info - ", $get);

    $cmd=$get['cmd'];
    $subcmd=$get['subcmd'];

    if ($cmd == 'product') { 
       switch ($subcmd) {

        case 'getQuippiFx':
	   getXchgRate($p);
           break;
        case 'createPin': 
	   processPinReq($p);
           break;
        case 'cancelPin': 
              cancelPin($p);
              break;
        case 'productStatus':
              getPinStatus($p);
              break;
        default:
            throw new Exception("Invalid URL $cmd", 400);
     } // end switch
  } // end if
}//parseAction

// The following code is the main driver that is run when a request is sent in

try {

$reqRev='';
$getRev='';

//   errlog("INFO: GLOBALS: ", $GLOBALS);

if (!empty($_GET)) {
   errlog("INFO: GET: ", $_GET);
   $getRev=$_GET;
}
if (!empty($HTTP_RAW_POST_DATA)) {
   $reqRev=$HTTP_RAW_POST_DATA;
   errlog("INFO: HTTP_RAW_POST_DATA", $HTTP_RAW_POST_DATA);

}
elseif (!empty($_POST)) {
   errlog("INFO: POST: ", $_POST);
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
