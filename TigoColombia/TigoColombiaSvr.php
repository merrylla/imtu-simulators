<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));

include '../ezetop/ImtuTopUpClass.php';
include '../QAlib/wslib1.php';

$wsdl="http://devcall02.ixtelecom.com/TigoColombia/TigoColombia.wsdl";

function moneyDTO($m, $curcy='US')
{
return array( 'amount' => $m,
              'currencyId' => 123,
              'currencyName' => $curcy);
}

function throwErr($c, $m)
{
    $faultcode='S:Server';
    $faultstring="Recharge failed. Credit rollback successfully. $m RC $c";
    errlog("throwErr: faultcode: $faultcode -- faultstring: ", $faultstring);
    throw new SoapFault($faultcode, $faultstring);
}


class SoapHandler {

function eRecharge($q)
{
    errlog("INFO: eRecharge\n", $q);

    $p=&$q->arg0;

 
    $amount = $p->amount->amount;
    $currency = $p->amount->currencyName;
    //$information = $p->information;  option NOT USED
    $target = $p->target;
    $terminalId = $p->terminalId;
    $transactionId = $p->transactionId;

    $xA=array('amount' => 'a0', 'currencyName' => 'a1');
    $rA=array('errCode' => 'a2', 'errMsg' => 'a3');

    $idDTO=array();
    $debitResponseDTO=array();

    $rechargeResponse= array('authorizationNumber' => 'TigoCO.'.$transactionId,
                             'bonusBalance' => moneyDTO(0), 
                             'bonusCredited' => moneyDTO(0),
                             'mobileNumber' => $target,
                             'realBalance' => moneyDTO(0),
                             'realCredited' => moneyDTO($amount, $currency), 
                             'transactionId' => $transactionId
                            );

    $rA1=array('bonusStatus' => $idDTO,
               'debitResponse' => $debitResponseDTO,
               'message' => 'eRecharge Response Message',
               'rechargeResponse' => $rechargeResponse);

    $key = array('app' => 'tigoCO' ,'key' => $target);

    $tpO= new TopupObj($key, $xA, $rA);

    $tpO->validateReqInput($p->amount);

    $tpO->buildRspData1($rA1);

    if (isset($tpO->re['errCode'])) {
        $c=$tpO->re['errCode'];
        $m=$tpO->re['errMsg'];
        throwErr($c, $m); 
    }

   errlog('Sending FlexiRecharge Response:', $rA1);

    return array('return' => $rA1);

}//eRecharge

function eBalance($p)
{
    errlog("INFO: eBalance\n", $p);

    $arg0 = $p->arg0;

    $rsp=array('bonusBalance' => moneyDTO(0),
               'realBalance' => moneyDTO(100000),
               'transactionId' => 'string-12345'
              ); 

    return array('return' => $rsp);

}//eBalance

} //SoapHandler


//$msg= "DEBUG: HTTP_RAW_POST_DATA:".mix2str($HTTP_RAW_POST_DATA)."\n";
errlog('Main', $HTTP_RAW_POST_DATA);

$server = new SoapServer($wsdl, array('soap_version' => SOAP_1_2));
$server->setClass("SoapHandler");
//$func=$server->getFunctions();
//errlog('Main - List of soap methods:', $func);

try {

$server->handle($HTTP_RAW_POST_DATA);

}
catch (Exception $e) {

   $rc= $e->getCode();
   $rm= $e->getMessage();

   $detail='exception details in xml format';

   throw new SoapFault($rc, $rm);
}

?>
