<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));

include './ImtuTopUpClass.php';
#include '../QAlib/wslib1.php';
include './wslib1.php';

$wsdl="http://devcall02.ixtelecom.com/CLAROPR/ClaroPR.wsdl";

$loginName='testarg0';
$password='testarg1';

// courtesy of 0.015 solutions...

// Messages in Spanish
$achar = chr(160);
$echar = chr(130);
$ichar = chr(177);
$ochar = chr(162);
$uchar = chr(163);
$nchar = chr(164);
$Nchar = chr(165);
$Echar = chr(144);

$achar = 'á';
$echar = 'é';
$ochar = 'ó';
$uchar = 'ú';
$nchar = 'ñ';
$ichar = 'í';

$msg0 = '0001';
$msg1 = 'Error en in your ClienID or Password.';
$msg2 = 'Favor de verificar su Cliente ID.';
$msg3 = 'Error en su Cliente ID '.$ochar.' Contrase'.$nchar.'a, Por favor trate de nuevo.';
$msg4 = 'Favor de verificar el n'.$uchar.'mero de Tel'.$echar.'fono.';
$msg5 = 'Para el n'.$uchar.'mero de Tel'.$echar.'fono el total de d'.$ichar.'gitos son 10.';

$msg6 = 'N'.$uchar.'mero de Tel'.$echar.'fono invalido.';
$msg7 = 'La Cantidad de Pago debe ser igual '.$ochar.' menor de $80.00.';
$msg8 = 'Favor de verificar la Cantidad de Pago.';
$msg9 = 'Favor de verificar el N'.$uchar.'m. de Referencia o N'.$uchar.'m. Recibo.';
$msg10 = 'Debes de escoger un M'.$echar.'todo de Pago.';
$msg11 = 'El M'.$echar.'todo de Pago es inv'.$achar.'lido.';
$msg12 = 'Solicitud invalida para esta cuenta.';
$msg13 = 'Esta transacci'.$ochar.'n ya fue procesada, verifique en su informe.';
$msg14 = 'Account not found';


$msgArray = array( 	'0001' => $msg0,
			'1' => $msg1,
			'2' => $msg2,
			'3' => $msg3,
			'4' => $msg4,
			'5' => $msg5,
			'6' => $msg6,
			'7' => $msg7,
			'8' => $msg8,
			'9' => $msg9,
			'10' => $msg10,
			'11' => $msg11,
			'12' => $msg12,
			'13' => $msg13,
			'14' => $msg14
			);

function FImakeXML($a)
{
global $FIxml;
global $msgArray;

$FIxml = "";
foreach( $a as $tag => $value ) {
        FILOG("ClaroPRSvr.php::FImakeXML: tag is: " . $tag . " value is: " . $value . " \n");
        $FIxml = $FIxml . '<' . $tag . '>' . $value . '</' . $tag . '>' ;
FILOG("ClaroPRSvr.php::FImakeXML: value = " . $value . " \n");
        } // end foreach

FILOG("ClaroPRSvr.php::FImakeXML: outbuff = " . $FIxml . " \n");

return($FIxml);

} // end FImakeXML

#
##################################################
#
class SoapHandler {
	
//====
	function PostPayment($p)
	{
	global $msgArray;
FILOG("FI: Inside ClaroPR.PostPayment p= ".print_r($p,true)."\n");

	//check for; msisdn, etc. 
        $xA=array('clienteID' 	=> 'a0',               
                'accountNumber' => 'a1',                 // MSISDN
                'paymentAmount' => 'a2',           
                'paymentSubMethod' => 'a3',         
             //   'numRef' 	=> 'a4',             
                'cardDigit' 	=> 'a5',              
                'tipoVenta' 	=> 'a6',               
                'param1' 	=> 'a7',                
                'param2' 	=> 'a8',                 
                'param3' 	=> 'a9'
                );
	//return: 

        $rA=array('PostPaymentResult' => 'a10');
        //$rA=array('PostPaymentResult' => $msg3);

	$key = array('app' => 'CLAROPR' ,'key' => $p->accountNumber);
	$tpO= new TopupObj($key, $xA, $rA);

	$tpO->validateReqInput($p);

	$rsp=$tpO->buildRspData($rA);

	//$rsp1=array('PostPaymentResult' => $msgArray['07'] );
FILOG('FI: Inside ClaroPR.PostPayment $rsp[PostPaymentResult] = '.print_r($rsp['PostPaymentResult'],true)."\n");
FILOG('FI: Inside ClaroPR.PostPayment $msgArray[$rsp[PostPaymentResult]] = '.print_r($msgArray[$rsp['PostPaymentResult']],true)."\n");
FILOG("FI: Inside ClaroPR.PostPayment rsp before msg= ".print_r($rsp,true)."\n");
	$rsp=array('PostPaymentResult' => $msgArray[$rsp['PostPaymentResult']] );
FILOG("FI: Inside ClaroPR.PostPayment rsp after msg= ".print_r($rsp,true)."\n");
	FImakeXML($rsp);
	return $rsp;
	} // end PostPayment
//====
} //SoapHandler

if (empty($HTTP_RAW_POST_DATA)) {

    $doc = new DOMDocument();
    $doc->load('./rechargeAccount.xml');
    $HTTP_RAW_POST_DATA= $doc->saveXML();
}

errlog('Main', $HTTP_RAW_POST_DATA);

//
// notice $server is started differently here...
//
$server = new SoapServer($wsdl, array('soap_version' => SOAP_1_2));
#$server = new SoapServer(null, array('uri' =>$wsdl));
$server->setClass("SoapHandler");
$func=$server->getFunctions();
FILOG(print_r($func,true));

//
// the viva DO wsdl has '.' in fuction name which php does not like.
// they are changed to '_' to make php happy, and then reverted by out_callback
// to '.' to make IMTU happy...

function out_callback($buffer)
{
global $FIresp;
global $FIxml;
global $msgArray;

return($buffer);

$start=strpos($buffer,"<env:Body>");
$end=strpos($buffer,"</env:Body>");

//PostPayment
if( strpos($buffer,'<ns1:PostPaymentResponse>') !== false ) {
	$lRR = '<ns2:PostPaymentResponse xmlns:ns2="http://server.dtsws/">'.$FIxml.'</ns2:PostPaymentResponse>';
	$lRR = utf8_encode($lRR);
 	$outbuff= substr($buffer,0,$start) . '<env:Body>' . $lRR . substr($buffer,$end);
        }
else if( strpos($buffer,'<ns1:PostPaymentResponse/>') !== false ) {
	$lRR = '<ns2:PostPaymentResponse xmlns:ns2="http://server.dtsws/">'.$FIxml.'</ns2:PostPaymentResponse>';
	$lRR = utf8_encode($lRR);
 	$outbuff= substr($buffer,0,$start) . '<env:Body>' . $lRR . substr($buffer,$end);
        }
else {
	$outbuff = $buffer;
	}

$FIresp = $outbuff;
return($outbuff);

} // end out_callback

try {

FILOG("FI: Inside ClaroPRSvr.php\n");
foreach($func as $method ) {
	FILOG($method . "\n");
	} // end foreach
FILOG("FI: Inside ClaroPRSvr.php HTTP_RAW_POST_DATA=:\n" . $HTTP_RAW_POST_DATA );

//
// edit HTTP_RAW_POST_DATA function name to make php happy
//
$FI_HTTP_RAW_POST_DATA=preg_replace("/".'viva_topup.Recharge'."/",'viva_topup_Recharge',$HTTP_RAW_POST_DATA);

FILOG("FI: Inside ClaroPRSvr.php FI_HTTP_RAW_POST_DATA=:\n" . $FI_HTTP_RAW_POST_DATA );
//
//XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

ob_start("out_callback");
$server->handle($FI_HTTP_RAW_POST_DATA);
ob_end_flush();

FILOG("FI: ClaroPRSvr.php: FIresp =:" . print_r($FIresp, true) . "\n");
//FILOG("FI: ClaroPRSvr.php: rawRsp =:" . print_r($rawRsp, true) . "\n");

//XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
FILOG("FI: Inside ClaroPRSvr.php after handle\n");
FILOG($HTTP_RAW_POST_DATA . "\n");
//FILOG('List of soap methods: ' . "\n");
FILOG("##################################################################################\n");

#$server->topupRequest($HTTP_RAW_POST_DATA);

}
catch (Exception $e) {

FILOG("FI: Inside catch Exception\n");
   $errMsg="CatchException";
   //$status= $e->getCode();
   //the return code eg 01 cannot be stored in getCode. hence use getMessage.
   $status= $e->getMessage();

   errlog('Rsp: <Error>', $status."</Error>");
   return array('Error' => $status);
}

?>
