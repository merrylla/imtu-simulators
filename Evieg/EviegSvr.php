<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__));
$sessKey='IDT-EVIEG-SESSION';

include '../ezetop/ImtuTopUpClass.php';
include '../QAlib/wslib1.php';


function sendRsp($a, $code=200)
{
errlog("sendRsp: ", $a);
$jmsg=json_encode($a);

$ht = new HttpResponse();
$ht->status($code);
$ht->setContentType('application/json');
$ht->setData($jmsg);
errlog("INFO: crResp sending resp back: ", $jmsg);
$ht->send();
}

function topup($p)
{
    errlog("INFO topup - ", $p);

    $login=$p['login'];
    $transid=$p['key'];
//  $md5=$p->md5;
    $destnumber=$p['destnumber'];
    $product=$p['product'];

    $xA=array('product' => 'a0');

    $rA=array('error_code' => 'a1',
              'error_text' => 'a2',
    	      'error_code2' => 'a3',
              'error_text2' => 'a4',
	      'pin_opt3' => 'a5',
// R16.20 Added override for pin_code (externalaccountid)
	       'pin_code' => 'a6'
              );

// Default response fields which can be overwritten with DB values
	
    if (is_numeric($destnumber))
    {	//topup
	$rA1=array('error_code' => 0,
              'error_text' => 'Success',
              'balance' => 4688.88,
              'order_id' => rand(100,999),
              'product_requested' => $product,
              'product_sent' => $product,
	      'pin_based' => 'no'
	      );
    }
    else
    {	//ePin pin_code maps to purchase.pinNumber 
    	$rA1=array('error_code' => 0,
	      'error_text' => 'Success',
              'balance' => 3688.37,
              'order_id' => rand(100,999),
              'product_requested' => $product,
	      'product_sent' => $product,
	      'pin_based' => 'yes',
//	      'pin_code' => 384340613,
	      'pin_code' => rand(100000000,999999999),
	      'pin_ivr' => 384,
	      'pin_value' => $product,
	      'pin_opt2' => 'Dial *384*PIN#,press send',
	      'pin_opt3' => '322 or 02071320322'
              );
    }

    $key = array('app' => 'Evieg' ,'key' => $destnumber);

    $tpO= new TopupObj($key, $xA, $rA);

    $tpO->validateReqInput($p);

    $rA2=$rA1;
    
    if ( array_key_exists('error_code2', $tpO->re))
                $rA2['error_code']=$tpO->re['error_code2'];
    if ( array_key_exists('error_text2', $tpO->re)) 
                $rA2['error_text']=$tpO->re['error_text2'];

    sessStore($transaction, $rA2);

    $rsp=$tpO->buildRspData($rA1);

// Error code should be retrieved from DB ??
//    if ($rsp['error_code'] != 0) $rsp['error_text']='transaction failed';

    sendRsp($rsp); 

}

function balance($p)
{
    $key=$p['key'];
//  $md5=$p->md5;
    $rsp=array('error_code' => 0,
	       'error_text' => 'Success',
	       'auth_key' => $key,
               'balance' => 38434.56);

    sendRsp($rsp);

}//balance

function checkNumber($p)
{
    errlog("INFO checkNumber - ", $p);

    $key=$p['key'];
    $number=$p['number'];
//  $md5=$p->md5;
    $rsp=array('error_code' => 0,
               'error_text' => 'Success',
               'auth_key' => $key,
               'network' => 'MOBITOPUP');

    sendRsp($rsp);

}//checkNumber

function parseAction($get, $p)
{
    errlog("parseAction: usr info - ", $get);

    $cmd=$get['cmd'];

    switch ($cmd) {

        case 'topup':
              topup($get);
              break;
        case 'balance':
              balance($get);
              break;
        case 'checknumber':
              checkNumber($get);
              break;
        default:
            throw new Exception("Invalid URL $cmd", 400);
     }
    
}//parseAction


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
