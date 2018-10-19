<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__));

include '../ezetop/ImtuTopUpClass.php';
include '../QAlib/wslib1.php';
$salewsdl = "http://devcall02.ixtelecom.com/wsdl/Cubacel_SaleService.wsdl";

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

function findStateCode($state){
 errlog("findStateCode state= ", $state);
 $code = "";
 switch ($state) {
        case 'Pendiente' :
          $code = "PE";
	  break;
        case 'En proceso' :
          $code = "PR";
	  break;
        case 'Realizada' :
          $code = "OK";
	  break;
        case 'En Error' :
          $code = "ER";
	  break;
        case 'Anulada' :
          $code = "AN";
	  break;
        case 'Die' :
          $code = "DI";
	  break;
        default:
          $code = "AN";
       } // end switch
     errlog("findStateCode code= ", $code);
       return $code;
}

function findState($digit){
 errlog("findState digit= ", $digit);
 $state = "";
 switch ($digit) {
        case '3' :
          $state = "Pendiente";
	  break;
        case '4' :
          $state = "En proceso";
	  break;
        case '5' :
          $state = "Realizada";
	  break;
        case '6' :
          $state = "En Error";
	  break;
        case '7' :
          $state = "Anulada";
	  break;
        case '8' :
          $state = "Die";
	  break;
        default:
          $state = "Pendiente";
       } // end switch
     errlog("findState state= ", $state);
       return $state;
}

function findSaleState($digit){
 errlog("findSaleState digit= ", $digit);
 $state = "";
 switch ($digit) {
        case '3':
        case '4':
        case '5' :
        case '6':
          $state = "Activa";
	  break;
        case '7':
          $state = "Anulada";
	  break;
        default:
          $state = "Activa";
       } // end switch
     errlog("findSaleState state= ", $state);
       return $state;
}

class SoapHandler {

//for Cubacel AuthenticationEndpoint web service
   function GetBalance($p) {
     errlog("INFO: GetBlance p = ", $p);
     $ticket = $p->SessionTicket;
     errlog("GetBlance ticket = ", $ticket);
      $rspA = array('Balance' => 1234.56,
		    'Result' => array('ValueOk' => 'true',
                   'Message' => 'OK',
                   'RequestTime' => new DateTime('NOW'),
                   'ResponseTime' => new DateTime('NOW')));

      return $rspA;
   } //getBalance


//The following is an actual topup
   function SaleRecharge($p) {
     $rspA = array();
     errlog("SaleRecharge p = ", $p);
     $rechargeData = $p->RechargeData;
     $phone = $rechargeData->PhoneNumber;
     $price = $rechargeData->Price;
     errlog("SaleRecharge phone = ", $phone);
     $tickArr = $p->SessionTicket;
     $ticket = $tickArr->Ticket;
     $transId = $p->TransactionId;
     $keyT=array('app' => 'Cubacel', 'key' => $transId);
     $key=array('app' => 'Cubacel', 'key' => $phone);
     $orderId = randStr(7);
     $xA = array('phone' => 'a0',
                  'ticket' => 'a1',
		  'price' => 'a2',
		  'transId' => 'a3',
		  'orderId' => 'a4',
		  'bool' => 'a5',
		  'msg' => 'a6',
		  'errCode' => 'a7',
		  'errMsg' => 'a8');
     errlog("SaleRecharge xa = ", $xA);
     $rA = array('ticket' => 'a1',
		'price' => 'a2',
		'transId' => 'a3',
		'orderId' => 'a4',
		'bool' => 'a5',
		'msg' => 'a6',
                'errCode' => 'a7',
		'errMsg' => 'a8');
     errlog("SaleRecharge rA = ", $rA);
     $xArr = array('a0' => $phone,
		'a1' => $ticket,
		'a2' => $price,
		'a3' => $transId,
		'a4' => $orderId,
		'a5' => true,
		'a6' => 'Ok');
     updateTransDBRec($phone, $xArr, 'Cubacel');
     insertTransDB($keyT, $xArr);
     $tpObj= new TopupObj($key, $xA, $rA);
     $rspA = array();
     $immeditateExit = false;
     if ($tpObj->re[errCode]) {
        $errMsg = "Error " . $tpObj->re[errCode];
        if ($tpObj->re[errCode] == "503") {
            errlog("SaleRecharge immediate Exit");
            $immeditateExit = true;
            header("HTTP/1.0 503 Service Unavailable");
        } else if ($tpObj->re[errCode] == "500") {
            $immeditateExit = true;
            header("HTTP/1.0 500 Service Unavailable");
        } else if ($tpObj->re[errCode] == "202")
           $errMsg .= " - 9-La venta ingresada ya existe.";
        else if  ($tpObj->re[errCode] == "300") 
           $errMsg .= " - 27-La venta especificada no existe.";
        $rspA = array('OrderId' => $orderId,
		  'Result' => array('ValueOk' => false,
                                     'Message' => $errMsg,
                   		     'RequestTime' => new DateTime('NOW'),
         		             'ResponseTime' => new DateTime('NOW')));
        errlog ("INFO SaleRecharge error response :", $rspA);
        } else {
     $massagedInput = array('phone' => $phone,
			    'ticket' => $ticket,
			    'price' => $price,
			    'transId' => $transId,
			    'orderId' => $orderId,
			    'bool' => 'true',
			    'msg' => 'Ok');
     errlog ("INFO SaleRecharge about to validate with:", $tpObj->re);
     $tpObj->validateReqInput($massagedInput);
     errlog ("INFO SaleRecharge finished validate :", $tpObj->re);
     errlog ("INFO SaleRecharge topup result:", $tpObj->re);
     $rA = array(); 
     $rsp = $tpObj->buildRspData($xArr); 
     $rspA = array('OrderId' => $orderId,
		  'Result' => array('ValueOk' => $rsp['bool'],
                                     'Message' => $rsp['msg'],
                   		     'RequestTime' => new DateTime('NOW'),
         		             'ResponseTime' => new DateTime('NOW')));
     errlog ("INFO SaleRecharge response :", $rspA);
     }
     errlog("SaleRecharge immediate Exit= ", $immeditateExit);
     if ($immeditateExit )
        return array();
     else return $rspA;
   } // end of SaleRecharge


//The following is a transactionStatus request
  function GetSaleRecharge($request) {
     errlog("GetSaleRecharge req = ", $request);
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
     if ($code == "DI") 
        $response = array();
     else $response = array(
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
  } // end of GetSaleRecharge

/*  This function is never called as Cubacel does not really want to
           support reversals.  Who knows.  It might be used eventually.
           This is the cancel topUp function for fixed price products */

  function CancelSaleRequest ($request) {
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
} // End CancelSaleRequest

} //SoapHandler

errlog('Main', $HTTP_RAW_POST_DATA);
//$data= file_get_contents('php://input');
//errlog("INPUT stream: $data\n\n");

$saleServer = new SoapServer($salewsdl, 
       array('uri' => "http://200.13.144.57:5976/VirtualPayment",
	     'soap_version' => SOAP_1_2,
	     'trace' => 1));

$saleServer->setClass("SoapHandler");
$saleServer->handle();
errlog('handled: ', $HTTP_RAW_POST_DATA);

?>
 
