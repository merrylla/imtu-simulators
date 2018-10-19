<?php
include '../QAlib/ImsgR1310b.php';
include '../QAlib/wslib.php';
include '../QAlib/soaplib.php';

$fn=preg_replace('/\.php/', '', basename(__FILE__));
ini_set("error_log", "/logs/" . $fn . ".log");

function sig_handler($signo)
{

     switch ($signo) {
         case SIGTERM:
             // handle shutdown tasks
             error_log("INFO: $fn received SIGTERM \n");
             exit;
             break;
         case SIGHUP:
             // handle restart tasks
             error_log("INFO: $fn received SIGHUP\n");
             break;
         case SIGUSR1:
             error_log("INFO: $fn received SIGUSR1\n");
             break;
         default:
             // handle all other signals
             error_log("INFO: $fn received unknown signal $signo\n");
     }

}

// setup signal handlers
//pcntl_signal(SIGTERM, "sig_handler");
//pcntl_signal(SIGHUP,  "sig_handler");
//pcntl_signal(SIGUSR1, "sig_handler");

function sendResp($msg, $code)
{
$rspA=array('Response' => array('Message' => $msg, 'Code' => $code));
$rp = buildxml($rspA);

return sendXmlResp($rp);
}

function sendXmlResp($xmlMsg)
{
$ht = new HttpResponse();
$ht->status(200);
$ht->setContentType('text/xml');
$ht->setData($xmlMsg);
$ht->setBufferSize(4096);
$ht->setThrottleDelay(.2);
$htHeader = apache_response_headers();
//dump_errorlog("INFO: sendXmlRespHeader: ", $htHeader);
dump_errorlog("INFO: sendXmlRespBody: ", $xmlMsg);
if (!$ht->send() ) dump_errorlog("WARNING: $fn ht->send failed", $htHeader);
}


try {

//dump_errorlog("FREDY-DEBUG: ",$HTTP_RAW_POST_DATA);
$sndMsg = '';
$htHeader = apache_request_headers();

//dump_errorlog("DEBUG: $fn received: ", $GLOBALS);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $sndMsg = file_get_contents("php://input");
  dump_errorlog("DEBUG: $fn received: $sndMsg", $htHeader);
} else {
   if (empty($HTTP_RAW_POST_DATA)) throw new Exception("ERROR: found empty POST message", 9901); 

   dump_errorlog("DEBUG: $fn received: $HTTP_RAW_POST_DATA", $htHeader);
   $sndMsg=$HTTP_RAW_POST_DATA;
}
//$host = "tcp://testdb02.ixtelecom.com";
$port = 9932; // 9162 9163 9164
$host = "tcp://10.227.2.113:$port"; // debitSkSvrWAP1  testlbdb02 9161 1 tcp
$timeout = 130;

if (isset($htHeader['Host'])) $host="tcp://".$htHeader['Host'];
dump_errorlog("DEBUG: Host = ", $host);

$fp = stream_socket_client("$host", $errno, $errstr, 30);
if (!$fp) throw new Exception("ERROR: failed to connect to '$errstr' ($errno) $host", 9901);

stream_set_timeout($fp, $timeout);

    $snd=new IMsg;

$app="NULL";

if (isset($htHeader['APP'])) $app=$htHeader['APP'];
if (isset($htHeader['ROUTETYPE'])) 
      $snd->header->setdbRouteInfoType=$htHeader['ROUTETYPE'];

switch($app) {

case 'SKSVR':
    $snd->header->setup4sksvr(); 
error_log("INFO: app=sksvr \n");
    break;
case 'TOKENSVR':
    $snd->header->setup4tokensvr();
error_log("INFO: app=tokensvr \n");
    break;
case 'B2B':
    $snd->header->setup4b2b();
error_log("INFO: app=B2B\n");
    break;
case 'IMTU':
error_log("imsgRtSvr.php:INFO: app=imtu \n");
    break;
case 'CCRSVR':
    $snd->header->setup4ccr();
error_log("imsgRtSvr.php:INFO: app=ccr \n");
    break;
case 'QRYSVR':
    $snd->header->setup4qrysvr();
error_log("imsgRtSvr.php:INFO: app=qry \n");
    break;
default: 
    error_log("WARNING: imsgRtSvr handling undefined app <$app>\n");
    $snd->header->setup4sksvr();
//      throw new Exception("ERROR: incorrect or missing APP <$app> field in the XMLS..", 9903);
}

    fwrite($fp, $snd->pack());
//    echo "Query: ",$snd->mbody,"\n";


   if (($msgHdr=fread($fp, $iMsgHdrSz))==FALSE) 
          throw new Exception ("ERROR: received garbled message header from $host", 9902);

   $rA = unpack("N10a*a*",$msgHdr);

   $MsgSz=array_shift($rA); 
//error_log("Received $MsgSz msgHdr: ".bin2hex($msgHdr));;
//var_dump($rA);

    $freadlen = $MsgSz-$iMsgHdrSz;
    $rsp='';

dump_errorlog("DEBUG: reading reponse from ", $host);

    while (strlen($rsp) < $freadlen) {
       $rsp .=fread($fp, $freadlen);
    }
    sendXmlResp($rsp);
//    sendResp("OK", 0);

    fclose($fp);
}//try

catch (Exception $e) {
   $msg=$e->getMessage();
   $code=$e->getCode();
   error_log("Caught exception: $msg, code=$code");
   sendResp($msg, $code);
}
?>
