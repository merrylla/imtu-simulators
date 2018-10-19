<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__));

include '../ezetop/ImtuTopUpClass.php';
include '../QAlib/wslib1.php';
$wsdl = "http://devcall02.ixtelecom.com/wsdl/DollarPhone.wsdl"; 
date_default_timezone_set('America/New_York');

function randStr($numDigits) {
    $chars = '0123456789';
    $randStr = "";
    for ($i=0; $i<$numDigits; $i++) {
        $randInt = rand(0, 9);
        $randStr .= $chars[$randInt];
    }
    return $randStr;
}


class SoapHandler {

public  $ResponseMessages = array(
				   '0'    => 'Success',
				   '1'    => 'Success',
				   '-1'   => 'Invalid Login',
				   '-2'   => 'Invalid Login',
				   '-6'   => 'Invalid offering',
				   '-34'  => 'Account past due',
				   '-35'  => 'Transaction exceeds credit limit',
				   '-40'  => 'Invalid Phone number',
				   '-41'  => 'Invalid amount',
				   '-42'  => 'Invalid Product',
				   '-400' => 'Invalid phone number',
				   '-401' => 'Processing error',
				   '-402' => 'Transaction already completed',
				   '-403' => 'Invalid transaction amount',
				   '-404' => 'Invalid product',
				   '-405' => 'Duplicate transaction',
				   '-406' => 'Invalid Transaction Id',
				   '-407' => 'Denomination currently unavailable',
				   '-408' => 'Transaction amount limit exceeded',
				   '-409' => 'Destination Account is not prepaid',
				   '-410' => 'Handset was reloaded within the last 10 minutes',
				   '-411' => 'TopUp refused'
				   );
  function TopUpRequest($request) {

    errlog("TopUpRequest request: ", $request);
    $action = $request->TopUpReq->Action;
    $amount = $request->TopUpReq->Amount;
    $msisdn = $request->TopUpReq->PhoneNumber;
    $orderId= $request->TopUpReq->OfferingId;
    $transid = randStr(5);
    $pin = '';

    if (strcasecmp($request->TopUpReq->Action, 'PurchasePin') == 0) {
	$pin = $this->s_rand(10);
    }
    $keyT=array('app' => 'DollarPhone', 'key' => $transid);
    $key=array('app' => 'DollarPhone', 'key' => $msisdn);
    $xA = array('phone' => 'a0',
                  'orderId' => 'a1',
                  'transid' => 'a2',
                  'pin' => 'a3',
                  'errCode' => 'a4',
                  'errMsg' => 'a5');
    errlog("TopUpRequest xa = ", $xA);
     $rA = array('orderId' => 'a1',
                'transid' => 'a2',
                  'pin' => 'a3',
                  'errCode' => 'a4',
                  'errMsg' => 'a5');
     errlog("TopUpRequest rA = ", $rA);
     $xArr = array('a0' => $msisdn,
                'a1' => $orderId,
                'a2' => $transid,
                'a3' => $pin);
     updateTransDBRec($msisdn, $xArr, 'DollarPhone');
     insertTransDB($keyT, $xArr);
     $tpObj= new TopupObj($key, $xA, $rA);
     errlog("TopUpRequest tpObj = ", $tpObj);
     if ($tpObj->re[errCode]) {
        $code = $tpObj->re[errCode];
        errlog("TopUpRequest set errMsg & errCode= $error", $errMsg);
     } else {
        $code = '1';
        errlog("TopUpRequest errCode= $code  transid", $transid);
        $massagedInput = array('phone' => $msisdn,
                                'orderId' => $orderId,
                                'transid' => $transid,
                                'pin' => $pin,
                                'errCode' => 0,
				'errMsg' => 'Success');
        $tpObj->validateReqInput($massagedInput);
        // The following will make sure the delay or abort happens
        $tpObj->buildRspData($xArr);
        errlog ("TopUpRequest topup result:", $tpObj->re);
       }
     if ($this->ResponseMessages[$code])  {
       $msg = $this->ResponseMessages[$code];
     } else {
       $msg = "Unknown Error";
     }
     $response = array('TopUpRequestResult' =>
                      //array('responseMessage' => $msg,
                       array('responseCode' => $code,
                            'TransId' => $transid
                            )
                      );
     errlog ("TopUpRequest returning:", $response);
     print_r($response, true);
     return $response;
   } // end of TopUpRequest


  function TopupConfirm($request) {
     $transid = $request->TransID;
     errlog ("TopUpConfirm transid:", $transid);
     $status = 'Invalid';
     $code = '-406';
    $pin = NULL;
    if ($transid > 0) {
        $xA = array('phone' => 'a0',
                  'orderId' => 'a1',
                  'transid' => 'a2',
                  'pin' => 'a3');
        errlog("TopUpConfirm xa = ", $xA);
        $rA = array('orderId' => 'a1',
                'transid' => 'a2',
                  'pin' => 'a3');
        $key=array('app' => 'DollarPhone', 'key' => $transid);
        $tpObj= new TopupObj($key, $xA, $rA);
        errlog("TopUpConfirm tpObj->xe ", $tpObj->xe);
        errlog("TopUpConfirm tpObj ", $tpObj);
	if (!isset ($tpObj->xe['phone'])) {
	  errlog("TopUpConfirm topupObj: ", $tpObj);
	  $pin = NULL;
	  $id = $transid;
          $err = -406;
          
        } else {
	  $pin = $tpObj->xe['pin'];
	  $id = $transid;
	  $err =  0;
	  $errMsg =  "Success";
        }
        if ($err == 0) {
           $status = "Success";
        } else {
          $status = "Failed";
        } 
      $result = array('TopupConfirmResult' =>array('Status' => $status,  
                      'ID' => $id,
 		      'ErrorCode' => $err,
		      'ErrorMsg' => $errMsg,
		      'Pin' => $pin));
      errlog("TopUpConfirm response = ", $result);
      }
      return $result;
    } // end of TopupConfirm function

  function GetTopupProducts($request) {
    errlog("GetTopupProducts request = ", $request);
    $response = simplexml_load_file(dirname($_SERVER['SCRIPT_FILENAME']) . "/products.xml");
    $response = json_decode(json_encode($response), true);
    errlog("GetTopupProducts response = ", $response);
    return $response;
  } // end of GetTopupProducts
  } // SoapHandler


errlog('Main', $HTTP_RAW_POST_DATA);

$server = new SoapServer($wsdl,array(
                               'uri' => "urn:https://dollarphone.com/PMAPI/PinManager",
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
