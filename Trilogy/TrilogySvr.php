<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));

include '/debit/apps/htdocs/ezetop/ImtuTopUpClass.php';
include '/debit/apps/htdocs/QAlib/wslib2.php';
include '/debit/apps/htdocs/Trilogy/rmTool.php';

$wsdl="http://devcall02.ixtelecom.com/Trilogy/Trilogy.wsdl";
//$wsrm="http://devcall02.ixtelecom.com/Trilogy/wsrm.wsdl";

function shutdown()
{
     errlog("INFO: shutdown - TrilogySVr is ", "exiting.");
}

//httpResponse 
function crResp1($msg) {

$rp = &$msg;
$ht = new HttpResponse();
//$ht->status(200);
//$ht->setContentType('text/xml');
$ht->setContentType('application/xop+xml; charset=UTF-8; type="application/soap+xml"');
$ht->setData($rp);
errlog("INFO: crResp1 sending resp back: ", $rp);
$ht->send();

} //function crResp1


function handleTopUp($p)
{
     global $wsdl;

     //errlog("INFO: handleTopUp ", $p);

     $server = new SoapServer($wsdl, array('soap_version' => SOAP_1_2));
     $server->setClass("SoapHandler");

     try {
       ob_start();
       $server->handle($p);

       $soaRsp=ob_get_contents();
       //errlog("INFO: SoapRsp: ", $soaRsp);
       ob_end_clean();
       return $soaRsp;
     }
     catch (Exception $e) {

       $rc= $e->getCode();
       $rm= $e->getMessage();

       errlog("Caught Exception: $rc ", $rm);
     }


}//handleTopUp

function parseAction($rm) 
{

     $cmd = $rm->cmd;

     switch ($cmd) 
      {
       case 'CreateSequence':
          $r=$rm->crSeqAck();
          crResp1($r);
          //exit(0);
          break;
       case 'SequenceAcknowledgement':
          errlog("INFO: parseAction Rec SequenceAcknowledgement cmd", 'Do nothing and loop');
          exit(0);
    
       case 'RunTopUp':
          //$topupRsq=preg_replace('/^.*(<SOAP-{0,1}ENV:Body>.*SOAP-ENV:Body>).*$/is', '$1', $rm->msg);
          //$fixup='<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope">'.$rm->bdy.'</SOAP-ENV:Envelope>';
          $fixup='<soapenv:Envelope xmlns:soapenv="http://www.w3.org/2003/05/soap-envelope">'.$rm->bdy.'</soapenv:Envelope>';

          $tp=handleTopUp($fixup);
          $ns=preg_replace('/^.*soap-envelope" (.*>)<env:Body>.*$/s', '$1', $tp);
          $fixup=preg_replace('/^.*:Body>(.*)<\/env:Body>.*$/s', '$1', $tp);
          $r=$rm->SeqAck($ns, $fixup);
          //$conn=connection_status();
          crResp1($r);
          //sleep(5);
          //$seqTerm=http_get_request_body();
          //$conn=connection_status();
          //$seqTerm=file_get_contents("php://input");
          break;
  
       default:
          errlog("INFO: parseAction cmd: $cmd not found", 'Do nothing and loop');
          //exit(1);
       }
} //parseAction


class SoapHandler {

function RunTopUp($q)
{
    //errlog("INFO: RunTopUp\n", $q);

    $p=&$q->request->AccountTopUp;

    $accNumber = $p->Account->Number;
    $accType = $p->Account->Type;
    $Amount = $p->Transaction->Amount;
    $Comments = $p->Transaction->Comments;  //Voila!
    $Currency = $p->Transaction->Currency;
    $FirstName = $p->Transaction->FirstName; //IDT Corporation
    $TopupType = $p->Transaction->TopupType; //fixed
    $ReferenceId = $p->Transaction->ReferenceId;
    $Uid = $p->User->Id;
    $Password = $p->User->Password;

    $xA=array('Amount' => 'a0', 'Comments' => 'a1', 'TopupType' => 'a2');
    $rAx=array('Error' => 'a3', 'Status' => 'a4', 'Version' => 'a5');

    $rAccount = array('Currency' => $Currency, 'NewBalance' => '123',
                      'Number' => $accNumber, 'OldBalance' => '100',
                      'Type' => $accType );
    $rTransaction = array('BonusAmount' => '0', 'BonusPercent' => '0',
                          'ConfirmationId' => (string)rand(100000, 200000),
                          'PhoneActivatePin' => '23456',
                          'ReferenceId' => $ReferenceId,
                          'TopupPin' => '000000' );
    $rUser = array('Id' => $Uid, 'Password' => $Password);

    $topupRsp = array('Account' => $rAccount, 'Transaction' => $rTransaction, 'User' => $rUser);

    $rA= array ('RunTopUpResult' => array ('Error' => 'null',
                               'Status' => 'SUCCESS',
                               'Version' => '5678',
                               'AccountTopUp' => $topupRsp));


    $key = array('app' => 'Trilogy' ,'key' => $accNumber);

    $tpO= new TopupObj($key, $xA, $rAx);

    $tpO->validateReqInput($p->Transaction);

    $tpO->buildRspData1($rA);

    //errlog("INFO: RunTopUpResult\n", $rA);
    return $rA;

    //throw new SoapFault(9999, "RunTopUp is under contruction");

}//RunTopUp


} //SoapHandler

//register_shutdown_function('shutdown');

if (!isset($HTTP_RAW_POST_DATA) ) {
    //$HTTP_RAW_POST_DATA =file_get_contents('./javaCreateSequence');
    $HTTP_RAW_POST_DATA =file_get_contents('./javaRunTopup');
}
errlog('TrilogySvr', $HTTP_RAW_POST_DATA);

try {

   $hbody=preg_replace('/^.*(<SOAP-{0,1}ENV:Envelope xmlns:SOAP.*SOAP-{0,1}ENV:Envelope>).*/is', '$1', $HTTP_RAW_POST_DATA);
   $rm=new RMobj($hbody);
   parseAction($rm);
}//try
catch (Exception $e) {

   $rc= $e->getCode();
   $rm= $e->getMessage();
   errlog("Caught Exception <$rc>: ", $rm);
}
 
errlog("Main - exiting", "\n");
?>
