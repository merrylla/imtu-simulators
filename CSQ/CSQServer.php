<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__));

include '../ezetop/ImtuTopUpClass.php';
include 'wslib1.php';
$wsdl = "http://devcall02.ixtelecom.com/wsdl/CSQServer.wsdl";


date_default_timezone_set('America/New_York');

function randStr($len) {
  $ret = '';
  $dig = '0123456789';
  $d = rand(0,9);
  $newChar = $dig[$d];
  $ret = $newChar;
  for ($i=1; $i<$len; $i++) {
      $d = rand(0,9);
      $newChar = $dig[$d];
      $ret .= $newChar;
  }
  return $ret;
}

function  findErrorMsg($type,  $code) {
   $msg = "";
     errlog("INFO: findErrorMsg type: $type code ", $code);
       switch ($code) {
         case -1 :
          $msg = "Invalid parameters values / invalid parameters tags";
          break;
        case 0 :
         $msg = "Operation completed succesfully";
         break;
        case 10 :
         $msg = "Operation completed succesfully";
         break;
        case 11 :
         $msg = "Operation rejected by operator";
         break;
        case 12 :
         $msg = "Duplicate tranaction";
         break;
        case 13:
         $msg = "Insufficient center credit";
         break;
        case 14:
         $msg = "The card does not allow refills or it is at maximum capacity";
         break;
        case 15:
         $msg = "Pending operation. Try refilling again in 5 seconds.";
         break;
        case 16:
         $msg = "Temporarily unavailable product. Try again later";
         break;
        case 17:
         $msg = "Rejected for security reasons. Same refill was recently carried out.";
         break;
        case 20 :
         $msg = "Reversion succesfully completed";
         break;
        case 21 :
         $msg = "Reversion not accepted. Customer has spent part of the credit.";
         break;
        case 22 :
         $msg = "Operation to revert does not exists or was not previously completed";
         break;
        case 23 :
         $msg = "Operation was previously reversed";
         break;
        case 24 :
         $msg = "Cannot be reverted. Allowed time for reversion had expired.";
         break;
        case 25 :
         $msg = "Pending operation. Retry reversion later.";
         break;
        case 27 :
         $msg = "Original operation was not completed. There's no need to revert.";
         break;
        case 29 :
         $msg = "This product does not allow reversion.";
         break;
        case 30 :
         $msg = "Operation was completed succesfully";
         break;
        default:
         $msg = "xxx";
     } // end switch
   return $msg;
}
class SoapHandler {

//for CSQ  serviceversion ican make sure the web service is up
   function Query($p) {
     errlog("INFO: Query p = ", $p);
     $user = $p->user;
     errlog("Query user = ", $user);
     $pwd = $p->password; 
     errlog("Query pwd = ", $pwd);
     $ref = $p->localRef;
     errlog("Query ref = ", $ref);
     $hash = $p->hash;
     errlog("Query hash = ", $hash);
     $date = $p->date;
     errlog("Query date = ", $date);
     $time = $p->time;
     errlog("Query time = ", $time);
     $msisdn = $p->MSISDNDestination;
     errlog("Query msisdn = ", $msisdn);
     $op = $p->originalOpReference;
     errlog("Query op = ", $op);
     $opDate = $p->originalOpDate;
     errlog("Query opDate = ", $opDate);
     $getTick = $p->getTicket;
     errlog("Query getTick = ", $getTick);
     $term = $p->terminal; 
     errlog("Query term = ", $term);
     $key = array('app' => 'CSQ',
		  'key' => $msisdn);
     $xA = array('msisdn' => 'a0',
                  'ref' => 'a1',
		  'amt' => 'a2',
		  'transId' => 'a3',
		  'opId' => 'a4',
		  'bool' => 'a5');
     errlog("Topup xa = ", $xA);
     $rA = array('ref' => 'a1',
		'amt' => 'a2',
		'transId' => 'a3',
		'opId' => 'a4',
		'bool' => 'a5');
     errlog("Topup rA = ", $rA);
     $tpObj= new TopupObj($key, $xA, $rA);
     $rspA = array('QueryResult' => array('resultCode' => '0',
                   'resultMessage' => 'OK'));
      return $rspA;
   } // end of Query

//The following is an actual topup
   function Topup($p) {
     $rspA = array();
     errlog("Topup: p = ", $p);
     $user = $p->user;
     errlog("Topup user = ", $user);
     $pwd = $p->password; 
     errlog("Topup pwd = ", $pwd);
     $ref = $p->localRef;
     errlog("Topup ref = ", $ref);
     $hash = $p->hash;
     errlog("Topup hash = ", $hash);
     // I don't see what the key is and cannot confirm hash without it
     //verityHash($user, $ref, $pwd, $hash);
     $date = $p->date;
     $time = $p->time;
     $msisdn = $p->MSISDNDestination;
     $op = $p->operatorId;
     $amt = $p->amount;
     $getTick = $p->getTicket;
     $keyT=array('app' => 'CSQ', 'key' => $ref);
     $key=array('app' => 'CSQ', 'key' => $msisdn);
     $xA = array('msisdn' => 'a0',
                  'user' => 'a1',
		  'amt' => 'a2',
		  'reversed' => 'a3',
		  'testName' => 'a4',
		  'resultCode' => 'a5');
     errlog("Topup xa = ", $xA);
     $rA = array('user' => 'a1',
		'amt' => 'a2',
		'reversed' => 'a3',
		'testName' => 'a4',
		'resultCode' => 'a5');
     errlog("Topup rA = ", $rA);
     $xArr = array('a0' => $msisdn,
		'a1' => $user,
		'a2' => $amt,
		'a3' => 'false');
     updateTransDBRec($msisdn, $xArr, 'CSQ');
     insertTransDB($keyT, $xArr);
     $tpObj= new TopupObj($key, $xA, $rA);
     errlog("TopUp tpObj = ", $tpObj);
     $rspA = array();
     $successArray = array(0, 10, 20, 23, 30, 31, 92);
     if (!in_array($tpObj->re[resultCode], $successArray)) {
        $errMsg = findErrorMsg("TopUp",  $tpObj->re[resultCode]);
        $rspA = array('TopupResult' => array('resultCode' => $tpObj->re[resultCode],
		  'resultMessage' => $errMsg,
                  'Authorization' => NULL,
		  'DestinationAmount' => $amt,
		  'DestinationCurrency' => '???',
		  'Ticket' => NULL));
        errlog ("INFO Topup error response :", $rspA);
        } else {
     if (!$tpObj->re[resultCode]) {
         $tpObj->re[resultCode] = 10;
         
         }
         $massagedInput = array('msisdn' => $msisdn,
			    'user' => $user,
			    'amt' => $amt,
			    'reversed' => 'false',
			    'testName' => 'test',
			    'resultCode' => $tpObj->re[resultCode]);
     errlog ("INFO Topup about to validate with:", $tpObj->re);
     $tpObj->validateReqInput($massagedInput);
     errlog ("INFO Topup finished validate :", $tpObj->re);
     errlog ("INFO Topup topup result:", $tpObj->re);
     $rA = array(); 
     $rsp = $tpObj->buildRspData($xArr);
     $auth = 'Test-' + randStr(9); 
     $rspA = array('TopupResult' => array('resultCode' => $tpObj->re[resultCode],
		  'resultMessage' => 'Operation completed succesfully',
                  'Authorization' => $auth,
		  'DestinationAmount' => $amt,
		  'DestinationCurrency' => 'COP'));
     errlog ("INFO Topup response :", $rspA);
     }
   return $rspA;
   } // end of Topup


//The following is a transactionList request
  function TransactionList ($request) {
     errlog("TransactionList req = ", $request);
     $rspA = array();
     $tickArr = $request->SessionTicket;
     $ticket = $tickArr->Ticket;
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
     $xe = $tpObj->xe;
     $phone = $xe['phone'];
     errlog("GetSaleRecharge phone= ", $phone);
// set recharge state based on pphone number
     $firstDigit = substr($phone, 1, 1);
     errlog("GetSaleRecharge first= ", $firstDigit);
     $lastDigit = substr($phone, -1);
     errlog("GetSaleRecharge last= ", $lastDigit);
     $state = findState($lastDigit);
     $code = findStateCode($state);
     $saleSt = findSaleState($lastDigit);
     errlog("GetSaleRecharge status= ", $status);
     $price = $xe['price'];
     $response = array(
                      'SaleRecharge' => array('TransactionId' => $transId,
						'PhoneNumber' => $phone,
                                              'Price' => $price,
                   		      	      'Date' => new DateTime('NOW'),
                                              'RechargeStateCode' => $code,
                                              'RechargeState' => $state,
                                              'ProductID' => '999',
                                              'ProductName' => 'Sample',
                                              'Message' => 'OK',
                                              'ProductCurrency' => 'USD',
                                              'SaleState' => $saleSt),
			'Result' => array('ValueOk' => 'true',
                                        'Message' => 'OK',
                   		        'RequestTime' => new DateTime('NOW'),
         		                'ResponseTime' => new DateTime('NOW')));
    errlog("GetSaleRecharge response = ", $response);
    return $response;
  } // end of TransactionList

/*  This function is never called as Cubacel does not really want to
           support reversals.  Who knows.  It might be used eventually.
           This is the cancel topUp function for fixed price products */

  function Revert ($p) {
     errlog("Revert req = ", $request);
     $rspA = array();
     errlog("Revert : p = ", $p);
     $user = $p->user;
     errlog("Revert user = ", $user);
     $pwd = $p->password; 
     errlog("Rever pwd = ", $pwd);
     $ref = $p->localRef;
     errlog("Rever ref = ", $ref);
     $hash = $p->hash;
     errlog("Rever hash = ", $hash);
     $msisdn = $p->MSISDNDestination;
     errlog("Rever msisdn = ", $msisdn);
     $key=array('app' => 'CSQ', 'key' => $msisdn);
     $xmld = getDB('CSQ', $msisdn);
     errlog("INFO -Revert - order xmld", $xmld);
     $xA = array('msisdn' => 'a0',
                  'user' => 'a1',
		  'amt' => 'a2',
		  'reversed' => 'a3',
		  'testName' => 'a4',
		  'resultCode' => 'a5');
     $rA = array();
     $rspA = array();
     $tpObj= new TopupObj($key, $xA, $rA);
     if (!isset ($tpObj->xe['msisdn'])) { //|| $tpObj->xe['reversed']) {
       errlog("reverse unfound msisdn tpObj= ", $tpObj);
       $rspA = array('RevertResult' => array('resultCode' => 22,
		  'resultMessage' => 'Operation to revert does not exist or was not previously completed.',
                  'Authorization' => $auth,
		  'DestinationAmount' => $tpObj->xe['amt'],
		  'Ticket' => NULL));
       errlog("revert response = ", $rspA);
     } else if  (strcasecmp($tpObj->xe['reversed'],'false') != 0) {
       $rspA = array('RevertResult' => array('resultCode' => 23,
		  'resultMessage' => 'Operation was previously reversed',
                  'Authorization' => $auth,
		  'DestinationAmount' => $tpObj->xe['amt'],
		  'Ticket' => NULL));
       errlog("revert response already reversed = ", $rspA);
     } else {
       errlog("revert response after 1st 2 cases = ", $rspA);
        $dbhandle=new SQLite3('/u/debit/apps/htdocs/QAdata/db/QAImtu.db', SQLITE3_OPEN_READWRITE);
        if (!$dbhandle) {
           errlog("WARN: Revert connection failed", $dbhandle);
       $rspA = array('RevertResult' => array('resultCode' => 25,
		  'resultMessage' => 'Pending operation. Retry reversioon later',
                  'Authorization' => $auth,
		  'DestinationAmount' => $tpObj->xe['amt'],
		  'Ticket' => NULL));
           }
        else {
       $xArr = array('a0' => $msisdn,
		'a1' => $user,
		'a2' => $tpObj->xe['amt'],
		'a3' => 'true');
       updateTransDBRec($msisdn, $xArr, 'CSQ');
       $rspA = array('RevertResult' => array('resultCode' => 20,
		  'resultMessage' => 'Reversion successfully completed',
                  'Authorization' => $auth,
		  'DestinationAmount' => $tpObj->xe['amt'],
		  'Ticket' => NULL));
       errlog("Revert response = ", $result);
       }
    }
    return $rspA;
} // end of Revert

} //SoapHandler

errlog('Main', $HTTP_RAW_POST_DATA);
//$data= file_get_contents('php://input');
//errlog("INPUT stream: $data\n\n");

$saleServer = new SoapServer($wsdl, 
       array('uri' => "http://200.13.144.57:5976/VirtualPayment",
	     'soap_version' => SOAP_1_2,
	     'trace' => 1));

$saleServer->setClass("SoapHandler");
$saleServer->handle();
errlog('handled: ', $HTTP_RAW_POST_DATA);

?>
 
