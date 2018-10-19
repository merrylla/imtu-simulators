<?php
include '../QAlib/ImsgR1310.php';
include '../QAlib/wslib.php';
include '../QAlib/soaplib.php';

$fn=basename(__FILE__);

function sendResp($msg, $code)
{
$rspA=array('IMTUWebResponse' => array('Message' => $msg, 'Code' => $code));
$rp = buildxml($rspA);

return sendXmlResp($rp);
}

function sendXmlResp($xmlMsg)
{
$ht = new HttpResponse();
$ht->status(200);
$ht->setContentType('text/xml');
$ht->setData($xmlMsg);
dump_errorlog("INFO: sendXmlResp: ", $xmlMsg);
$ht->send();
}

//dump_errorlog("DEBUG: $fn received: ", $GLOBALS);
if (empty($HTTP_RAW_POST_DATA)) { 
    sendResp("ERROR: found empty POST message", 9901);
    exit();
}

dump_errorlog("DEBUG: $fn received: ", $HTTP_RAW_POST_DATA);
$sndMsg=$HTTP_RAW_POST_DATA;
//sendResp("OK", 0);

$fp = stream_socket_client("tcp://qaweb01.ixtelecom.com:9932", $errno, $errstr, 30);

stream_set_timeout($fp, 130);

if (!$fp) {
    echo "$errstr ($errno)<br />\n";
} else {
    $snd=new IMsg;

    fwrite($fp, $snd->pack());
//    echo "Query: ",$snd->mbody,"\n";


   if (($msgHdr=fread($fp, $iMsgHdrSz))==FALSE) die("ERROR: failed to get response message header");

   $rA = unpack("N10a*a*",$msgHdr);

   $MsgSz=array_shift($rA); 
//echo"Received msgHdr: ",bin2hex($msgHdr),"\n";
//var_dump($rA);

    $rsp=fread($fp, $MsgSz-$iMsgHdrSz);
    sendXmlResp($rsp);
//    sendResp("OK", 0);

    fclose($fp);
}
?>
