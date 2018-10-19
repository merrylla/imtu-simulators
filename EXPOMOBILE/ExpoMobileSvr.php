<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));

include './ImtuTopUpClass.php';
include './wslib1.php';

global $params;

$params = array( 'agid', 'agpass', 'id', 'amount', 'phone', 'CardNum', 'CardExp', 'CardSecure', 'CardName', 'CardStreet', 'CardZip', 'note', 'cmd' );

global $a;
global $cmd;

if( array_key_exists('cmd',$_GET) ) {
	$cmd = $_GET['cmd'];
} 

foreach( $params as $field ) {
	if( array_key_exists($field,$_GET) ) {
		if( $field == 'cmd' ) continue;
		$a[$field] = $_GET[$field];
	}
}

function FImakeXML($a)
{
global $FIxml;

$FIxml = "";
foreach( $a as $tag => $value ) {
//        FILOG("ExpoMobileSvr.php::FImakeXML: tag is: " . $tag . " value is: " . $value . " \n");
        $FIxml = $FIxml . '<' . $tag . '>' . $value . '</' . $tag . '>' ;
//FILOG("ExpoMobileSvr.php::FImakeXML: value = " . $value . " \n");
        } // end foreach
//$FIxml = '<net>' . $FIxml . '</net>';
errlog("ExpoMobileSvr.php::FImakeXML: outbuff = " , $FIxml); 

return($FIxml);

} // end FImakeXML

#
##################################################
#
	
//====
function agInformation($p)
{
	global $params;

	errlog("ExpoMobile.agInformation p= ",$p);

	global $a;

	foreach( $params as $field ) {
		if( array_key_exists($field,$_GET) ) {
			$a[$field] = $_GET[$field];
		}
	}

	$xA = array( 'agid' => 'a0',
			'agpass' => 'a1');
		//	'review' => 'a2' );

	$rA = array( 'ret' => 'a2',
			'entity' => 'a3',
			'balance' => 'a4',
			'firstname' => 'a5',
			'lastname' => 'a6',
			'email' => 'a7' );
	
	errlog("ExpoMobile.agInformation xA= ",$xA);
	errlog("ExpoMobile.agInformation rA= ",$rA);

	$key = array('app' => 'EXPOMOBILE' ,'key' => 'BALANCE');
	$tpO= new TopupObj($key, $xA, $rA);

	$tpO->validateReqInput($p);

	$rsp=$tpO->buildRspData($rA);

	errlog("ExpoMobile.agInformation rsp before msg= ",$rsp);
	FImakeXML($rsp);
	return $rsp;
} // end agInformation

function status($p)
{
	global $params;

	errlog("ExpoMobile.status p= ",$p);

	global $a;

	foreach( $params as $field ) {
		if( array_key_exists($field,$_GET) ) {
			$a[$field] = $_GET[$field];
		}
	}

	$xA = array( 'agid' => 'a0',
			'agpass' => 'a1' );

	if( array_key_exists('review',$_GET) ) {
		$review = $_GET['review'];
	}
	$phone = substr( $review, -10 ,10);

	errlog("ExpoMobile.status phone= ",$phone);
	
	$rA = array( 'ret' => 'a2',
			'retmess' => 'a3',
			'confirmation' => 'a4'
			 );
	
	errlog("ExpoMobile.status xA= ",$xA);

	$key = array('app' => 'EXPOMOBILE' ,'key' => 'S'.$phone);
	$tpO= new TopupObj($key, $xA, $rA);

	$tpO->validateReqInput($p);

	$rsp=$tpO->buildRspData($rA);

	errlog("ExpoMobile.status rsp before msg= ",$rsp);
	FImakeXML($rsp);
	return $rsp;
} // end status

function expo1($p)
{
	global $params;

	errlog("ExpoMobile.expo1 p= ",$p);

	global $a;

	foreach( $params as $field ) {
		if( array_key_exists($field,$_GET) ) {
			$a[$field] = $_GET[$field];
		}
	}

	global $dbk;
	$dbk  = array( 'a0','a1','a2','a3','a4','a5','a6','a7','a8','a9','a10','a11', 'a12' );
	$i = 0;
	foreach( $params as $field ) {
		if( $field == 'cmd') continue;
		$xA[$field] = $dbk[$i];
		$i++;
	}
	if( array_key_exists('note',$xA) ) {
		$xA['note'] =  $_GET['note'];
	}

	
	FILOG("FI: Inside ExpoMobile.expo1 xA= ".print_r($xA,true)."\n");

	$rA = array( 'ret' => 'a12',
			'retmess' => 'a13',
			'confirmation' => 'a14' );
	
	$key = array('app' => 'EXPOMOBILE' ,'key' => $p['phone']);
	$tpO= new TopupObj($key, $xA, $rA);

	$tpO->validateReqInput($p);

	$rsp=$tpO->buildRspData($rA);
	errlog("ExpoMobile.expo1 rsp before msg= ",$rsp);
	FImakeXML($rsp);
	return $rsp;
} // end expo1

//====

errlog( 'ExpoMobileSvr.php: $params =:', $params);
errlog( 'ExpoMobileSvr.php: $a =:', $a);

$HTTP_RAW_POST_DATA = FImakeXML($a);
errlog( 'ExpoMobileSvr.php: raw post data =:', $HTTP_RAW_POST_DATA);

try {
global $FIxml;

errlog("ExpoMobileSvr.php::try: cmd = ", $cmd);

if( $cmd == 'agInformation' ) {
	errlog("ExpoMobileSvr.php: processing agInformation", $a);
	agInformation($a);
	echo '<net>' . $FIxml . '</net>';
}
else if( $cmd == 'expo1' ) {
	errlog("ExpoMobileSvr.php: processing expo", $a);
	expo1($a);
	echo '<net>' . $FIxml . '</net>';
}
else if( $cmd == 'status' ) {
	errlog("ExpoMobileSvr.php: processing status", $a);
	status($a);
	echo '<net>' . $FIxml . '</net>';
}
else {
	echo '<net><ret>-9999</ret></net>';
}

errlog("ExpoMobileSvr.php HTTP_RAW_POST_DATA=:", $HTTP_RAW_POST_DATA);
//
//XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

FILOG("##################################################################################\n");

}

catch (Exception $e) {

errlog("ExpoMobileSvr catch Exception", $e);
   echo '<net><ret>97</ret><retmess>FI: Inside catch Exception</retmess></net>';
   $errMsg="CatchException";
   $status= $e->getMessage();

   errlog('Rsp: <Error>', $status."</Error>");
   return array('Error' => $status);
}

#http://devcall02.ixtelecom.com/EXPOMOBILE/ExpoMobileSvr.php?cmd=expo&agid=fredy&agpass=passwd&id=SKU&amount=10&phone=5551234&CardNum=000000001234567890&CardExp=&CardSecure=123&CardName=IDT-Test&CardStreet="225%20Old%20NewBrunwick"&CardZip=08854&note=note
?>
