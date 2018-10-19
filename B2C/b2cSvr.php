<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));

include '../ezetop/ImtuTopUpClass.php';
include '../QAlib/wslib1.php';

$wsdl="http://devcall02.ixtelecom.com/B2C/Bridge2Call.wsdl";

function cksum($p) 
{
     $Publickey='b2cQAtest';

     $str='';
     foreach ($p as $key => $val) 
        if ($key != 'Checksum') $str .=$val.'|';
     
     $str .= $Publickey;


     $checksum = md5(sha1($str));
     

     if ($checksum == $p->Checksum) return true;
     else 
        return false;
}

class SoapHandler {


function ResellerBalance($p)
{
    //errlog("INFO: ResellerBalance\n", $p);

    $LoginId = $p->LoginId;
    $RequestId = $p->RequestId;

    $rA1=array('ResponseCode' => '000',
               'ResponseDescription' => 'balance ok',
               'TillDateBalance' => '289111',
               'CurrentBalance' => '2890.12'
               );

    if (cksum($p) == false) {

       $rA1['ResponseCode']= '001';
       $rA1['ResponseDescription'] = 'Login Invalid - balance cksum error';
       $rA1['ConfirmationCode'] = '0';

    }

    errlog('Sending ResellerBalance Response:', $rA1);
    return $rA1;

}//ResellerBalance

function FlexiRecharge($p)
{
    //errlog("INFO: FlexiRecharge\n", $p);

    $LoginId = $p->LoginId;
    $RequestId = $p->RequestId;
    $BatchId = $p->BatchId;
    $SystemServiceID = $p->SystemServiceID;
    $MobileNumber = $p->MobileNumber;
    $Amount = $p->Amount;
    $Checksum = $p->Checksum;

    $xA=array('Amount' => 'a0');

    $rA=array('ResponseCode' => 'a1',
              'ResponseDescription' => 'a2');

     $rc='000';
     $rm='Request Successfully completed';
     $cc= (string) (rand(289000, 289999));

    $rA1=array('ResponseCode' => $rc,
               'ResponseDescription' => $rm,
               'ConfirmationCode' => $cc,
               'AuditNo' => 'AuditNo.'.$RequestId,
               'TopUpAmount' => $Amount
               );

    if (cksum($p) == false) {

       $rA1['ResponseCode']= '001';
       $rA1['ResponseDescription'] = 'Login Invalid - cksum error';
       $rA1['ConfirmationCode'] = '0';

       return $rA1;
    }

    $key = array('app' => 'b2c' ,'key' => $MobileNumber);
   
    $tpO= new TopupObj($key, $xA, $rA);

    $tpO->validateReqInput($p);

    $tpO->buildRspData1($rA1);

    if ($rA1['ResponseCode'] != '000' ) 
            $rA1['ConfirmationCode'] = '0';

   errlog('Sending FlexiRecharge Response:', $rA1);
   return $rA1;

}//FlexiRecharge

} //SoapHandler


$msg= "DEBUG: HTTP_RAW_POST_DATA:".mix2str($HTTP_RAW_POST_DATA)."\n";
errlog('Main', $msg);

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

   $rA1=array('ResponseCode' => $rc,
               'ResponseDescription' => $rm,
               'ConfirmationCode' => '0',
               'AuditNo' => 'AuditNo.'.'0000000',
               'TopUpAmount' => '0'
               );

   errlog('Exception Rsp: ', $rA1);
   return $rA1;
}

?>
