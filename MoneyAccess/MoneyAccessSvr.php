<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__));

#include '../ezetop/ImtuTopUpClass.php';
include 'ImtuTopUpClass.php';
include '../QAlib/wslib1.php';
#include 'wslib1.php';
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

function getXchgRate($ap)
/*  If the signature is correct then we figure out the rate and put it into 
the DB as well as send it back.  We put it into the DB so taht when someone
asks for a particular rate, we know that Quippi sent the that rate.*/
{
    errlog("INFO - getXchgRate", $ap);
    $country = $ap['countryName'];
    $curr = $ap['baseCurrency'];
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

function findMerchantInfo($ap)  
{
  errlog("INFO findMerchantInfo- ", $ap);
  $merch1 = array('code' => "001",
  		  'merchantName' => "REGRAPHARMA",
  		  'countryName' => "GUATEMALA",
  		  'alpha2Code' => "GT",
  		  'resultCode' => null,
  		  'description' => null,
  		  'retailPayerPackageList' => null);
  $mercArray = array($merch1);
  $rsp = array ('resultCode' => "0",
  		'description' => "",
  		'merchants' => $mercArray);
  errlog ("findMerchantInfo: rsp to send", $rsp);
  sendRsp($rsp);
}

function findListOfProducts($ap) {
  errlog("INFO findListOfProducts - ", $ap);
  $merchant = $ap['merchant'];
  $prod1 = array('code' => "1001",
  		 'description' => "VALE CONSULTA REDIMIBLE POR EL MONTO TOTAL",
		 'shortDescription' => "CONSULTA MEDICA",
	         'baseCurrencyAmount' => 20.00,
		 'quoteCurrencyAmount' => 150.00,
		 'fxRate' => 7.5000,
		 'merchantName' => "REGRAPHARMA");
  $prod2 = array('code' => "1002",
  		 'description' => "VALE CONSULTA REDIMIBLE POR EL MONTO TOTAL",
		 'shortDescription' => "CONSULTA MEDICA",
	         'baseCurrencyAmount' => 30.00,
		 'quoteCurrencyAmount' => 225.00,
		 'fxRate' => 7.5000,
		 'merchantName' => "REGRAPHARMA");
  $prod3 = array('code' => "1003",
  		 'description' => "VALE CONSULTA REDIMIBLE POR EL MONTO TOTAL",
		 'shortDescription' => "CONSULTA MEDICA",
	         'baseCurrencyAmount' => 40.00,
		 'quoteCurrencyAmount' => 300.00,
		 'fxRate' => 7.5000,
		 'merchantName' => "REGRAPHARMA");
  $prod4 = array('code' => "1004",
  		 'description' => "VALE CONSULTA REDIMIBLE POR EL MONTO TOTAL",
		 'shortDescription' => "CONSULTA MEDICA",
	         'baseCurrencyAmount' => 50.00,
		 'quoteCurrencyAmount' => 375.00,
		 'fxRate' => 7.5000,
		 'merchantName' => "REGRAPHARMA");
  $prod5 = array('code' => "1005",
  		 'description' => "VALE CONSULTA REDIMIBLE POR EL MONTO TOTAL",
		 'shortDescription' => "CONSULTA MEDICA",
	         'baseCurrencyAmount' => 60.00,
		 'quoteCurrencyAmount' => 450.00,
		 'fxRate' => 7.5000,
		 'merchantName' => "REGRAPHARMA");
  $prod6 = array('code' => "1006",
  		 'description' => "VALE CONSULTA REDIMIBLE POR EL MONTO TOTAL",
		 'shortDescription' => "CONSULTA MEDICA",
	         'baseCurrencyAmount' => 70.00,
		 'quoteCurrencyAmount' => 525.00,
		 'fxRate' => 7.5000,
		 'merchantName' => "REGRAPHARMA");
  $prod7 = array('code' => "1007",
  		 'description' => "VALE CONSULTA REDIMIBLE POR EL MONTO TOTAL",
		 'shortDescription' => "CONSULTA MEDICA",
	         'baseCurrencyAmount' => 80.00,
		 'quoteCurrencyAmount' => 600.00,
		 'fxRate' => 7.5000,
		 'merchantName' => "REGRAPHARMA");
  $prod8 = array('code' => "1008",
  		 'description' => "VALE CONSULTA REDIMIBLE POR EL MONTO TOTAL",
		 'shortDescription' => "CONSULTA MEDICA",
	         'baseCurrencyAmount' => 90.00,
		 'quoteCurrencyAmount' => 675.00,
		 'fxRate' => 7.5000,
		 'merchantName' => "REGRAPHARMA");
  $prod9 = array('code' => "1009",
  		 'description' => "VALE CONSULTA REDIMIBLE POR EL MONTO TOTAL",
		 'shortDescription' => "CONSULTA MEDICA",
	         'baseCurrencyAmount' => 100.00,
		 'quoteCurrencyAmount' => 750.00,
		 'fxRate' => 7.5000,
		 'merchantName' => "REGRAPHARMA");
  $prod10 = array('code' => "1010",
  		 'description' => "VALE CONSULTA REDIMIBLE POR EL MONTO TOTAL",
		 'shortDescription' => "CONSULTA MEDICA",
	         'baseCurrencyAmount' => 110.00,
		 'quoteCurrencyAmount' => 825.00,
		 'fxRate' => 7.5000,
		 'merchantName' => "REGRAPHARMA");
  $prod11 = array('code' => "1011",
  		 'description' => "VALE CONSULTA REDIMIBLE POR EL MONTO TOTAL",
		 'shortDescription' => "CONSULTA MEDICA",
	         'baseCurrencyAmount' => 120.00,
		 'quoteCurrencyAmount' => 900.00,
		 'fxRate' => 7.5000,
		 'merchantName' => "REGRAPHARMA");
  $prod12 = array('code' => "1012",
  		 'description' => "VALE CONSULTA REDIMIBLE POR EL MONTO TOTAL",
		 'shortDescription' => "CONSULTA MEDICA",
	         'baseCurrencyAmount' => 130.00,
		 'quoteCurrencyAmount' => 975.00,
		 'fxRate' => 7.5000,
		 'merchantName' => "REGRAPHARMA");
  $prod13 = array('code' => "1013",
  		 'description' => "VALE CONSULTA REDIMIBLE POR EL MONTO TOTAL",
		 'shortDescription' => "CONSULTA MEDICA",
	         'baseCurrencyAmount' => 140.00,
		 'quoteCurrencyAmount' => 1050.00,
		 'fxRate' => 7.5000,
		 'merchantName' => "REGRAPHARMA");
  $prod14 = array('code' => "1014",
  		 'description' => "VALE CONSULTA REDIMIBLE POR EL MONTO TOTAL",
		 'shortDescription' => "CONSULTA MEDICA",
	         'baseCurrencyAmount' => 150.00,
		 'quoteCurrencyAmount' => 1125.00,
		 'fxRate' => 7.5000,
		 'merchantName' => "REGRAPHARMA");
  $prod15 = array('code' => "1015",
  		 'description' => "VALE CONSULTA REDIMIBLE POR EL MONTO TOTAL",
		 'shortDescription' => "CONSULTA MEDICA",
	         'baseCurrencyAmount' => 160.00,
		 'quoteCurrencyAmount' => 1200.00,
		 'fxRate' => 7.5000,
		 'merchantName' => "REGRAPHARMA");
  $prod16 = array('code' => "1016",
  		 'description' => "VALE CONSULTA REDIMIBLE POR EL MONTO TOTAL",
		 'shortDescription' => "CONSULTA MEDICA",
	         'baseCurrencyAmount' => 170.00,
		 'quoteCurrencyAmount' => 1275.00,
		 'fxRate' => 7.5000,
		 'merchantName' => "REGRAPHARMA");
  $prod17 = array('code' => "1017",
  		 'description' => "VALE CONSULTA REDIMIBLE POR EL MONTO TOTAL",
		 'shortDescription' => "CONSULTA MEDICA",
	         'baseCurrencyAmount' => 180.00,
		 'quoteCurrencyAmount' => 1350.00,
		 'fxRate' => 7.5000,
		 'merchantName' => "REGRAPHARMA");
  $prod18 = array('code' => "1018",
  		 'description' => "VALE CONSULTA REDIMIBLE POR EL MONTO TOTAL",
		 'shortDescription' => "CONSULTA MEDICA",
	         'baseCurrencyAmount' => 190.00,
		 'quoteCurrencyAmount' => 1425.00,
		 'fxRate' => 7.5000,
		 'merchantName' => "REGRAPHARMA");
  $prod19 = array('code' => "1019",
  		 'description' => "VALE CONSULTA REDIMIBLE POR EL MONTO TOTAL",
		 'shortDescription' => "CONSULTA MEDICA",
	         'baseCurrencyAmount' => 200.00,
		 'quoteCurrencyAmount' => 1500.00,
		 'fxRate' => 7.5000,
		 'merchantName' => "REGRAPHARMA");
  $prodArr= array($prod1, $prod2, $prod3, $prod4, $prod5, $prod6, $prod7,
		  $prod8, $prod9, $prod10, $prod11, $prod12, $prod13, $prod14,
  		  $prod15, $prod16, $prod17, $prod18, $prod19);
  errlog ("findListOfProducts: rsp to send", $prodArr);
  sendRsp($prodArr);
} // findListOfProducts 

function getGiftCardInfo($ap) 
/*  This routine can be called at any time.  It is used to look into the DB 
    to see the status of a Pin.  There are 3 possible statuses: ACTIVE, 
    INVALID and REDEEMED.  However, since there is no way to know when a
    card has been redeemed, the end resulting status is either ACTIVE or
    INVALID. */
{
    errlog("INFO getGiftCardInfo - ", $ap);
    $card = "";
    if (isset($ap['eGiftcardNumber'])) {
       $card = $ap['eGiftcardNumber'];
    }
    errlog("INFO getGiftCardInfo - card ", $card);
    $key = array ('app' => 'MoneyAccess', 'key' => $card);
      $xmld = getDB('MoneyAccess', $card);  
      if ($xmld) {
// should check that the rate is the same. 
      $xA = array('sendName' => 'a0',
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
	  	  'txCode' => 'a11',
	  	  'fxRate' => 'a12',
	  	  'quoteCurr' => 'a13',
	  	  'quoteAmt' => 'a14',
	  	  'authorization' => 'a15',
                  'balance' => 'a16',
		  'status' => 'a17');
      $rA = array();
        $tpObj = new TopupObj($key, $xA, $rA);
        if (isset($tpObj->xe['sendName'])) {

           sendStatusResponse($card, $tpObj->xe['sendName'], 
		$tpObj->xe['senderPhone'], $tpObj->xe['baseCurrencyAmount'], 
		$tpObj->xe['retailPayer'], 
		$tpObj->xe['beneficiaryName'], $tpObj->xe['beneficiaryPhone'], 
		$tpObj->xe['beneficiaryCountry'], $tpObj->xe['beneficiaryPhone'], 
		//$tpObj->xe['store'], $tpObj->xe['tellerStation'], 
		//$tpObj->xe['txNumber'], $tpObj->xe['txDate'], 
		//$tpObj->xe['txCode'], 
		$tpObj->xe['fxRate'], 
		$tpObj->xe['quoteCurr'], $tpObj->xe['quoteAmt'], 
		$tpObj->xe['authorization'], $tpObj->xe['txNumber'], 
	        $tpObj->xe['balance'], $tpObj->xe['status'],
		0, null);
		//$tpObj->xe['quoteAmt']);
		

        } else { // if there was no matching record for that orderID 
          sendStatusResponse($card, "", null, null, "", "", "", "", null, null, 
		null, null, null, null, null, 0.00, -1, 300, 
		"EGiftCard [" . $card . "] NOT FOUND", null, "REQUEST DENIED",
		null); 
        }
    } else {
          sendStatusResponse($card, "", null, null, "", "", "", "", null, null, 
		null, null, null, null, null, 0.00, -1, 300, 
		"EGiftCard [" . $card . "] NOT FOUND", null, "REQUEST DENIED",
		null);
           } 
}  // end of getGiftCardInfo 

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

function createGiftCard($ap)
/* This routine is called to start up a new order after a request was sent
   to discover the current exchange rate. So, this routine verifies that
   there is a record added to the DB for the amount in question.  It could 
   be improved by checking the date as well....  */
{
    errlog("INFO - createGiftCard.input array", $ap);
    //parse_str($ap, $p);
    $sendName = "Default Sender"; 
    if (isset($ap['senderName'])) {
       $sendName = $ap['senderName'];
    }
    $mobile = "";
    if (isset($ap['senderPhoneNumber'])) {
       $mobile = $ap['senderPhoneNumber'];
    }
    $baseCurr = "USD";
    if (isset($ap['baseCurrency'])) {
       $baseCurr = $ap['baseCurrency'];
    }
    $base = 0;
    if (isset($ap['baseCurrencyAmount'])) {
       $base = $ap['baseCurrencyAmount'];
    }
    $payer = "";
    if (isset($ap['retailPayer'])) {
       $payer = $ap['retailPayer'];
    }
    $recipCntry = "";
    if (isset($ap['beneficiaryCountry'])) {
       $recipCntry = $ap['beneficiaryCountry'];
    }
    $recipName = "";
    if (isset($ap['beneficiaryName'])) {
       $recipName = $ap['beneficiaryName'];
    }
    $recipPhone = "";
    if (isset($ap['beneficiaryPhoneNumber'])) {
       $recipPhone = $ap['beneficiaryPhoneNumber'];
    }
    $store = "";
    if (isset($ap['store'])) {
       $store = $ap['store'];
    }
    $teller = "";
    if (isset($ap['tellerStation'])) {
       $teller = $ap['tellerStation'];
    }
    $txNumb = "";
    if (isset($ap['txNumber'])) {
       $txNumb = $ap['txNumber'];
    }
    $txDate = date("Y-m-d H:i:s");
    if (isset($ap['txRequestDate'])) {
       $txDate = $ap['txRequestDate'];
    }
    $fxRate = "";
    if (isset($ap['fxRate'])) {
       $fxRate = $ap['fxRate'];
    }
    $quoteAmt = "";
    if (isset($ap['quoteCurrencyAmount'])) {
       $quoteAmt = $ap['quoteCurrencyAmount'];
    }
    $phones = $mobile . $recipPhone; 
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

        case 'fxrate':
	   getXchgRate($p);
           break;
        case 'get':  // for status inquiry
	   getGiftCardInfo($p);
           break;
        case 'merchants':  // return list of merchants
	   findMerchantInfo($p);
           break;
        case 'merchantpackages': 
	   findListOfProducts($p);
           break;
        case 'void': 
           cancelGiftCard($p);
           break;
        case 'purchase':
           createGiftCard($p);
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
