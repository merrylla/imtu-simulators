<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__));
$sessKey='IDT-MULTIRED-SESSION';

include '../ezetop/ImtuTopUpClass.php';
include '../QAlib/wslib1.php';


function sendRsp($a, $code=200)
{
$jmsg=json_encode($a);

$ht = new HttpResponse();
$ht->status($code);
$ht->setContentType('application/json');
$ht->setData($jmsg);
errlog("INFO: crResp sending resp back: ", $jmsg);
$ht->send();
}

function trans($ap)
{
    errlog("INFO trans - ", $ap);

    $p=json_decode($ap);
    errlog("INFO trans: usr info - ", $p);
    errlog("INFO trans product - ", $p->product_id);

    if ($p->product_id == '3001')
    	giftCard($p);
    elseif (productIDSpecifiesBillPay($p->product_id))
        internationalBillPay($p);
    elseif ($p->product_id != '0')
	topup($p);
    else
    {
	$rsp=array('status_code' => 1,
              'status_message_en' => 'Transaction failed - bad product_id',
              'creation_date' => round(microtime(true) * 1000),
              'transaction_number' => rand(100,999)
	);
        sendRsp($rsp); 
    }	
}

function topup($p)
{
    errlog("INFO topup - ", $p);

    $transaction=$p->transaction_id;
    $product=$p->product_id;
    $phone=$p->phone_number;
    $amount=$p->amount_usd;

    $xA=array('product_id' => 'a0',
              'amount_usd' => 'a1');

    $rA=array('phone_number' => 'a2',
              //'status_message_en' => 'a3',
              'receipt_text' => 'a3',
              'status_code' => 'a4',
              'status_code2' => 'a5',
              'status_message2' => 'a6' 
              );

    $rA1=array('phone_number' => $phone,
              'status_message_en' => 'Transaction successful',
              'status_code' => 0,
              'creation_date' => round(microtime(true) * 1000),
              'transaction_number' => rand(100,999),
              'authorization' => rand(7000000,9999999),
              'amount_usd' => $amount,
              'transaction_id' => $transaction,
              'product_id' => $product,
              'receipt_text' => 'receipt-text-4-compliance' 
              );

    $key = array('app' => 'Multired' ,'key' => $phone);

    $tpO= new TopupObj($key, $xA, $rA);

    $tpO->validateReqInput($p);

    $rA2=$rA1;
    
    if ( array_key_exists('status_code2', $tpO->re))
                $rA2['status_code']=$tpO->re['status_code2'];
    if ( array_key_exists('status_message2', $tpO->re)) 
                $rA2['status_message_en']=$tpO->re['status_message2'];
    if ( array_key_exists('receipt_text', $tpO->re)) 
                $rA2['receipt_text']=$tpO->re['receipt_text'];

    sessStore($transaction, $rA2);

    $rsp=$tpO->buildRspData($rA1);

    if ($rsp['status_code'] != 0) $rsp['status_message_en']='transaction failed';

    sendRsp($rsp); 

}

// This information is taken out of the Multired REST Web Service Interface
// which can be found in https://development.multiredglobalapi.com/docs/?username=idt
function internationalBillPay($p) {
    errlog("INFO internationalBillPay - ", $p);
    $transaction = $p->transaction_id;
    $product = $p->product_id;
//bottom of page 4 shows required parts for requesting a BillPay
    $billing_reference = $p->billing_reference;
    $billing_amt_mxn = $p->billing_amount_mxn;
//optional param follows but it is not really as it is needed to find the quote
    $quote_id = $p->quote_id;
    $sender = $p->sender_name;
    $sender_phone = $p->sender_phone_number;
//conditionally required param follows
    $billing_ref_parity = $p->billing_reference_parity;
//optional param follows
    $receiver = $p->beneficiary_name;
//optional param follows
    $receiver_phone = $p->beneficiary_phone_number;
    $xAQ=array('product_id' => 'a0',
              'billing_amount_mxn' => 'a1');
    $rAQ=array('cost_usd' => 'a2',
	      'expiration' => 'a3');
    $key = array('app' => 'Multired', 'key' => $quote_id);
    $tpQuery = new TopupObj($key, $xAQ, $rAQ);
    $tpQuery->validateReqInput($p);
    if (array_key_exists('cost_usd', $tpQuery->re)) {
       $quoteStatus = 0;
       $cost_usd = $tpQuery->re['cost_usd'];
       $message = 'Found Quote for transaction';
    } 
    else {
       $cost_usd = $billing_amt_mxn *.1 + 5.;
       $quoteStatus = -1;
       $message = 'Quote must precede transaction';
    }
//another topUp object was put into the DB by the test to create a particular situation
// The next few lines define how we will send XMLS_IMTU_SIM_INSTR2_REQ
// That is, the definition of all of the arguments to it are defined below
    $xA=array('sender_name' => 'a1');
//  This is already in the DB record as a key
	      //'billing_reference' => 'a3');
//The following fields are those that we want to take the values form the DB 
//record and returna in the response array.
    $rA=array('status_code' => 'a2',
	      'status_message_en' => 'a3');
    $key = array('app' => 'Multired', 'key' => $billing_reference);
    // find the record that says how to handle the test
    $tpObj = new TopupObj($key, $xA, $rA);
    $tpObj->validateReqInput($p);
    $stmt = sprintf("%d and %s", $quoteStatus, $message);
    errlog("INFO status and message sending - ", $stmt);
// The response object contains  all of the parameters that were POSTED (the next 10) plus ...
// page 7 lists all of these parts of any response from Multired
//top of page 4 of the Multired Web Service Interface document shows the two following
// parameters for requesting a BillPay
    $receipt = sprintf("SERVICIO PROPORCIONADO POR PAGOEXPRESS. EL PERIODO PARA LA APLICACION DEL PAGO ES DE 24 HRS. PARA CUALQUIER DUDA LLAME AL %d-%d-%d O %d-%d", rand(0,99999), rand(0.999), rand(0,9999), rand(0,9999), rand(0,9999));
    $respToTrans=array('transaction_id' => $transaction,
               'product_id' => $product,
               'billing_reference' => $billing_reference,
               'billing_reference_parity' => $billing_ref_parity,
               'billing_amount_mxn' => $billing_amt_mxn,
	       'sender_name' => $sender,
	       'sender_phone_number' => $sender_phone,
	       'quote_id' => $quote_id,
	       'status_code' => $quoteStatus,
		'status_message_en' => $message,
		'transaction_number' => rand(100,999),
		'creation_date' => round(microtime(true) * 1000),
		'receipt_text' => $receipt,
                'authorization' => rand(7000000,9999999),
	        'cost_usd' => $cost_usd);
    sessStore($transaction, $respToTrans);
    $rsp=$tpObj->buildRspData($respToTrans);
    if ($quoteStatus != 0) { 
       $rsp['status_code']=204; // billing reference expired
       $rsp['status_message_en']='Billing reference expired'; // really no quote found
   }
    sendRsp($rsp);
    $transId =  'T' . $transaction;
    date_default_timezone_set('Mexico/CST');
    $date  = date('m-d-Y');
    $key = array('app' => 'Multired', 'key' => $transId);
    $xA=array('a0' => $billing_reference,
		      'a1' => $cost_usd,
                       'a2' => $date);
    insertTransDB($key, $xA); //Add a record into the DB for this transaction
} // end of internationalBillPay

function giftCard($p)
{
    errlog("INFO giftCard - ", $p);

    $transaction=$p->transaction_id;
    $product=$p->product_id;
    $merchant=$p->merchant_id;
    $amount=$p->amount_usd;
    $sender=$p->sender_name;
    $receiver=$p->beneficiary_name;
    $receiver_phone=$p->beneficiary_phone_number;
    $xA=array('amount_usd' => 'a0'
	     );

    $rA=array('sender' => 'a1',
	      'status_code' => 'a2',
	      'status_message_en' => 'a3',
              'status_code2' => 'a4',
              'status_message2' => 'a5' 
              );

    $rA1=array('transaction_id' => $transaction,
              'product_id' => $product,
              'merchant_id' => $merchant,
	      'amount_usd' => $amount,
	      'sender_name' => $sender,
	      'beneficiary_name' => $receiver,
	      'beneficiary_phone_number' => $receiver_phone,
              'status_code' => 0,
	      'status_message_en' => 'Transaction successful',
              'transaction_number' => rand(100,999),
              'creation_date' => round(microtime(true) * 1000),
              'receipt_text' => 'giftcard-receipt-4-compliance', 
              'authorization' => rand(7000000,9999999),
	      'amount_credited_mxn' => ($amount * 12.66),
	      'pin' => rand(10000000,99999999)
              );

    $key = array('app' => 'Multired' ,'key' => $receiver_phone);

    $tpO= new TopupObj($key, $xA, $rA);

    errlog("INFO giftCard validating phone", $receiver_phone);
    $tpO->validateReqInput($p);
    errlog("INFO giftCard validated phone", $receiver_phone);


    $rA2=$rA1;
    
    if ( array_key_exists('status_code2', $tpO->re))
                $rA2['status_code']=$tpO->re['status_code2'];
    if ( array_key_exists('status_message2', $tpO->re)) 
                $rA2['status_message_en']=$tpO->re['status_message2'];
    if ( array_key_exists('receipt_text', $tpO->re)) 
                $rA2['receipt_text']=$tpO->re['receipt_text'];

    sessStore($transaction, $rA2);

    $rsp=$tpO->buildRspData($rA1);

    if ($rsp['status_code'] != 0) $rsp['status_message_en']='transaction failed';
    else {

       $transId =  'T' . $transaction;
       date_default_timezone_set('Mexico/CST');
       $date  = date('m-d-Y');
       $key = array('app' => 'Multired', 'key' => $transId);
       $xA=array('a0' => $receiver_phone,
		      'a1' => $cost_usd,
                       'a2' => $date);
       insertTransDB($key, $xA); //Add a record into the DB for this transaction
       }
    sendRsp($rsp); 
}

function authorizationFail()
{
    sendRsp(array(), 401);
}

function doReverse($tid)
// This function works for billPayReversal as well as for other reversals.  
//The amount of the reversal returned is always the same which might be 
// a problem in the future.  Since it is based on the transaction_id, it might 
// be a good idea to add in a table to the SQLite3 database
// If the reversal is not allowed a 0 is returned from the reversalAllowed function,
// for the amount of the reversal. Otherwise the correct amount is returned in that position.
// The next value in the array returned is the correct status code when the reversal is not allowed.
{
    errlog("INFO doReversal - ", $tid);
   $revStatus = reversalAllowed($tid); //checks the DB to see if the reversal should be allowed
    errlog("INFO doReversal revStatus - ", $revStatus);
   $rA = array();
   $xA = array ('cost_usd' => 'a1');
   $id = 'T' . $tid;
   $key = array ('app' => 'Multired', 'key' => $id);
   $tpObj = new TopupObj($key, $xA, $rA);
   $refund = $tpObj->xe['cost_usd'];
   $msg = 'No reversal was done';
   if ($revStatus == 0) {
      $rsp=array ('status_code' => 0,
                'status_message_en' => 'Reversal successful',
                'transaction_id' => $tid,
                'amount_usd' => $refund,
                'username' => 'IDT',
                'creation_date' => round(microtime(true) * 1000),
		'transaction_number' => rand(1000,9999),
		'refunded_transaction_number' => rand(1000,9999),
		'product_id' => -2);
     sendRsp($rsp);
     } else {
       switch($revStatus) {
	case 203: $msg = 'Billing reference invalid or billing reference parity incorrect';
	         break;
        case 204: $msg = 'Billing reference expired';
	         break;
        case 206: $msg = 'Payment not done due to suspected fraud. Notify support.';
	         break;
        case 302: $msg = 'PIN/Reference already redeemed.';
	         break;
        default: $msg = 'Unknown error. Program should be fixed.';
        } 
      $rsp=array ('status_code' => $revStatus,
                'status_message_en' => $msg,
                'transaction_id' => $tid,
                'amount_usd' => $refund,
                'username' => 'IDT',
                'creation_date' => round(microtime(true) * 1000),
		'transaction_number' => rand(1000,9999),
		'refunded_transaction_number' => rand(1000,9999),
		'product_id' => -2);
     sendRsp($rsp);
     reversalApplied($tid); //deletes the DB transaction record
     }
}//doReverse

function accBalance($p)
{
    $rsp=array('username' => 'IDT',
               'balance_usd' => 98343.56);

    sendRsp($rsp);

}//accBalance

function changePasswd($p)
{
   $pwd=$p->password;

   $rsp=array( 'result' => 'true');
   sendRsp($rsp);
}//changePasswd

function doStatus($tid)
{
    errlog("INFO doStatus - ", $tid);

    $rsp=sessRetrive($tid);
    // this is manual test for tc MTR_xerr_8_c
    // should be commented out under normal testing
    //$rsp['status_code']='3';

    if (EMPTY($rsp) ) 
         throw new Exception("Invalid transaction $tid", 400);

    sendRsp($rsp);

}//doStatus

//According to the Multired REST Web Service Interface document, billPayment products
//have the IDs listed in this function. They are on pages 15, 16 and 17 of that doucment.
function productIDSpecifiesBillPay($pid)
{
   if  ($pid == 101 || $pid == 102 || $pid == 103 || $pid == 104 || 
	$pid == 105 || $pid == 106 || $pid == 107 || $pid == 108 || 
	$pid == 110 || $pid == 111 || $pid == 113 || $pid == 115 ||
	$pid == 117 || $pid == 118 || $pid == 120 || $pid == 124 || 
	$pid == 125 || $pid == 127 || $pid == 130 || $pid == 131 || 
	$pid == 134 || $pid == 135 || $pid == 139 || $pid == 140 ||
	$pid == 141 || $pid == 142 || $pid == 144 || $pid == 145 || 
	$pid == 149 || $pid == 152 || $pid == 153) {
        return true;
        }
    else {
        return false;
        }
}

function getquote($ap, $pid)
/* This function works for getting a quote for billPay as well as 
 for others.  The quote_id returned is randomly created, which is fine.
 06/26/14 (MKA) - I changed this function so that it would return the posted
 parameters as well as the additional response parameters, as per the spec.
 Also made changes so that the amount_mxn parameter is only passed in and out
 for billPayQuote, since the spec also specified that. 
 Also made changes so that the amount_usd parameter is only passed in and out
 for giftCard quote, since the spec also specified that. */ 
{
    $p=json_decode($ap);
    errlog("getquote: usr info - ", $p);
    //$pid=$p->product_id;
    if ($pid == 3001) {
       $usd = $p->amount_usd;
       $rsp=array('product_id' => $pid,
//		'amount_usd' => $usd, my bad .. only needed for cash2pin
		'quote_id' => rand(1000, 9999),
// Perhaps the next 4 were needed for giftcards in the past but they are 
/* not needed now.
                'mxn_usd_exchange_rate' => 0.0765,
                'multired_currency_markup' => 0.05,
                'multired_fee_usd' => 10.0,
                'multired_total_usd' => 100, */
                'expires' => round(microtime(true) * 1000) + 24*3600+1000*10);
       }
/* If the product is a billPay product, then we need to store this information
   in the DB as well as respond to it. */
    elseif (productIDSpecifiesBillPay($pid)) {
            $mxn = $p->amount_mxn;
            $totalUSD = $mxn*(0.0768+0.005)+5.00;
	    $quoteId =  'Q' . rand(1000, 9999);
            $expireDate  = round(microtime(true) * 1000) + 24*3600+1000*10;
	    $key = array('app' => 'Multired', 'key' => $quoteId);
	    $xA=array('a0' => $pid,
		      'a1' => $mxn,
                       'a2' => $totalUSD,
			'a3' => $expireDate);
            insertQuoteDB($key, $xA); //Add a record into the DB for this quote
       	    $cost_usd = $totalUSD;
            $rsp=array('product_id' => $pid,
		'amount_mxn' => $mxn,
		'quote_id' => $quoteId, 
                'cost_usd' => $totalUSD,
                'expires' => $expireDate);
             } 
    else {
             $rsp=array('product_id' => $pid,
			'quote_id' => rand(1000, 9999),
               		'mxn_usd_exchange_rate' => 0.0765,
                	'multired_currency_markup' => 0.05,
                	'multired_fee_usd' => 10.0,
                	'multired_total_usd' => 100,
                'expires' => round(microtime(true) * 1000) + 24*3600+1000*10);
	  } 
     sendRsp($rsp);
} //getquote

function parseAction($get, $p)
{
    errlog("parseAction: usr info - ", $get);

    $cmd=$get['cmd'];

    switch ($cmd) {

        case 'transaction':

           if ($get['subcmd'] == 'reverse') 
                 doReverse($get['tid']);
           elseif (isset($get['tid']))
                 doStatus($get['tid']);
           else
		 trans($p);
           break;
        case 'account':
              accBalance($p);
              break;
        case 'password':
              changePasswd($p);
              break;
        case 'product':
            if ($get['subcmd'] == 'quote') 
                 getquote($p, $get['tid']);
              break;
        default:
            throw new Exception("Invalid URL $cmd", 400);
     }
    
}//parseAction


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
