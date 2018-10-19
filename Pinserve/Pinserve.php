<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__));

include '../ezetop/ImtuTopUpClass.php';
include '../QAlib/wslib1.php';
$wsdl = "http://devcall02.ixtelecom.com/wsdl/Pinserve.wsdl";

/* this was taken from Cubacel and it is getting changed over according
   to the wsdl for Pinserve*/

date_default_timezone_set('America/New_York');

function oneYearLater() {
	$myDate = new DateTime('NOW');
        $month = date("m",strtotime($mydate));
        $year = date("y",strtotime($mydate));
        $day = date("d",strtotime($mydate));
}

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

//The following is an actual topup for Pinserve
   function DoTopUp($p) {
$disclaimer = "Blackstone is a patented distributor of phone cards & calling ";
$tAndC = "Prices, fees, availability, terms of use and rates are subject";
$disclaimer .= "cards for the purpose of international & domestic long distance phone calls. This";
$tAndC .= " to change without prior notice at the discretion of the network ";
$tAndC .= "service provider. For more detail on rates and other charges, please";
 $tAndC .= " consult the customer service. Blackstone is not responsible for ";
 $tAndC .= "lost or stolen pins and changes in the rates by service provider.";
     errlog("DoTopUp p = ", $p);
     $phone = $p->PhoneNumber;
     $code = $p->ProductMaincode;
     errlog("DoTopUp phone = ", $phone);
     /*$rspA = array('DoTopUpResult' => 
              array('ErrorCode' => 0,
		    'ErrorMessage'=> "Nothing",
		    'TransactionID' => randStr(8),
		    'PinID' => '12345',
		    'ProductMainCode' => $code));
     return $rspA; */
     $orderId = $p->OrderID;
     errlog("DoTopUp orderId = ", $orderId);
     $keyT=array('app' => 'Pinserve', 'key' => $orderId);
     $key=array('app' => 'Pinserve', 'key' => $phone);
     $xA = array('phone' => 'a0',
		  'orderId' => 'a1',
		  'code' => 'a2',
		  'msg' => 'a3',
		  'errCode' => 'a4',
		  'errMsg' => 'a5');
     errlog("DoTopUp xa = ", $xA);
     $rA = array('orderId' => 'a1',
		'code' => 'a2',
		'msg' => 'a3',
                'errCode' => 'a4',
		'errMsg' => 'a5');
     errlog("DoTopUp rA = ", $rA);
     $xArr = array('a0' => $phone,
		'a1' => $orderId,
		'a2' => $code,
		'a3' => 'Ok');
     updateTransDBRec($phone, $xArr, 'Pinserve');
     insertTransDB($keyT, $xArr);
     $tpObj= new TopupObj($key, $xA, $rA);
     errlog("DoTopUp tpObj = ", $tpObj);
     if ($tpObj->re[errCode]) {
        $error = $tpObj->re[errCode];
        $errMsg = $tpObj->re[errMsg];
        errlog("DoTopUp set errMsg & errCode= $error", $errMsg);
     /*$rspA = array('DoTopUpResult' =>  */
        } else {
        $error = "0";
        $errMess = NULL;
     $massagedInput = array('phone' => $phone,
				'orderId' => $orderId,
				'code' => $code,
				'msg' => 'Ok');
     errlog ("INFO DoTopUp about to validate with:", $tpObj->re);
     $tpObj->validateReqInput($massagedInput);
     errlog ("INFO DoTopUp finished validate :", $tpObj->re);
     errlog ("INFO DoTopUp topup result:", $tpObj->re);
     $rA = array(); 
     $rsp = $tpObj->buildRspData($xArr);
       } 
     $rspA = array('ErrorCode' => $error,
			'ErrorMessage'=> $errMsg,
			'TransactionID' => randStr(8),
			'PinID' => '12345',
			'ProductMainCode' => $code,
			'ProductDenomination' => '10.00',
			'PinNumber' => '12695',
			'ControlNumber' => NULL,
			'Language' => NULL,
			'ProductSBT' => randStr(6),
			'Conn800English' => NULL,
			'CustomerServiceEnglish' => NULL,
			'LocalAccessPhones>' =>
				array('anyType' => 'ab'),
			'ItemFK' => randStr(6),
			'TransactionMode' => NULL,
			'ProductDescription' => NULL,
			'Batch' => NULL,
			'ExpirationDate' => new DateTime('last day of next month'),
			'ProductType' => 'Top Up',
			'Barcode' => '01110',
			'Instructions' => 'Do not move',
			'PrinterDisclaimer' => $disclaimer,
			'ToppedUpNumber' => $phone,
			'AccountNumber' => NULL,
			'LegalInfo>' => 
				array('Version' => '2.3.3',
				'Copyright' => 'Copyright © 2008 Pinserve Technologies, Inc. All Rights Reserved',
				'Disclaimer' => $disclaimer,
			        'PrivacyURL' => 'http://www/blackstoneonline.com/contactus/privacypolicy',
				'TermsAndConditions'=> $tAndC,
				'ContactPhone'=> '1 800 639-5555'),
                         'ForeignAmount' => '0',
			 'ForeignMoneyLeft' => '0',
			'ReferenceNumber' => randStr(8),
			'AuthorizationCode' => randStr(5),
			'CurrencyCode' => NULL);  // a la Junie B
        errlog ("INFO Topup response :", $rspA);
        return array('DoTopUpResult' => $rspA);
   } // end DoTopUp

/*  This function is never called as Cubacel does not really want to
           support reversals.  Who knows.  It might be used eventually.
           This is the cancel topUp function for fixed price products */

  function VoidOrder ($request) {
     errlog("CancelSaleRequest req = ", $request);
     $rspA = array();
     $tickArr = $request->SessionTicket;
     $ticket = $tickArr->Ticket;
     $orderId = $request->OrderId;
     $transId = $request->TransactionId;
     $key=array('app' => 'Cubacel', 'key' => $transId);
     $xA = array('phone' => 'a0',
                  'ticket' => 'a1',
		  'price' => 'a2',
		  'transId' => 'a3',
		  'orderId' => 'a4',
		  'bool' => 'a5',
		  'msg' => 'a6');
     $rA = array('ticket' => 'a1',
		'price' => 'a2',
		'transId' => 'a3',
		'orderId' => 'a4',
		'bool' => 'a5',
		'msg' => 'a6');
     $tpObj= new TopupObj($key, $xA, $rA);
     if (!isset ($tpObj->xe['phone'])) {
       errlog("CancelSaleRequest phone= ", $phone);
       $result = array('ValueOk' => 'true',
                       'Message' => 'OK',
                       'RequestTime' => new DateTime('NOW'),
         	       'ResponseTime' => new DateTime('NOW'));
       errlog("CancelSaleRequest response = ", $result);
     } else { 
        $dbhandle=new SQLite3('/u/debit/apps/htdocs/QAdata/db/QAImtu.db', SQLITE3_OPEN_READWRITE);
        if (!$dbhandle) {
           errlog("WARN: CancelSaleRequest connection failed", $dbhandle);
           $result = array('Fault' => 
                         array ('faultcode' => 'Client',
    'faultstring' => 'The creator of this fault did not specify a Reason.',
				    'detail' => 'NotValidSessionTicketFault',
				    'NotValidSessionTicketFault' =>
array ('Message' => 'Not Valid Session Ticket Fault')));
           }
        else {
       $stmt1 = "SELECT xml FROM xmlData where app='".$app."' AND key='".$k . "'";
        $xmlData= $dbhandle->querySingle($stmt1, true);
        errlog("CancelSaleRequest xmlData retrieved from DB:" , $xmlData);
        $xmlData = $xmlData["xml"];
        $result = array('ValueOk' => 'true',
                       'Message' => 'OK',
                       'RequestTime' => new DateTime('NOW'),
         	       'ResponseTime' => new DateTime('NOW'));
       errlog("CancelSaleRequest response = ", $result);
       }
    }
    return $result;
}

} //SoapHandler

errlog('Main', $HTTP_RAW_POST_DATA);
//$data= file_get_contents('php://input');
//errlog("INPUT stream: $data\n\n");
// Here we seem to do this just like LIME/limeSvrR1715.php
//$server = new SoapServer($wsdl, array('soap_version' => SOAP_1_2));
$server = new SoapServer($wsdl, 
       array(//'uri' => "http://services.blackstoneonline.com/transactionbroker/",
	     'soap_version' => SOAP_1_2,
	     'trace' => 1));


$server->setClass("SoapHandler");
try {
$postdata = file_get_contents("php://input");
$server->handle($postdata);

errlog('handled:', $postdata);
errlog('handled:', $HTTP_RAW_POST_DATA);

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
 
