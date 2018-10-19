<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__));

include '../Remittance/rmttObjBase.php';
include '../Remittance/dbClass.php';
include '../QAlib/wslib2.php.mka';
include '../QAlib/soaplib.php';
include 'SochitelData.php';
include '../QAlib/ImtuTopUpClass.php.mka';

$sessName='IDT-TRFTO-SESSION';
$IDTtrfToSession=99124;
session_name($sessName);
$password='o53ixg2ca';

date_default_timezone_set('America/New_York');

function dateformat() { return date("d-m-Y H:i:s");}

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

// Since this calls for no input parameters and sends simple json output
// it seemed this woudl be a good first one to try as fully json.
//
function transData($p){
     $funcName=__FUNCTION__;
     errlog("DEBUG: processing $funcName", $p);
     $authArr = $p->auth;
     $username=$authArr->username;;
     errlog("DEBUG: processing $funcName user: ", $username);
     $password=$authArr->password;;
     $id = '';
     if (isset($p->id))
        $id=$p->id;
// CFW R16.20Added id as confirmation id required by intlTopup
     else
        $id=rand();
     $ref = '';
     if (isset($p->userReference))
        $ref = $p->userReference;
     else
        $ref=rand(10000,99999);
     $retCode=100;
     $retmsg='Successful';
     $key = array('app' => 'sockitel' ,'key' => $ref);
     // key for Epin
     errlog("DEBUG: key: ", $key);
     $tpO= new TopupObj1($key);
     errlog("DEBUG: finished setting up tpO status:", $tpO); 
     $ct= $tpO->retrieveContext();
     errlog("DEBUG: found context", $ct); 
     errlog("DEBUG: finished retrieving context :", $tpO); 
     $context=array();

     if ($ct == '') {
        $context['status'] = 22;
        $context['operator']='';
        $context['msisdn']='';
        $context['amount']='';
        $retmsg='TRANSACTION_NOT_FOUND';
     errlog("DEBUG: finished setting context :", $context); 
     }
     else
        $context= (array) json_decode($ct);
     errlog("DEBUG: finished setting context with if :", $context); 

     $statusInside = array('id' => $context['status'],
			'name' => $retmsg,
			'type' => $context['status'],
			'typeName' => $retmsg
			);
     $opArr = array('id' => $context['operator'],
		    'name' => 'Nigeria MTN',
			'reference' => rand(100000, 9999999));
     $amtArr = array('user' => $context['amount'],
			'operator' => '100.00');
     $currArr = array('user' => 'USD',
			'operator' => 'NGN'); 
     $chanArr = array('channel' => 'API',
			'reference' => rand(100000, 999999999)); 
     $rspInside = array('id' => $id,
			'productType' => array('id' => 1, 
				'name' => 'Mobile Top Up'),
			'userReference' => $ref,
			'operator' => $opArr,
			'status' => $statusInside,
			'date' => '2014-09-24 18:19:00',
			'amount' => $amtArr,
			'currency' => $currArr,
			'channel' => $chanArr,
			'note' => 'special notes',
			'msisdn' => $context['msisdn']
			);
     //throw in the delay if it is required
     $rsp=array('status' => $statusInside,
		'command' => 'getTransactionData',
		'timestamp' => round(microtime(true) * 1000),
		'reference' => rand(100000000,99999999),
     		'result' => $rspInside); 
     //  CFW R15.15 added 3rd response PSPD-464
     if ($context['status'] == 9) {
         $context['status'] = 0;
     errlog("DEBUG: finished setting content d :", $d); 
	 $d = json_encode($context);
	 $tpO->saveContext($ref, $d);
     errlog("DEBUG: finished saving context for tpO :", $tpO); 
         } 

     $tpO->buildRspData($rsp);
     $rsp=json_encode($rsp);
     errlog("DEBUG: about to send resp from transData:", $rsp); 
     crResp($rsp); 
     errlog("DEBUG: sent resp from transData:", $rsp); 
} //transData

function getBal()
{
     $funcName=__FUNCTION__;
     errlog("DEBUG: processing $funcName", "\n");
     $statusInside = array('id' => 0,
			'name' => 'Successful',
			'type' => 0,
			'typeName' => 'Success'
			);
     $rspInside = array('value' => 100.99,
			'currency' => 'EUR'
			);
     $rsp=array('status' => $statusInside,
		'command' => 'getBalance',
		'timestamp' => round(microtime(true) * 1000),
		'reference' => 'yyy',
     		'result' => $rspInside); 
     sendRsp($rsp);
}//getBal

function commands()
{

     $funcName=__FUNCTION__;
     errlog("DEBUG: processing $funcName", "\n");
     $cmd1 = array('command' => 'mobileTopUp',
		   'description' => 'Execute or simulate a mobile top up');
     $cmd2 = array('command' => 'getCurrencies',
                   'description' => 'Retrieve the list of currencies');
     $cmd3 = array('command' => 'getOperators',
                   'description' => 'Retrieve the list of operators');
     $cmd4 = array('command' => 'getOperatorProducts',
                   'description' => "Retrieve the list of operator's products");
     $cmd5 = array('command' => 'getTransactionData',
                   'description' => 'Retrieve data of a transaction');
     $cmd6 = array('command' => 'mobilePin',
                   'description' => 'Execute a mobile PIN request');
     $cmd7 = array('command' => 'getBalance',
                   'description' => "Retrieve the user's balance");
     $cmd8 = array('command' => 'parseMsisdn',
                   'description' => 'Parse phone number');
     $cmd9 = array('command' => 'getErrors',
                   'description' => 'Retrieve the list of error codes');
     $cmd10 = array('command' => 'getErrorTypes',
                   'description' => 'Retrieve the list of error types');
     $cmd11 = array('command' => 'getLocations',
                   'description' => 'Retrieve the list of locations');
     $cmd12 = array('command' => 'getCommands',
                   'description' => 'Retrieve list of the commands');
     $cmd13 = array('command' => 'getProductTypes',
                   'description' => 'Retrieve the list of product types');
     $cmd14 = array('command' => 'getStatistics',
                   'description' => 'Retrieve user statistics');
     $rsp=array('result' => 
         array('command1' => $cmd1,
		'command2' => $cmd2,
		'command3' => $cmd3,
		'command4' => $cmd4,
		'command5' => $cmd5,
		'command6' => $cmd6,
		'command7' => $cmd7,
		'command8' => $cmd8,
		'command9' => $cmd9,
		'command10' => $cmd10,
		'command11' => $cmd11,
		'command12' => $cmd12,
		'command13' => $cmd13,
		'command14' => $cmd14,
		)
	); 
     sendRsp($rsp);
}

function currencies()
{

     $funcName=__FUNCTION__;
     errlog("DEBUG: processing $funcName", "\n");
     $rsp=array('result' => 
         array('currencyId1' => 'USD',
		'currencyId2' => 'EUR',
		'currencyId3' => 'MXN',
		'currencyId4' => 'GBP',
		'currencyId5' => 'JPY',
		'currencyId6' => 'JOD',
		'currencyId7' => 'PHP',
		'currencyId8' => 'CNY',
		'currencyId9' => 'CAD',
		'currencyId10' => 'ARS',
		)
	);
     sendRsp($rsp);
}

function operators($p)
{

     $funcName=__FUNCTION__;
     errlog("DEBUG: processing $funcName ", "\n");
     if (isset($p->location)) {
        $location=$p->location;
     } else {
        $location = 0;
       errlog("DEBUG: in $funcName location = ", "$location ");
     }
     if (isset($p->local)) {
        $local = $p->local;
     } else {
        $local = 0;
        errlog("DEBUG: processing $funcName with local $local", "\n");
     }
     if (isset($p->productType)) {
        $productType = $p->productType;
     } else {
        $productType = '0';
        errlog("DEBUG: in $funcName prodTYpe = $productType ", "\n");
     }

     errlog("DEBUG: processing $funcName", "\n");
     $prodTyp1 = array ('id' => '1',
			'name' => "Mobil Top Up");
     $prodTyp2 = array ('id' => '2',
			'name' => "Boss Revolution");
     $cnty1 = array('id' => 'AF',
		    'name' => 'Afghanistan',
		    'prefixes' => array("93"),
		    'operatorPrefixes' => array ("93", "9377", "9376", "9378"),
		    'msisdnLength' => array ('min' => 11, 'max' => 11));
     $cnty2 = array('id' => 'CN',
		    'name' => 'China',
		    'prefixes' => array("98"),
		    'operatorPrefixes' => array ("98", "9877", "9876", "9878"),
		    'msisdnLength' => array ('min' => 11, 'max' => 11));
     $cnty3 = array('id' => 'MX',
		    'name' => 'Mexico',
		    'prefixes' => array("38"),
		    'operatorPrefixes' => array ("38", "3877", "3876", "3878"),
		    'msisdnLength' => array ('min' => 10, 'max' => 10));
     $cnty4 = array('id' => 'IS',
		    'name' => 'Israel',
		    'prefixes' => array("92"),
		    'operatorPrefixes' => array ("44", "3838", "9999", "8878"),
		    'msisdnLength' => array ('min' => 10, 'max' => 11));
     $op1 = array('id' => '49',
		  'name' => 'Asefa Wireless',
		  'productType' => $prodTyp1,
		  'currency' => 'AFN',
		  'location' => array ( '3', '10'),
		  'prefixes' => array( '9377', '9376'),
		  'country' => $cnty1); 
     $op2 = array('id' => '1',
		  'name' => 'Ying',
		  'productType' => $prodTyp1,
		  'currency' => 'CNY',
		  'location' => array ( '3', '10'),
		  'prefixes' => array( '9377', '9376'),
		  'country' => $cnty2);
     $op3 = array('id' => '2',
		  'name' => 'Juanita',
		  'productType' => $prodTyp2,
		  'currency' => 'MXN',
		  'location' => array ( '2', '11'),
		  'prefixes' => array( '819', '836'),
		  'country' => $cnty3);
     $op4 = array('id' => '3',
		  'name' => 'Shulamit',
		  'productType' => $prodTyp2,
		  'currency' => 'ILS',
		  'location' => array ( '4', '18'),
		  'prefixes' => array( '615', '516'),
		  'country' => $cnty4);
     if ($location==0) {
	   $local = 0;
        }
     $opArr = array('49' => $op1, '1' => $op2, 
		'2' => $op3);
     if (($productType==0) && ($local==0)) {
       //$opArr = array_push_assoc($opArr, 'operatorId3', $op1);
       $opArr['3'] = $op4;
     } elseif ($productType==1) {
       //$opArr = array_push_assoc($opArr, 'operatorId3', $op1);
       $opArr['operatorId3'] = $op1;
       $opArr['operatorId4'] = $op2;
       //$opArr = array_push_assoc($opArr, 'operatorId4', $op2);
     } elseif ($local == 2) {
            if ($productType == 2) {
               $opArr['operatorId5'] = $op3;
            } else {
              $opArr['operatorId3'] = $op1;
              $opArr['operatorId4'] = $op2;
            }
     } else {
       errlog("DEBUG: fell through ifthen $funcName with prodTYpe = $productType & loc = $local & ", $opArr);
     }
     $statusInside = array('id' => 0,
			'name' => 'Successful',
			'type' => 0,
			'typeName' => 'Success'
			);
     $rsp=array('status' => $statusInside,
		'command' => 'getOperators',
		'timestamp' => round(microtime(true) * 1000),
		'reference' => 4677747345,
                'result' => $opArr); 
     sendRsp($rsp);
} // end of operators

function products($p)
{

     $funcName=__FUNCTION__;
     errlog("DEBUG: processing $funcName ", "\n");
     $operator=$p->operator;
     $typArr = array('id' => 1, 'name' => 'Mobile Top Up');
     $priceObject1 = array('operator' => 2500, 'user' => 2943);
     $priceObject2 = array('operator' => 3500, 'user' => 3854);
     $priceObject3 = array('operator' => 4500, 'user' => 4765);
     $prArr1 = array('id' => 1, 'type' => 'range', 'name' => 'samIAm', 
		    'price' => $priceObject1);
     $prArr2 = array('id' => 2, 'type' => 'fixed', 'name' => 'mrFox', 
		    'price' => $priceObject2);
     $prArr3 = array('id' => 3, 'type' => 'range', 'name' => 'redHen', 
		    'price' => $priceObject3);
     $prArr = array('productId0' => $prArr1, 'productId1' => $prArr2, 
		'productId2' => $prArr3);
     $curArr = array('user' => 'GBP', 'operator' => "EUR");
     $opArr = array('type' => $typArr, 'products' => $prArr,  
		     'currency' => $curArr); 
     $statusInside = array('id' => 0,
			'name' => 'Successful',
			'type' => 0,
			'typeName' => 'Success'
			);
     $rsp=array('status' => $statusInside,
		'command' => 'getOperatorProducts',
		'timestamp' => round(microtime(true) * 1000),
		'reference' => 4677747345,
                'result' => $opArr); 
     sendRsp($rsp);
} // end of products 

function statusQuery($p)
{
     $funcName=__FUNCTION__;

     $username=$p['username'];
     $password=$p['password'];
     $serial=$p['serial'];

     $id='';
     if (isset($p['id']))
	$id=$p['id'];
// CFW R16.20Added id as confirmation id required by intlTopup
     else
	$id=rand();

     $retCode=100;
     $retmsg='Successful';

     $key = array('app' => 'sockitel' ,'key' => $serial);
     $tpO= new TopupObj1($key);
     $ct= $tpO->retrieveContext();

     $context=array(); 

     if ($ct == '') {

        $context['status'] = 22;
        $context['operator']=''; 
        $context['msisdn']=''; 
        $context['amount']=''; 
        $retmsg='TRANSACTION_NOT_FOUND';
     }
     else
        $context= (array) json_decode($ct);
     

     $rdata=array('status' => 0,
              'statusString' => 'Successful',
              'command' => 'getTopupData',
              'result' => array('id' => $id,
                                'serial' => $serial,
                                'status' => $context['status'], 
                                'statusString' => $retmsg,
                                'date' =>  dateformat(),
                                'operator' => $context['operator'],
                                'operatorName' => 'Nigeria MTN',
                                'msisdn' => $context['msisdn'],
                                'amount' => $context['amount'].'USD',
                                'amountUser' => '0.70USD',
                                'amountOperator' => '100.00NGN')
              ); 
     //  CFW R15.15 added 3rd response PSPD-464
     if ($context['status'] == 9)
     {
	$context['status'] = 0;
	$d=json_encode($context);
	$tpO->saveContext($serial,$d);
     }
    
     $rsp=json_encode($rdata);
     crResp($rsp);


}//statusQuery

function mobileP($p)
{

     $funcName=__FUNCTION__;
     errlog("DEBUG: processing $funcName", $p);
     $authArr = $p->auth;
     $username=$authArr->username;
     errlog("DEBUG: processing $funcName user: ", $username);
     $password=$authArr->password;
     $operator=$p->operator;
     $msisdn=$p->msisdn;
     $amount=$p->amount;
     $prID = 0;
     if (!isset($p->productId)) {
        $prId = 1;
     } else {
       $prId = $p->productId; 
     }
     $ref = $p->userReference;
     errlog("DEBUG: $funcName values: $username, $password", $operator); 
     $massagedInput = array('username' => $username, 'password' => $password, 'operator' => $operator, 'msisdn' => $msisdn, 'amount' => $amount, 'serial' => $ref);
     $xA = array('username' => 'a0', 'operator' => 'a1', 'amount' => 'a2');
     $rA = array('status' => 'a3', 'statusString' => 'a4', 'pin' => 'a5', 'status2' => 'a6');
     $key = array('app' => 'sockitel' ,'key' => $msisdn);
     errlog("DEBUG: $funcName first using key: ", $key); 
     $tp1= new TopupObj1($key);
     $tp1->TopupSetup($xA, $rA);
     errlog("DEBUG: finished setting up tp1 status:", $tp1); 
     $tp1->validateReqInput($massagedInput);
     errlog("DEBUG: finished validate of tp1 ", $tp1); 
     $key2 = array('app' => 'sockitel' ,'key' => $ref);
     errlog("DEBUG: $funcName for tp2 using key: ", $key2); 
     $tp2= new TopupObj1($key2);
     $tp2->TopupSetup($xA, $rA);
     errlog("DEBUG: finished settting up tp2 :", $tp2); 
     //$tp2->validateReqInput($massagedInput);
     errlog("DEBUG: finished validating tp2 :", $tp2); 

     $status=0;
     $statusString='Successful'; 
     $result1 = $tp1->re; 
     $pin=$result1["pin"];
     errlog("DEBUG: set pin to return as:", $pin); 
     
     $result2 = $tp2->re; 
     if (sizeof($result1) > 0) {
        errlog("DEBUG: found tp1 status set", $tp1); 
        $status= $result1['status'];
        if ($status == "0") {
           $statusString = 'Successful';
        } else {
        $statusString= $result1['statusString'];
        }
     } else if (sizeof($result2) > 0) {
        $status= $result2['status'];
        $statusString= $result2['statusString'];
     } else {
        $status= 0;
        $statusString= 'Successful';
     }
       
     $bal=1000;
     $balAfter=$bal-$amount;
     $op = array('id' => $operator, 'name' => 'Merryll', 'reference' => rand(100000000,99999999));
     if (empty($amount)) {
        $amount = number_format($opAmount/5510.01, 2, '.', '');
     } else {
       $opAmount = number_format($amount*5510.01, 2, '.', '');
     }
     $amt = array('operator' => $opAmount, 'user' => $amount);
     $cur = array('user' => 'GBP', 'operator' => 'NGN');
     $commPercent = 10.0;
     $commCost = $amount * $commPercent * .01;
     $transAmt = $amount + ($amount * $commPercent * .01);
     $balArr = array('initial' => 0, 'transaction' => $transAmt,
		     'commission' => $commCost, 
                     'commissionPercentage' => $commPercent,
		     'final' => 61.50, 'currency' => 'GBP');
     $altArr = array('29', '53');
        $statusInside = array('id' => $status,
			'name' => $statusString,
			'type' => $status,
			'typeName' => $statusString
			);
     $curInside=array('user'=> $amt,
 		'operator' => $opAmt);
     $pinArr=array('number' => $pin,
 		'serial' => $pin,
                'instructions' => "Instruction");
     
     $rspInside= array('id' => rand(1000000, 9999999),
		'amount' => $amt,
		'currency' => $cur,
		//'currency' => $curInside,
		'productId' => $prId,
		'userReference' => $ref,
		'balance' => $balArr,
		'pin' => $pinArr,
		'alternativeProducts' => $altArr
		);
     $rsp=array('status' => $statusInside,
		'command' => 'mobilePin',
		'timestamp' => round(microtime(true) * 1000),
		'reference' => rand(100000000,999999999),
     		'result' => $rspInside);
     if (isset($tp1->re['status2'])) {
         errlog("DEBUG: mobileP saving context status", '/n');
         $context = array('serial' => $ref, 'username' => $username, 'operator' => $operator,
                          'msisdn' => $msisdn, 'amount' => $amount, 'status' => $tp1->re['status2']); 
          $d=json_encode($context);
          $tp1->saveContext($ref, $d);
     }

     if (sizeof($result1) > 0) {
        //$tp1->saveOldContext($ref);
        $tp1->buildRspData($rsp);
     } else if (sizeof($result2) > 0) {
        //$tp2->saveOldContext($ref);
        $tp2->buildRspData($rsp);
     } 
       

     $rsp=json_encode($rsp);
     errlog("DEBUG: sending resp from mobileP", $rsp);
     crResp($rsp); 
     errlog("DEBUG: sent resp from mobileP", "\n");

} // mobileP

function topUp($p)
{

     $funcName=__FUNCTION__;
     errlog("DEBUG: processing $funcName", $p);

     $authArr = $p->auth;
     $username=$authArr->username;
     errlog("DEBUG: processing $funcName user: ", $username);
     $password=$authArr->password;
     $operator=$p->operator;
     $msisdn=$p->msisdn;
     $amount=$p->amount;
     $opAmount=0;
     if (isset($p->amountOperator)) 
        $opAmount=$p->amountOperator;
     if (!isset($p->simulate)) {
        $sim = false;;
     } else {
       $sim = $p->simulate;
     }
     $ref = $p->userReference;
     $prID = 0;
     if (!isset($p->productId)) {
        $prId = 1;
     } else {
       $prId = $p->productId; 
     }
     errlog("DEBUG: $funcName values: $username, $password", $operator); 
     $massagedInput = array('username' => $username, 'password' => $password, 'operator' => $operator, 'msisdn' => $msisdn, 'amount' => $amount, 'serial' => $ref);
     $xA = array('username' => 'a0', 'operator' => 'a1', 'amount' => 'a2');
     $rA = array('status' => 'a3', 'statusString' => 'a4', 'status2' => 'a5');
     // Since Sochitel does not send the msisdn in the request for 
     // transaction data and instead uses the userReference, that should
     // be used as the key instead.
    $key = array('app' => 'sockitel' ,'key' => $msisdn);
     errlog("DEBUG: $funcName first using key: ", $key); 
     $tp1= new TopupObj1($key);
     $tp1->TopupSetup($xA, $rA);
     errlog("DEBUG: finished setting up tp1 status:", $tp1); 
     $tp1->validateReqInput($massagedInput);
     errlog("DEBUG: finished validate of tp1 for topup", $tp1); 
    $key2 = array('app' => 'sockitel' ,'key' => $ref);
     errlog("DEBUG: $funcName for tp2 using key: ", $key2); 
     $tp2= new TopupObj1($key2);
     $tp2->TopupSetup($xA, $rA);
     errlog("DEBUG: finished settting up tp2 :", $tp2); 
     $tp2->validateReqInput($massagedInput);
     errlog("DEBUG: finished validating tp2 :", $tp2); 

     $status=0;
     $statusString='Successful'; 
     $result1 = $tp1->re; 
     $result2 = $tp2->re; 
     if (sizeof($result1) > 0) {
        errlog("DEBUG: found tp1 status set", $tp1); 
        $status= $result1['status'];
        $statusString= $result1['statusString'];
     } else if (sizeof($result2) > 0) {
        $status= $result2['status'];
        $statusString= $result2['statusString'];
     } else {
        $status= 0;
        $statusString= 'Successful';
     }
       
     $bal=1000;
     $balAfter=$bal-$amount;
     $op = array('id' => $operator, 'name' => 'Merryll', 'reference' => rand(100000000,99999999));
     if (empty($amount)) {
        $amount = number_format($opAmount/5510.01, 2, '.', '');
     } else {
       $opAmount = number_format($amount*5510.01, 2, '.', '');
     }
     $amt = array('operator' => $opAmount, 'user' => $amount);
     $cur = array('user' => 'USD', 'operator' => 'NGN');
     $commPercent = 10.0;
     $commCost = $amount * $commPercent * .01;
     $transAmt = $amount + ($amount * $commPercent * .01);
     $balArr = array('initial' => 0, 'transaction' => $transAmt,
		     'commission' => $commCost, 
                     'commissionPercentage' => $commPercent,
		     'final' => 61.50, 'currency' => 'USD');
     $altArr = array('29', '53');
        $statusInside = array('id' => $status,
			'name' => $statusString,
			'type' => $status,
			'typeName' => $statusString
			);
     $rspInside= array('id' => rand(1000000, 9999999),
		'operator' => $op,
		'msisdn' => $msisdn,
		'amount' => $amt,
		'currency' => $cur,
		'productId' => $prId,
		'simulation' => $sim,
		'id' => '45783',
		'userReference' => $ref,
		'balance' => $balArr,
		'alternativeProducts' => $altArr
		);
     $rsp=array('status' => $statusInside,
		'command' => 'mobileTopup',
		'timestamp' => round(microtime(true) * 1000),
		'reference' => rand(100000000,999999999),
     		'result' => $rspInside);

     if (isset($tp1->re['status2'])) {
         errlog("DEBUG: topUp saving context status", '/n');
         $context = array('serial' => $ref, 'username' => $username, 'operator' => $operator,
                          'msisdn' => $msisdn, 'amount' => $amount, 'status' => $tp1->re['status2']); 
          $d=json_encode($context);
          $tp1->saveContext($ref, $d);
     }

     if (sizeof($result1) > 0) {
        //$tp1->saveOldContext($ref);
        $tp1->buildRspData($rsp);
     } else if (sizeof($result2) > 0) {
        //$tp2->saveOldContext($ref);
        $tp2->buildRspData($rsp);
     } 
       

     $rsp=json_encode($rsp);
     errlog("DEBUG: sending resp from topUp", $rsp);
     crResp($rsp); 
     errlog("DEBUG: sent resp from topUp", "\n");

}//topUp 


function topUpReq($p)
{

     $funcName=__FUNCTION__;
     errlog("DEBUG: processing $funcName", "\n");
//     errlog("DEBUG: processing $funcName dumpping input: ", $p);


     $keyA=array('x_account_number' => $p['mobileno']);

    $dbO=getDBdata1('IMTU_DATA', 'TopupObj', $keyA);
    if ($dbO == FALSE) {
        error_log("INFO: TRANSFERTO topUpReq - not using test data from DB\n");
        $dbO = new TopupObj;
        $dbO->remap();
       }

    $rsp= $dbO->setAfterDb($p);

    processRequest($p, $dbO);

    $delay=$dbO->delay;
    if ($delay>0) {

           error_log("WARN: Delaying resp for $delay seconds");
           time_sleep_until(microtime(true) + $delay);
      }

    if ($dbO->abort == true) exit(0);

    crResp($rsp);
  
}//topUpReq 

// p is string input
// return Array
function sInput2a($p)
{

$pieces = explode("&", $p);

$eA=array();

foreach ($pieces as $val) {
   $xA = explode("=", $val);
   $eA[$xA[0]]= $xA[1];
}

return $eA;
}


function parseAction($p) {
     $funcName=__FUNCTION__;

   if (gettype($p) == 'array') $argA=$p[0];
   else {
      //$rspA = sInput2a($p);
      //errlog("DEBUG: parsing array: $rspA", "\n");
   }
   $action=$p->command;

     errlog("DEBUG: processing action: $action", "\n");

switch ($action) {
   case "getBalance": getBal();
            break;
   case "getCommands": commands();
            break;
   case "getCurrencies": currencies();
            break;
   case "getErrors": errors();
            break;
   case "getErrorTypes": errorTypes();
            break;
   case "getLocations": locations($rspA);
            break;
   case "getOperators": operators($p);
            break;
   case "getOperatorProducts": products($p);
            break;
   case "getProductTypes": productTypes();
            break;
   case "getStatistics": stats($rspA);
            break;
   case "getTransactionData": transData($p);
            break;
   case "key": key($rspA);
            break;
   case "mobileTopup": topUp($p);
            break;
   case "mobilePin": mobileP($p);
            break;
   case "parseMsisdn": parsePhone($rspA);
            break;
   default: 
        throw new Exception("Invalid Action <$action>", 901); 
}
} //parseAction

function DeleteMEcrResp($msg) {

$rp = &$msg;
$ht = new HttpResponse();
$ht->status(200);
$ht->setContentType('text/xml');
$ht->setData($rp);
errlog("INFO: crResp sending resp back: ", $rp);
$ht->send();

} //function crResp


$reqRev="";
try {

   if (!empty($HTTP_RAW_POST_DATA)) {
      $reqRev=$HTTP_RAW_POST_DATA;
      errlog("INFO: $fn: HTTP_RAW_POST_DATA", $HTTP_RAW_POST_DATA);
      }
   else {
      $reqRev=file_get_contents('php://input');
      errlog("INFO: $fn: php input has: ", $reqRev);
     }
   $kVpairs= json_decode($reqRev);
// skip authorization
   //  if ($kVpairs->version != 5) {
      errlog("DEBUG: version read as: ", $kVpairs->version); 
   //} else { 
     errlog("INFO: $fn: Command:", $kVpairs->command);
     parseAction($kVpairs);
   //}  

   if (!empty($_POST)) {
      errlog("DEBUG: POST: ", $_POST);
//   throw new Exception("ERROR: DONOT export POST data", 002);
     $reqRev=$_POST;
     } 
   }  

   /*if (isset($reqRev) ) parseAction($reqRev);
   else 
   throw new Exception("ERROR: Missing POST data", 002);

   }
   catch (SoapFault $ex) {
    throw new Exception($ex->faultstring, $ex->faultcode); 
} */

catch (Exception $e) {
   $msg=$e->getMessage();
   $code=$e->getCode();
   error_log("Caught exception: $msg, code=$code");
   $retMsg="0:$code:$msg";
              
   crResp($retMsg);
}

?>
