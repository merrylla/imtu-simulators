<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__));
// This should bd done in a similar fashion to SmartLoadSvrNew.php
include '../ezetop/ImtuTopUpClass.php';
include '../QAlib/wslib1.php';

$Products = null;

function FILOG( $str ) {
  $now = date("M j G:i:s");
  $pid = getmypid();
  $FIlogfile='/tmp/DingV3.log';
  $FIlog = fopen($FIlogfile,'a+');
    
  fwrite($FIlog, "$now [$pid] $str");
  fflush($FIlog);
  fclose($FIlog);
} // end FILOG

function getProduct($sku, $sendVal) {
  global $Products;
  errlog("called getProduct with SkuCode $sku and val: ", $sendVal);
  $product = null;
  if (is_null($Products)) {
    $products_json = file_get_contents(dirname($_SERVER['SCRIPT_FILENAME']) . "/products.json");
    $Products = json_decode($products_json);
  }
  if (is_null($sku)) {
    return null;
  }
  foreach ($Products->Items as $item) {
    if ($item->SkuCode == $sku) {
       $max = $item->Maximum;
       $min = $item->Minimum;
       errlog("Found SkuCode with max: ", $max);
       if (($max->SendValue == $sendVal) && ($min->SendValue == $sendVal)) {
          $product = $item;
          }
       else {
         errlog("Found SkuCode with max not same as : ", $sendVal);
       }
    }
  } // end foreach
  if (is_null($product)) {
    errlog("SkuCode not found: ", $sku);
  } else {
    errlog(" Found Product=", json_encode($product));
  }
  return $product;
}

function s_rand($numDigits) {
  $chars = '0123456789';
  $randStr = "";
  for ($i=0; $i<$numDigits; $i++) {
    $randInt = rand(0, 9);
    $randStr .= $chars[$randInt];
  }
  return $randStr;
}

function topup($request) {
  $Pin = s_rand(12); // retrieve the pin from the DB
  $msisdn = $request->AccountNumber;
  $Control = s_rand(10);
  $product = getProduct($request->SkuCode, $request->SendValue);
  $statKey = array('app' => 'Ding', 'key' => $msisdn);
  errlog("INFO: Topup found product: ", $request->SendValue);
  $xA = array('product' => 'a0',
              'resultCode' => 'a1',
              'errorCode' => 'a2',
              'context' => 'a3',
              'state' => 'a4',
              'seconds' => 'a5');
  $rA = array('product' => 'a0');
  $transferID = s_rand(10);
  $transKey = array('app' => 'Ding', 'key' => $transferID);
  $tpObj = new TopupObj($statKey, $xA, $rA);
  errlog("INFO: tpObj: ", $tpObj);
  $responseToProduce = $tpObj->xe['resultCode'];
  $errorCodeToProduce = $tpObj->xe['errorCode'];
  $contextToProduce = $tpObj->xe['context'];
  $stateToReturn = $tpObj->xe['state'];
  $secondsToWait = $tpObj->xe['seconds'];
  $xArr = array('a0' => $request->SkuCode,
              'a1' => $responseToProduce,
              'a2' => $errorCodeToProduce,
              'a3' => $contextToProduce,
              'a4' => $stateToReturn,
              'a5' => $secondsToWait);
  if ($responseToProduce != 0) {
     $result = array(
		    'ResultCode' => $responseToProduce,
		    'ErrorCodes' => array(array('Code' => $errorCodeToProduce, 
                    'Context' => $contextToProduce))
		    );
    $status = "Failed";
    $resultCode=5;
  } else {
    $status = $stateToReturn;
    $resultCode=1;
    $result = $tpObj->buildRspData($xArr);
    $xATr = array('a0' => $status,
              'a1' => $responseToProduce,
              'a2' => $errorCodeToProduce,
              'a3' => $contextToProduce,
              'a4' => $resultCode,
              'a5' => date("Y-m-d\TH:i:s.v"),
              'a6' => $request->SkuCode,
              'a7' => $request->SendValue,
              'a8' => $request->DistributorRef,
              'a9' => $Pin,
              'a10' => $secondsToWait-1);
    $xr = $product->Maximum->ReceiveValue / $product->Maximum->SendValue;
    $ReceiveValue = $request->SendValue * $xr;

    if (is_null($product->Maximum->TaxRate)) {
      $ReceiveValueExTax = $ReceiveValue;
    } else {
      $ReceiveValueExTax = $ReceiveValue * (1 - $product->Maximum->TaxRate);
    }
    if ($secondsToWait == 0) {
       $price = array('CustomerFee' => $product->Maximum->CustomerFee,
                      'DistributorFee' => $product->Maximum->DistributorFee,
                      'ReceiveValue' => $ReceiveValue,
                      'ReceiveCurrencyIso' => $product->Maximum->ReceiveCurrencyIso,
                      'ReceiveValueExcludingTax' => $ReceiveValueExTax,
                      'TaxRate' => $product->Maximum->TaxRate,
                      'TaxName' => $product->Maximum->TaxName,
                      'TaxCalculation' => $product->Maximum->TaxCalculation,
                      'SendValue' => $request->SendValue,
                      'SendCurrencyIso' => $product->Maximum->SendCurrencyIso,
                      'xchangeRate' => $xr);
       $status = "Complete";
       $completed = date("Y-m-d\TH:i:s.v");
       $receiptTxt = "Product: Ding USA\r\nValue: $request->SendValue\r\nPin: $Pin\r\nControl: $Control\r\n";
       $receiptParms = array('PIN'=>$Pin); 
       $errCodes = array(array('Code' => 'OK', 'Context' => 'OK'));
    } else {
       $price = null; 
       $completed= null; 
       $receiptTxt = null;
       $receiptParms = null;
       $errCodes = array();
    }
    $result = array(
		    'TransferRecord' => array('TransferId' => array('TransferRef' => $transferID,
								    'DistributorRef' => $request->DistributorRef),
					      'SkuCode' => $request->SkuCode,
					      "Price" => $price),
		    "CommissionApplied" => 0,
		    "StartedUtc" => date("Y-m-d\TH:i:s.v"),
		    "CompletedUtc" => $completed,
		    "ProcessingState" => $status,
		    "ReceiptText" => $receiptTxt,
		    "ReceiptParams" => $receiptParms,
		    "AccountNumber"=> $request->AccountNumber,
		    'ResultCode' => $resultCode,
		    'ErrorCodes' => $errCodes
		    );
    errlog("INFO: tpObj returning: ", $result);
    insertTransDB($transKey, $xATr);
    }
// write to DB so that reversal and listTrans can access these
  return $result;
}

function status($request) {
  errlog("INFO: status called with : ", $request);
  $statKey = array('app' => 'Ding', 'key' => $request->TransferRef);
  $xA = array('status' => 'a0',
              'response' => 'a1',
              'errorCode' => 'a2',
              'context' => 'a3',
	      'result' => 'a4',
              'date' => 'a5',
              'sku' => 'a6',
              'sendVal' => 'a7',
              'distrRef' => 'a8',
              'pin' => 'a9',
              'secondsLeft' => 'a10');
  $rA = array('product' => 'a0');
  $tpObj = new TopupObj($statKey, $xA, $rA);
  $errCode = $tpObj->xe['errorCode'];
  $context = $tpObj->xe['context'];
  $responseToProduce = $tpObj->xe['response'];
  $resultCode = $tpObj->xe['result'];
  $status = $tpObj->xe['status'];
  $startD = $tpObj->xe['date'];
  $pin = $tpObj->xe['pin'];
  $secs = $tpObj->xe['secondsLeft'];
  $skuCode = $tpObj->xe['sku'];
  $sendVal = (float) $tpObj->xe['sendVal'];
  $product = getProduct($skuCode, $sendVal);
  $xr = $product->Maximum->ReceiveValue / $product->Maximum->SendValue;
  $ReceiveValue = $sendVal * $xr;
  if (is_null($product->Maximum->TaxRate)) {
      $ReceiveValueExTax = $ReceiveValue;
  } else {
      $ReceiveValueExTax = $ReceiveValue * (1 - $product->Maximum->TaxRate);
  }
  if ($secs == 0) {
     $status = 'Complete';
     $price = array('CustomerFee' => $product->Maximum->CustomerFee,
		'DistributorFee' => $product->Maximum->DistributorFee,
		'ReceiveValue' => $ReceiveValue,
		'ReceiveCurrencyIso' => $product->Maximum->ReceiveCurrencyIso,
		'ReceiveValueExcludingTax' => $ReceiveValueExTax,
		'TaxRate' => $product->Maximum->TaxRate,
		'TaxName' => $product->Maximum->TaxName,
		'TaxCalculation' => $product->Maximum->TaxCalculation,
		'SendValue' => $sendVal,
		'SendCurrencyIso' => $product->Maximum->SendCurrencyIso,
		'ExchangeRate' => $xr);
      $completed = date("Y-m-d\TH:i:s.v");
      $receipt = "Product: Ding USA\r\nValue: $sendVal\r\nPin: $pin\r\n";
      $receiptParms = array();
      $errorCodes = array('Code' => '', 'Context' => '');
    } else {
      $price = null;
      $completed = null;
      $receipt = null;
      $receiptParms = null; 
      $errorCodes = array();
    }
  $xATr = array('a0' => $status,
              'a1' => $responseToProduce,
              'a2' => $errCode,
              'a3' => $context,
              'a4' => $resultCode,
              'a5' => $startD, 
              'a6' => $skuCode,
              'a7' => $sendVal,
              'a8' => $request->DistributorRef,
              'a9' => $pin,
              'a10' => $secs-1);
  $result = array(
		  "ResultCode" => 1,
		  'ErrorCodes' => array(array('Code' => '', 'Context' => '')),
		  'Items' => array(
				   array(
					 'TransferRecord' => array('TransferId' => array('TransferRef'=> $request->TransferRef, 'DistributorRef' => $request->DistributorRef),
					 'SkuCode' => $tpObj->xe['sku'],
					   "Price" => $price,
		    "CommissionApplied" => 0,
		    "StartedUtc" => $startD,
		    "CompletedUtc" => $completed,
		    "ProcessingState" => $status,
		    "ReceiptText" => $receipt,
		    "ReceiptParams" => $receiptParms,
		    'AccountNumber' => $request->AccountNumber),
		  "ResultCode" => 1,
		  'ErrorCodes' => $errorCodes)),
		  'ThereAreMoreItems' => false
		  );

  updateTransDBRec($request->TransferRef, $xATr, 'Ding');
  return $result;
}

function reversal($request) {
  $transRef = $request[0]->TransferId->TransferRef;
  $distRef = $request[0]->TransferId->DistributorRef;
  errlog("INFO: Reversal found transRef : ", $transRef);
  $statKey = array('app' => 'Ding', 'key' => $transRef);
  $xA = array('status' => 'a0',
              'response' => 'a1',
              'errorCode' => 'a2',
              'context' => 'a3',
	      'result' => 'a4',
              'date' => 'a5',
              'sku' => 'a6',
              'sendVal' => 'a7',
              'distrRef' => 'a8',
              'pin' => 'a9');
  $rA = array('product' => 'a0');
  $tpObj = new TopupObj($statKey, $xA, $rA);
  $skuCode = $tpObj->xe['sku'];
  $sendValue = $tpObj->xe['sendVal'];
  $newStatus=$tpObj->xe['status'];
  $resultCode=$tpObj->xe['result'];
  $response=$tpObj->xe['response'];
  $errorCd =$tpObj->xe['errorCode'];
  $context =$tpObj->xe['context'];
  $pin=$tpObj->xe['pin'];
  $distRef =$tpObj->xe['distrRef'];
  errlog("INFO: Reversal found sendValue as: ", $sendValue);
  $sendVal = (float) $sendValue;
  errlog("INFO: Reversal found float sendValue as: ", $sendVal);
  $product = getProduct($skuCode, $sendVal);
  errlog("INFO: Reversal found product with Sku $skuCode and: ", $sendVal);
  errlog("INFO: Reversal request: ", $request);
  if (is_null($product)) {
    $result = array(
		    'ResultCode' => 3,
		    'ErrorCodes' => array(array('Code' => 'ParameterInvalid', 'Context' => 'Unknown SkuCode'))
		    );
    $resultCode=3;
    $errorCd = "ParameterInvalid";
    $context = "Unknown SkuCode";
  } else {
    $xr = $product->Maximum->ReceiveValue / $product->Maximum->SendValue;
    $ReceiveValue = $sendVal * $xr;
    if (is_null($product->Maximum->TaxRate)) {
      $ReceiveValueExTax = $ReceiveValue;
    } else {
      $ReceiveValueExTax = $ReceiveValue * (1 - $product->Maximum->TaxRate);
    }
    $newStatus="Cancelled";
    $result = 
		    array('Items' => 
			  array( 
				array("TransferId" => array("TransferRef" => $transRef,
							    "DistributorRef" => $tpObj->xe['distrRef']),
			              "ProcessingState" => $newStatus,
			              "BatchItemRef" => $request[0]->BatchItemRef,
		                      "ResultCode" => 1,
		                      'ErrorCodes' => array(array('Code' => '', 'Context' => '')),
		    )),
		   "ResultCode" => 1,
		   'ErrorCodes' => array(array('Code' => '', 'Context' => '')));
  }
  $xATr = array('a0' => $newStatus,
              'a1' => $response,
              'a2' => $errorCd,
              'a3' => $context,
              'a4' => $resultCode,
              'a5' => date("Y-m-d\TH:i:s.v"),
              'a6' => $skuCode,
              'a7' => $sendValue,
              'a8' => $distRef,
              'a9' => $pin);
  updateTransDBRec($transRef, $xATr, 'Ding');
  return $result;
}

function quote($request) {
  errlog("INFO: Quote request        : ", $request[0]);
  $product = getProduct($request[0]->SkuCode, (float) $request[0]->SendValue);

  if (is_null($product)) {
    $result = array(
		    'ResultCode' => 3,
		    'ErrorCodes' => array(array('Code' => 'ParameterInvalid', 'Context' => 'Unknown SkuCode'))
		    );
  } else {
    $xr = $product->Maximum->ReceiveValue / $product->Maximum->SendValue;
    $ReceiveValue = $request[0]->SendValue * $xr;

    if (is_null($product->Maximum->TaxRate)) {
      $ReceiveValueExTax = $ReceiveValue;
    } else {
      $ReceiveValueExTax = $ReceiveValue * (1 - $product->Maximum->TaxRate);
    }

    $result = array('Items' => array(
				     array(
					   "Price" => array(
							    'CustomerFee' => $product->Maximum->CustomerFee,
							    'DistributorFee' => $product->Maximum->DistributorFee,
							    'ReceiveValue' => $ReceiveValue,
							    'ReceiveCurrencyIso' => $product->Maximum->ReceiveCurrencyIso,
							    'ReceiveValueExcludingTax' => $ReceiveValueExTax,
							    'TaxRate' => $product->Maximum->TaxRate,
							    'TaxName' => $product->Maximum->TaxName,
							    'TaxCalculation' => $product->Maximum->TaxCalculation,
							    'SendValue' => (float) $request[0]->SendValue,
							    'SendCurrencyIso' => $product->Maximum->SendCurrencyIso,
							    ), // end of Price array
					   "SkuCode" => $request[0]->SkuCode,
					   "BatchItemRef" => $request[0]->BatchItemRef,
					   "ResultCode" => 1,
					   'ErrorCodes' => array(array('Code' => '', 'Context' => ''))
					   )),
		      "ResultCode" => 1,
		      'ErrorCodes' => array(array('Code' => '', 'Context' => ''))
                       );
  }

  return $result;
}

function balance($request) {
  $result = array(
		  'Balance' => 421.66,
		  'CurrencyIso' => 'USD',
		  'ResultCode' => 1,
		  'ErrorCodes' => array(array('Code' => '', 'Context' => ''))
		  );

  return $result;
}

$request = array();
$result = json_encode(array(
		  'ResultCode' => 3,
		  'ErrorCodes' => array(array('Code' => 'OtherError',
					      'Context' => 'Unsupported action.'))
		  ));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
   $client_data = file_get_contents("php://input");
   errlog("INFO: POST --------------------------------------------------","\n");
   $request = json_decode($client_data);
   }
  if  (!empty($_POST)) {
      errlog("INFO: POST: ", $_POST);
      $reqRev=$_POST;
  }

   errlog("INFO: request: ", $request);
   $command = basename($_SERVER['REQUEST_URI']);
   errlog("INFO: command: ", $command);
  switch ($command) {
  case 'SendTransfer':
    $result = topup($request);
    break;
  case 'ListTransferRecords':
    $result = status($request);
    break;
  case 'CancelTransfers':
    $result = reversal($request);
    break;
  case 'EstimatePrices':
    $result = quote($request);
    break;
  case 'GetBalance':
    $result = balance($request);
    break;
  default:
   break;
  }

$response = json_encode($result);
errlog("INFO: response: ", $response);

header('Content-Type: application/json');
echo $response;


exit();
?>

