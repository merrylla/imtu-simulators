<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__));
// The following combination is used for ImtuTopupClass because we need to 
//be able to do reversals
include '../QAlib/wslib1.php';
include '../ezetop/ImtuTopUpClass.php';

function reverse($mCode, $egcCode)
{   
/* This funcgtion is called when the service is called to cancle a giftCard.
   The key to find the giftCard is the egcCode which was saved as one of the 
   keys when the order was created. */
    errlog("reverse: top of function", $egcCode);
     $xa = array('target' => 'a0', 'orderNum' => 'a1', 'transId' => 'a2',
                'egcNum' => 'a3', 'accessCode' => 'a4', 'merchant_code' => 'a5',
                'initial_balance' => 'a6', 'current_balance' => 'a7',
	        'balance_last_updated' => 'a8', 'active' => 'a9',
		'status' => 'a10', 'currency' => 'a11', 'challenge' => 'a12',
		'challenge_description' => 'a13', 'delivery_method' => 'a14',
		'delivery_target' => 'a15', 'scheduleDate' => 'a16',
		'deliverDate' => 'a17', 'sender' => 'a18', 'recip' => 'a19',
		'message_body' => 'a20', 'language_code' => 'a21', 
	        'audit_number' => 'a22', 'egcCode' => 'a23');
     $ra = array();
     $key = array('app' => 'CashStar', 'key' => $egcCode);
     $tpObj = new TopupObj($key, $xa, $ra);
     $today = date(DATE_ATOM, time());
     $country='';
     $curr = $tpObj->xe['currency'];
    if ($curr == "USD") {
       $country = 'US';
    } else {
       $country = 'UK'; // unknown
    }
    $bal = $tpObj->xe['initial_balance'];
    errlog ("reverse balance", $bal); 
    sendXMLReversalResponse($tpObj->xe['transId'], $egcCode,  
        $tpObj->xe['accessCode'], $mCode, $bal, $bal,  $today, 
	$curr,$tpObj->xe['challenge'], $tpObj->xe['challenge_description'], 
	$tpObj->xe['delivery_method'], $tpObj->xe['delivery_target'], 
	$tpObj->xe['scheduleDate'], $tpObj->xe['sender'], $tpObj->xe['recip'], 
	$tpObj->xe['message_body'], $country); 
}//reverse

function sendRsp($a, $code=200)
/* This is used every time an JSON response is sent */
{
    $ht = new HttpResponse();
    $ht->status($code);
    //$ht->setContentType('application/xml');
    $ht->setContentType('application/json');
    $ht->setData($a);
    errlog("DEBUG: sending resp from sendRsp", $ht);
    $ht->send();
}

function i_rand($min=0,$max=1000){
/* This random number generator is used to find a pin */
    if ($min>$max) return false;
    return mt_rand($min,$max);
}

function an_rand($numDigits, $digitsOnly){
/* This random alphnumeric string generator is used to find the egc code */
    $key = "";
    $chars = "";
    $numChars = 0;
    if ($digitsOnly) {
       $numChars = 9;
       $chars = "1234567890";
    } else {
       $numChars = 61;
       $chars = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    }
    $i = 0;
    while ($i < $numDigits) {
      $rand = mt_rand(0, $numChars);
      $newChar = $chars[$rand];
      $key = $key . $newChar;
      $i++;
    } 
    return $key;
}

function findMerchants() 
{
   /* The API gave us an input to respond this way.  So, it is here just in 
      case it is needed. */
   $x0 = array ('merchant_code' => 'BG',
                'name' => 'BabyGap',
		'legal_name' => 'Gap Inc');  
   $x1 = array ('merchant_code' => 'BB',
                'name' => 'BestBuy',
		'legal_name' => 'Best Buy');  
   $x2 = array ('merchant_code' => 'HD',
                'name' => 'HomeDepot',
		'legal_name' => 'The Home Depot');  
   $x3 = array ('merchant_code' => 'ON',
                'name' => 'OldNavy',
		'legal_name' => 'Gap Inc');
   $orderId = i_rand(1,50000);
   $main = array ('merchants' => array ('transaction_id' => $orderId,
				        'merchant' => array ($x0,
					$x1,
					$x2,
					$x3)));
  $rsp = json_encode($main);
  errlog("DEBUG: sending resp from findMerchants", $rsp); 
  sendRsp ($rsp); 
} // end of findMerchants

function findCatalog($get){
   /* The API gave us an input to respond this way.  So, it is here just in 
      case it is needed. */
    if (isset($get['country'])) {
       $country=$get['country'];
    } else {
      $country='US';
    }
    $merchant = $get['mcode'];
    if (isset($get['currency'])) {
       $curr = $get['currency'];
    } else {
      $curr = 'USD';
    }
    $transId = i_rand(1,50000000);
    switch ($merchant) {
        case 'BB': 
	case 'ON':
      $x0 = array ('value' => 50);
      break;
        case 'HD': 
	case 'BG':
      $x0 = array ('vslue' => 75);
      break;
        default:
      $x0 = array('message' => 'No such merchant');
     }
  $main = array ('catalog' => array (
         'transaction_id' => $transId,
	 'currency' => $curr,
	 'supports_balance' => true,
	 'supports_reload' => false,
	 'fixed' => array($x0)));
  $rsp = json_encode($main);
  errlog("DEBUG: sending resp from findCatalog ", $rsp); 
  sendRsp ($rsp); 
}// findCatalog

function findCatOpt($get){
   /* The API gave us an input to respond this way.  So, it is here just in 
      case it is needed. */
   $merchant = $get['mcode'];
   errlog("DEBUG: findCatOpt  ", $merchant); 
   $transId = i_rand(1,50000000);
   $x0 = array('country' => 'None', "currency" => 'USD');
   $x1 = array('country' => 'US', "currency" => 'USD');
   $main = array ('catalog_options' => array (
         'merchant_code' => $merchant,
         'transaction_id' => $transId,
         'options' => array($x0, $x1)));
  $rsp = json_encode($main);
  errlog("DEBUG: sending resp from findCatOpt", $rsp); 
  sendRsp ($rsp); 
}

function findFacePlate($merchant) {
   /* The API gave us an input to respond this way.  So, it is here just in 
      case it is needed. It is doubtful that we would need this one. */
   $transId = i_rand(1,50000000);
   $facePlateTY1 = array(
      'faceplate_code' => 'TY1',
      'name' => "Thank You",
      'text_color' => "FFFFFF",
      'thumbnail' => 'http://www.icons.com/thumb1.jpg',
      'preview' => 'http://www.icons.com/preview1.jpg',
      'print' => 'http://www.icons.com/print1.jpg');
   $facePlateHB1 = array(
      'faceplate_code' => 'HB1',
      'name' => "Happy Birthday",
      'text_color' => "FFFFFF",
      'thumbnail' => 'http://www.icons.com/HBthumb1.jpg',
      'preview' => 'http://www.icons.com/HBpreview1.jpg',
      'print' => 'http://www.icons.com/HBprint1.jpg');
   $main - array ('faceplates' => array (
         'transaction_id' => $transId,
         'faceplate' => $facePlateTY1,
         'faceplate' => $facePlateHB1));
  $rsp = json_encode($main);
  errlog("DEBUG: sending resp from findFacePlate", $rsp); 
  sendRsp ($rsp); 
}

function findECard($merchant, $card) {
   /* The API gave us an input to respond this way.  So, it is here just in 
      case it is needed. */
   $EGCNum = i_rand(1,99999999);
   $accCd = i_rand(1,99999999);
   $viewDate = new DateTime('2000-01-01');
echo $date->format('Y-m-d H:i:s');
   $eGCard= array(
	'egc_code' => $card,
	'egc_number' => $EGCNum,
	'access_code' => $accCd,
	'barcode_url' => 'http://barcode.url/v1/barcode/AB1234567890123456CD/format/CODE128',
	'merchant_code' => $merchant,
	'inital_balance' => 50,
	'promo_code' => 'xyz',
	'template_code' => 'CUSTOM_TEMPLATE',
	'current_balance' => 10.00,
	'balance_last_updated' => $today,
	'balance_url' => 'http://www.devcall02.ixtelecom.com/CashStar/giftcard/balance',
	'curreny' => 'USD',
	'active' => 'true',
	'status' => 'ACTIVE',
	'viewed' => 'false',
	'first_viewed' => $viewDate);
}

function processMerchantReq($ap, $get){
   /* The API gave us an input to respond this way.  So, it is here just in 
      case it is needed. */
    $jsonReq = json_decode($ap, true);
    if (isset($jsonReq['egc'])) {
       $sender = $jsonOrder['Sender'];
    }
}

function showVersion (){
   /* The API gave us an input to respond this way.  So, it is here just in 
      case it is needed. */
    $main = array ('version' => array (
		     'specification' => "2.9.4-b")
		     );
  $rsp = json_encode($main);
  errlog("DEBUG: sending resp from showVersion", $rsp); 
  sendRsp ($rsp); 
}

function parseOrder   ($values, $val, &$auditNum, &$lang, &$trouble) {
/* This function is used to parse the first part of the order XML. 
   One can see by the arguments to this function that the returned values
   are auditNum, lang and any trouble.  The input arguments are the values and 
   val arrays */
           $tempArr = $values[$val[0]];
           $attArray = $tempArr['attributes'];
	   if (isset ($attArray['audit_number'])) 
              $auditNum = $attArray['audit_number'];
           else $trouble = true; 
	   if (isset ($attArray['language_code'])) 
	      $lang = $attArray['language_code']; 
           else $trouble = true; 
    errlog("INFO auditNum order", $auditNum);
    errlog("INFO lang order", $lang);
    return true;
}

function parseEGC ($values, $val, &$mcode, &$initBal, &$curr, &$challng, 
                   &$challngDesc, &$trouble)  {
 /* This function is used to parse the EGC part of the XML input
    in the request to purchase a new giftCard. The input is the value
    array along with val whereas the output is mcode, initBal, curr, 
    the challenge answer, the challenge question and any trouble. */
           $tempArr = $values[$val[0]];
           $attArray = $tempArr['attributes'];
	   if (isset ($attArray['merchant_code'])) 
	      $mcode = $attArray['merchant_code'];
           else  $trouble = true; 
    errlog("INFO merchant_code - egc", $mcode);
	   if (isset ($attArray['initial_balance']) )
	      $initBal = $attArray['initial_balance'];
           else $trouble = true; 
    errlog("INFO initial_balance - egc", $initBal);
	   if (isset ($attArray['currency'])) 
	      $curr = $attArray['currency'];
           else $trouble = true; 
    errlog("INFO - currency egc", $curr);
	   if (isset ($attArray['challenge'])) 
	      $challng = $attArray['challenge'];
           else $trouble = true;
    errlog("INFO - challenge egc", $challng);
	   if (isset ($attArray['challenge_description']))
	      $challngDesc = $attArray['challenge_description'];
           else $trouble = true;
    errlog("INFO - challengeDesc egc", $challngDesc);
    return true;
}

function parseDeliv($values, $val, &$method, &$schdDate, &$target, &$trouble) { 
 /* This function is used to parse the delivery part of the XML input
    in the request to purchase a new giftCard. The input is the value array
    which has particular keys for the delivery XML. The output includes
    values for method, schdeduleDate, targeta and any trouble.  */
    $tempArr = $values[$val[0]];
    $attArray = $tempArr['attributes'];
    if (isset ($attArray['method']))
	$method = $attArray['method'];
    else $trouble = true;
    errlog("INFO - method delv", $method);
    if (isset ($attArray['scheduled']))
	$schdDate = $attArray['scheduled'];
    else $trouble = true;
    errlog("INFO - sched  delv", $schdDate);
    if (isset ($attArray['target']))
	$target= $attArray['target'];
    else $trouble = true;
    errlog("INFO - target delv", $target);
    return true;
}

function parseMsg($values, $val, &$from, &$to, &$body, &$trouble) {
 /* This function is used to parse the message part of the XML input
    in the request to purchase a new giftCard. The input is the value array
    which has particular keys for the delivery XML. The output includes
    values for the sender, recipient, message body and any trouble.  */
    $tempArr = $values[$val[0]];
     $attArray = $tempArr['attributes'];
     if (isset ($attArray['from']))
	$from= $attArray['from'];
     else $trouble = true;
     errlog("INFO - sender mess", $from);
     if (isset ($attArray['to']))
	$to = $attArray['to'];
     else $trouble = true;
     errlog("INFO - recip mess", $to);
     if (isset ($attArray['body']))
	$body = $attArray['body'];
     else $trouble = true;
     errlog("INFO - body mess", $body);
     return true;
}

function parseAdd($values, $val, &$type, &$content, &$trouble) {
  /* Sometimes there is supposed to be additional content to parse.
     This function is here to parse that content */
           $tempArr = $values[$val[0]];
           $attArray = $tempArr['attributes'];
	   if (isset ($attArray['type']))
	      $type= $attArray['type'];
           else $trouble = true;
    errlog("INFO - additional type", $type);
	   if (isset ($attArray['content']))
	      $content = $attArray['content'];
           else $trouble = true;
    errlog("INFO - additional content", $content);
}

function createBadResponse($errorCode, $transId) {
  /* This is the way that the different error codes are returned.  The 
     format of these errors is specified in the API document.  Paul Mowatt
     told me that it is the error_code that lines up with our return codes.
     He also told me that the code that is sent via the sendRsp function
     must be soemthing other than 200 to register the error as an error.*/
   $rsp = new SimpleXMLElement('<error/>');
   $rsp->addAttribute('transaction_id', $transId);
   $rsp->addAttribute('http_code', 400);
   $rsp->addAttribute('error_code', $errorCode);
   switch ($errorCode) {
        case '4000': 
	case '4005':
           $rsp->addAttribute('error_message', "Invalid request");
	   break;
        case '4001':
           $rsp->addAttribute('error_message', "Authorization failure");
	   break;
        case '1007': 
	case '4009':
           $rsp->addAttribute('error_message', "Insufficient funds");
	   break;
        case '4003': 
	case 'merchant_code':
           $rsp->addAttribute('error_message', "Invalid Merchant");
	   break;
        case '4004':
           $rsp->addAttribute('error_message', "Transaction not found");
	   break;
        case '5000': 
	case '5002': 
	case '5003':
           $rsp->addAttribute('error_message', "Internal Error");
	   break;
        case 'initial_balance':
           $rsp->addAttribute('error_message', "Invalid Amount");
	   break;
        default: $rsp->addAttribute('error_message', "Invalid Product");
        }
   $dom = dom_import_simplexml($rsp);
   $domOut = new DOMDocument('1.0', 'UTF-8');
   $domOut->formatOutput = true;
   $dom = $domOut->importNode($dom, true);
   $dom = $domOut->appendChild($dom);
   errlog("xml to send", $domOut->saveXML($domOut, LIBXML_NOEMPTYTAG));
   sendRsp($domOut->saveXML($domOut, LIBXML_NOEMPTYTAG), 400);
}

function sendXMLReversalResponse($transId, $egcCode, $access, $mcode, $initBal,
        $presentBal, $today, $curr, $chal, $chalDesc, $method, $target, 
        $schdDate, $sender, $recip, $body, $country) {
/* Even though the API seems to specify that the response to a reversal is the
   same XML format as for an order creation or status request, it is not.  I
   found that this is the format by running a reversal to the CashStar test
   webService and took note of the format of the returned information */
   $egc = new SimpleXMLElement('<egc/>');
   $egc->addAttribute('status', "CLOSED");
   $egc->addAttribute('merchant_code', $mcode);
   $egc->addAttribute('initial_balance', $initBal);
   $egc->addAttribute('country_issued', $country);
   $egc->addAttribute('egc_code', $egcCode);
   $egc->addAttribute('challenge_type', "CASE_INSENSITIVE");
   $egc->addAttribute('balance_last_updated', $today);
   $egc->addAttribute('url', NULL);
   $egc->addAttribute('challenge_description', $chalDesc);
   $egc->addAttribute('challenge', $chal);
   $egc->addAttribute('balance_url', NULL);
   $egc->addAttribute('currency', $curr);
   $egc->addAttribute('transaction_id', $transId);
   $egc->addAttribute('current_balance', $presentBal);
   $egc->addAttribute('active', "false");
   $egc->addAttribute('faceplate_code', "XYZ123MKA");
   $egc->addAttribute('viewed', 'false');
   $delv = $egc->addChild('delivery');
   $delv->addAttribute('delivered_by', 'CASHSTAR');
   $delv->addAttribute('scheduled', $schdDate);
   $delv->addAttribute('status', "SENT");
   $delv->addAttribute('target', $target);
   $delv->addAttribute('method', $method);
   $mess = $egc->addChild('message');
   $mess->addAttribute('body', $body);
   $mess->addAttribute('to', $recip);
   $mess->addAttribute('from', $sender);
   $dom = dom_import_simplexml($egc);
   $domOut = new DOMDocument('1.0', 'UTF-8');
   $domOut->formatOutput = true;
   $dom = $domOut->importNode($dom, true);
   $dom = $domOut->appendChild($dom);
   errlog("xml to send", $domOut->saveXML($domOut, LIBXML_NOEMPTYTAG));
   sendRsp($domOut->saveXML($domOut, LIBXML_NOEMPTYTAG));
} //sendXMLReversalResponse

function sendXMLOrderResponse($orderNum, $transId, $auditNum, $egcCode, 
        $access, $mcode, $initBal, $presentBal, $balUpdated, $active, $status, $curr,
        $chal, $chalDesc, $method, $target, $schdDate, $sender, $recip,
        $body, $lang, $orderStatus, $country) {
/* This function sends out the XML of the order information after it is 
    resend args:
      sendXMLOrderResponse($tpObj->xe['orderNum'], $tpObj->xe['transId'], 
	$auditNum, $tpObj->xe['egcCode'], $tpObj->xe['accessCode'],
	$tpObj->xe['merchant_code'], $tpObj->xe['initial_balance'], 
        $tpObj->xe['current_balance'], $tpObj->xe['balance_last_updated'],
	$tpObj->xe['active'], $tpObj->xe['status'], $tpObj->xe['currency'],
	$tpObj->xe['challenge'],$tpObj->xe['challenge_description'],
        $tpObj->xe['method'],$tpObj->xe['target'],$tpObj->xe['scheduleDate'],
	$tpObj->xe['deliverDate'], $tpObj->xe['sender'],$tpObj->xe['recip'],
	$tpObj->xe['message_body'], $tpObj->xe['language_code'], "ACTIVE",
   created or if it is called for status */
   $rsp = new SimpleXMLElement('<order/>');
   $rsp->addAttribute('audit_number', $auditNum);
   $rsp->addAttribute('language_code', $lang);
   $rsp->addAttribute('order_status', $orderStatus);
   $rsp->addAttribute('order_number', 'CAD-'.$orderNum);
   $rsp->addAttribute('transaction_id', $transId);
   $egc = $rsp->addChild('egc');
   $egc->addAttribute('status', $status);
   $egc->addAttribute('merchant_code', $mcode);
   $egc->addAttribute('initial_balance', $initBal);
   $egc->addAttribute('country_issued', $country);
   $egc->addAttribute('egc_code', $egcCode);
   $egc->addAttribute('challenge_type', "CASE_INSENSITIVE");
   $egc->addAttribute('balance_last_updated', $balUpdated);
   $egc->addAttribute('url', NULL);
   $egc->addAttribute('challenge_description', $chalDesc);
   $egc->addAttribute('challenge', $chal);
   $egc->addAttribute('balance_url', NULL);
   $egc->addAttribute('currency', $curr);
   $egc->addAttribute('current_balance', $presentBal);
   $egc->addAttribute('active', $active);
   $egc->addAttribute('faceplate_code', $access);
   $egc->addAttribute('viewed', 'false');
   $delv = $egc->addChild('delivery');
   $delv->addAttribute('delivered_by', 'CASHSTAR');
   $delv->addAttribute('scheduled', $schdDate);
   $delv->addAttribute('target', $target);
   $delv->addAttribute('method', $method);
   //$delv->endElement();
   $mess = $egc->addChild('message');
   $mess->addAttribute('body', $body);
   $mess->addAttribute('to', $recip);
   $mess->addAttribute('from', $sender);
   //$mess->endElement();
   //$egc->endElement();
   //$rsp->endElement();
   $dom = dom_import_simplexml($rsp);
   $domOut = new DOMDocument('1.0', 'UTF-8');
   $domOut->formatOutput = true;
   $dom = $domOut->importNode($dom, true);
   $dom = $domOut->appendChild($dom);
   errlog("xml to send", $domOut->saveXML($domOut, LIBXML_NOEMPTYTAG));
   sendRsp($domOut->saveXML($domOut, LIBXML_NOEMPTYTAG));
} //sendXMLOrderResponse

function resend ($auditNum, $id = "123")
/* This routine is called when intlTopUp says that it is taking too long for the   service to respond.  It is called with the audit number to find the correct
   order in the local DB */
{
    errlog("resend :$auditNum", $auditNum);
    $key = array('app' => 'CashStar', 'key' => $auditNum);
    $xa = array('target' => 'a0', 'orderNum' => 'a1', 'transId' => 'a2',
                'egcNum' => 'a3', 'accessCode' => 'a4', 'merchant_code' => 'a5',
                'initial_balance' => 'a6', 'current_balance' => 'a7',
	        'balance_last_updated' => 'a8', 'active' => 'a9',
		'status' => 'a10', 'currency' => 'a11', 'challenge' => 'a12',
		'challenge_description' => 'a13', 'delivery_method' => 'a14',
		'delivery_target' => 'a15', 'scheduleDate' => 'a16',
		'deliverDate' => 'a17', 'sender' => 'a18', 'recip' => 'a19',
		'message_body' => 'a20', 'language_code' => 'a21', 
	        'audit_number' => 'a22', 'egcCode' => 'a23');
    $ra = array();
    $tpObj = new TopupObj($key, $xa, $ra);
    errlog("target", $tpObj->xe['target']);
    errlog("orderNum", $tpObj->xe['orderNum']);
    $country='';
    if ($tpObj->xe['currency'] == "USD") {
       $country = 'US';
    } else {
       $country = 'UK'; // unknown
    }
        /*sendXMLOrderResponse($orderNum, $transId, $auditNum, $egcCode, $access,
	$mcode, $initBal, 0, $today, 'true', 'ACTIVE', $curr,$chal,$chalDesc,
        $method,$target,$schdDate,$sender,$recip,$body, $lang, "ACTIVE", $country); */
    sendXMLOrderResponse($tpObj->xe['orderNum'], $tpObj->xe['transId'], 
	$auditNum, $tpObj->xe['egcCode'], $tpObj->xe['accessCode'],
	$tpObj->xe['merchant_code'], $tpObj->xe['initial_balance'], 
        $tpObj->xe['current_balance'], $tpObj->xe['balance_last_updated'],
	$tpObj->xe['active'], $tpObj->xe['status'], $tpObj->xe['currency'],
	$tpObj->xe['challenge'],$tpObj->xe['challenge_description'],
        $tpObj->xe['method'],$tpObj->xe['target'],
	$tpObj->xe['deliverDate'], $tpObj->xe['sender'],$tpObj->xe['recip'],
	$tpObj->xe['message_body'], $tpObj->xe['language_code'], "ACTIVE",
        $country);
}

function  parseXMLOrder($ap, &$auditNum, &$lang, &$mcode, &$initBal, &$curr, 
    &$chal, &$chalDesc, &$method, &$schdDate, &$target, &$sender, &$recip, &$body) {
/* This function is called to parse the XML order input. There are 4 parts.
   The input is the ap array and the output is all of the arguments of the 
   function after that. */
    $parser = xml_parser_create();
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, $ap, $values, $tags);
    $gotOrder= $gotEGC=$gotDelivery=$gotMess =$trouble=false;
    foreach ($tags as $key=>$val) {
    switch ($key) {
        case 'order': // part 1 is the order section
// Set the auditNum and lang values, note if there is trouble and return boolean
           $gotOrder = parseOrder($values, $val, $auditNum, $lang, $trouble);
	   break;
        case 'egc': // this is the egc section
           $gotEGC = parseEGC ($values, $val, $mcode, $initBal, $curr, $chal, $chalDesc, $trouble); 
           break;
        case 'delivery': // this is the delivery section
           $gotDelivery = parseDeliv($values, $val, $method, $schdDate, $target, $trouble);
           break;
        case 'message':  // the last section is for the message
           $gotMess = parseMsg($values, $val, $sender, $recip, $body, $trouble);
           break;
        case 'additional_content':
           $type= $content="";
           parseAdd($values, $val, $type, $content, $trouble);
           break;
       default :
    errlog("INFO - default $key", $val);
       } // end switch
    } // end foreach
    errlog("INFO - sender ", $sender);
    errlog("INFO - trouble ", $trouble);
    errlog("INFO - gotMess", $gotMess);
    errlog("INFO - gotDeli", $gotDelivery);
    errlog("INFO - gotEGC", $gotEGC);
    errlog("INFO - gotOrder", $gotOrder);
    if ($trouble || (!$gotMess) || !$gotDelivery || !$gotEGC || !$gotOrder) {
       errlog("INFO createOrder   - dealt with missing data:", $trouble);
       createBadResponse(5000, $target);
       return true;
    } else {
      return false;
    }
} // end parseXMLOrder

function createOrder ($ap, $id = "123")
/* This routine is called to 1) parse the order sent in via XML
   2) start up a new order after a request was sent
   to discover the current exchange rate. So, this routine verifies that
   there is a record added to the DB for the amount in question.  It could 
   be improved by checking the date as well....  */
{
   $auditNum=$lang= $mcode= $initBal= $curr= $chal= $chalDesc=""; 
   $method= $schdDate= $target= $sender= $recip= $body="";
   if (parseXMLOrder($ap, $auditNum, $lang, $mcode, $initBal, $curr, 
    $chal, $chalDesc, $method, $schdDate, $target,$sender, $recip, $body)) {
      return; 
  } else {
    $orderNum = an_rand(4,true);
    errlog("INFO createOrder   - id:", $orderNum);
    $transId = i_rand(1,99999999);
    $egcCode = an_rand(18,false);
    errlog("INFO egcCode- id:", $egcCode);
    $egcNum = an_rand(18,true);
    $access = an_rand(4,true);
    errlog("INFO createOrder   - audit:", $auditNum);
    errlog("INFO egcNum- id:", $egcNum);
    $problemFound = false;
    $errorCode = 0;
    $xa =array();
    // set up the order of the array values in the GC script so that tags
    // can be matched with values correctly via the topUpObj class.
    $xa = array('target' => 'a0', 'orderNum' => 'a1', 'transId' => 'a2',
                'egcNum' => 'a3', 'accessCode' => 'a4', 'merchant_code' => 'a5',
                'initial_balance' => 'a6', 'current_balance' => 'a7',
	        'balance_last_updated' => 'a8', 'active' => 'a9',
		'status' => 'a10', 'currency' => 'a11', 'challenge' => 'a12',
		'challenge_description' => 'a13', 'delivery_method' => 'a14',
		'delivery_target' => 'a15', 'scheduleDate' => 'a16',
		'deliverDate' => 'a17', 'sender' => 'a18', 'recip' => 'a19',
		'message_body' => 'a20', 'language_code' => 'a21', 
	        'audit_number' => 'a22', 'egcCode' => 'a23');
    $ra = array();
    $key1 = array('app' => 'CashStar', 'key' => $egcCode);
    $key2 = array('app' => 'CashStar', 'key' => $target);
    $key3 = array('app' => 'CashStar', 'key' => $auditNum);
    $tpDelayObj = new TopupObj($key2, $xa, $ra);
    $status = "";
    errlog("INFO target:", $tpDelayObj->xe['target']);
    errlog("INFO delivery_target:", $tpDelayObj->xe['delivery_target']);
    if ($tpDelayObj->xe['target'] != $tpDelayObj->xe['delivery_target']) {
       $errorCode = $tpDelayObj->xe['target'];
       createBadResponse($errorCode, $tpDelayObj->xe['transId']);
    } else {
    $today = date(DATE_ATOM, time());
    $country='';
    if ($curr == "USD") {
       $country = 'US';
    } else {
       $country = 'UK'; // unknown
    }
    // This array is set up and inserted into the DB so that statuses can
    // be returned correctly
    if ($tpDelayObj->abort){
       $status = 'PROCESSING';
    } else {
       $status = 'ACTIVE';
    }
    $xA=array('a0' => $target,
              'a1' => $orderNum,
	      'a2' => $transId,
	      'a3' => $egcNum,
	      'a4' => $access,
	      'a5' => $mcode,
	      'a6' => $initBal,
	      'a7' => 0, //current balance is 0 since it is sent
		'a8' => $today,
		'a9' => 'true',
		'a10' => $status,
		'a11' => $curr,
		'a12' => $chal,
		'a13' => $chalDesc,
		'a14' => $method,
		'a15' => $target, 
		'a16' => $schdDate,
		'a17' => $today,
		'a18' => $sender,
		'a19' => $recip,
		'a20' => $body,
                'a21' => $lang,
		'a22' => $auditNum, 'a23' => $egcCode);
// egcCode with record is needed for responding with correct order
// for status, cancel, balance and resend requests givn the egcCode
    insertTransDB($key1, $xA); 
// This key is used when we want to retrieve the order based on the phone number 
//This was put into the CG script with delay etc
    //insertTransDB($key2, $xA); 
// This key is used when getting order by audit number
    insertTransDB($key3, $xA);
    errlog("tpDelayObj->xe", $tpDelayObj->xe);
    errlog("tpDelayObj->delay", $tpDelayObj->delay);
    errlog("tpDelayObj->abort", $tpDelayObj->abort);
    $tpDelayObj->buildRspData($ap); // Deal with wait or abort from CG script
    // return response to the createOrder request
    errlog("tpDelayObj->delay", $tpDelayObj->delay);
    errlog("tpDelayObj->abort", $tpDelayObj->abort);
    if ($tpDelayObj->abort == 1) { 
      errlog("No response as there was an abort", $tpDelayObj->abort);
    } else {
        sendXMLOrderResponse($orderNum, $transId, $auditNum, $egcCode, $access,
	$mcode, $initBal, 0, $today, 'true', 'ACTIVE', $curr,$chal,$chalDesc,
        $method,$target,$schdDate,$sender,$recip,$body, $lang, "ACTIVE", $country);
     }
    }} // created good response
} //end of  createOrder    

function authenticate() {
}

function parseAction($get, $p, $parms)
/* This function is used to make sure that all requests are directed
   appropriately to the correct routines. */
{
    errlog("parseAction: usr info - get: $get; p: $p; parms ", $parms);

    $id="";
    $cmd="";
    if (isset($get['id'])) {
       $id=$get['id'];
    } else {
      $id = NULL;
    }
    if (isset($get['cmd'])) {
       $cmd=$get['cmd'];
       errlog("parseAction: cmd:", $cmd);
    } 
    switch ($cmd) {

        case 'merchant':
           findMerchants();
           break;
        case 'merchantCatalog':
           findCatalog($get);
           break;
        case 'merCatalogOpt':
           findCatOpt($get);
	   break;
        case 'merchantFaceP':
           $merchant = $get['mcode'];
           findFacePlate($merchant);
	   break;
        case 'merchantECard':
           $merchant = $get['mcode'];
           $card = $get['eCode'];
           findECard($merchant, $card);
	   break;
        case 'cancel':
           $merchant = $get['mcode'];
           $card = $get['eCode'];
	   reverse ($merchant, $card);
           break;
        case 'order':
           $id='MKA';
           if (isset($get['audit_number'])) {
              $id=$get['audit_number'];
              errlog("parseAction: audit id:", $id);
              } 
            createOrder($p, $id);
              break;
        case 'showOrder':
              resend($get['audit_number']);
              break;
        case 'reload':
              loadOrder($id, $p);
              break;
        default:
               if (empty($id)) {
		 createOrder($p);
               }
     } // end switch
}//parseAction

// The following code is the main driver that is run when a request is sent in
// to this RESTfuul web service
try {

$reqRev='';
$getRev='';
$putRev='';
$delRev='';

//   errlog("INFO: GLOBALS: ", $GLOBALS);

if (!empty($_GET)) {
   errlog("INFO: GET: ", $_GET);
   errlog("GET['cmd'] = ",$_GET['cmd']);
   if (isset($_GET['mcode'])) {
      errlog("GET['mcode'] = ",$_GET['mcode']);
   }
   if ($_GET['cmd'] == 'showOrder') {
      $args = $_SERVER['REQUEST_URI'];
      $pos = strpos($args, "audit_number");
      $pos = strpos($args, "=", $pos);
      $val = substr($args, ++$pos);
      $_GET['audit_number'] = $val;
   }
   $getRev=$_GET;
}

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
   parse_str(file_get_contents("php://input"), $post_vars);
   errlog("INFO: PUT: ", $post_vars);
   $putRev=$post_vars;
}

if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
   parse_str(file_get_contents("php://input"), $post_vars);
   errlog("INFO: DELETE: ", $post_vars);
   $delRev=$post_vars;
}

if (!empty($HTTP_RAW_POST_DATA)) {
   $reqRev=$HTTP_RAW_POST_DATA;
   errlog("INFO: HTTP_RAW_POST_DATA", $HTTP_RAW_POST_DATA);

}
elseif (!empty($_POST)) {
   errlog("INFO: POST: ", $_POST);
   $reqRev=$_POST;
}


parseAction($getRev, $reqRev, $putRev);

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
