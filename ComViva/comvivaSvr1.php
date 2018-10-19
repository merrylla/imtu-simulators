<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__)); // for logging file name

include '../Remittance/rmttObjBase.php';
include '../Remittance/dbClass.php';
include '../QAlib/wslib.php';
include '../QAlib/soaplib.php';
include '../QAlib/errlog.php';
include 'comviva_data.php';

date_default_timezone_set('America/New_York');

function comvivadate() { return date("d-m-Y H:i:s");}

// x is xml DomNode
// return Array
function xmln2a(&$x)
{
    $a=array();

    $nName = $x->nodeName;
    $nValue = $x->nodeValue;
    $nType = $x->nodeType;
    error_log("Comviva DEBUG: nodeName: $nName, nValue: $nValue, nType: $nType");
    errlog("Comviva DEBUG: nodeName: $nName, nValue: $nValue, nType: $nType");

    $cn=$x->firstChild();
    while($cn) {
       $a[$cn->nodeName] = $cn->nodeValue;        
       $cn=$cn->next_sibling();
       error_log("ComViva Debug: nodeName: $cn->nodeName, NodeValue: $cn->nodeValue");
       errlog("ComViva Debug: nodeName: $cn->nodeName, NodeValue: $cn->nodeValue");
    }
/*
    switch ($x->nodeType) {
       case XML_ELEMENT_NODE:
            $a[$nName] = $x->child_nodes(); 
            break;
       default:
    }
*/
    if (empty($a)) 
        throw new Exception("ERROR: xmln2a found no xml data", 500);  

    return $a;
}

// x is xml string input
// return DomNode
function xmls2a($p)
{

	$xmlA=array();
    //error_log("Comviva DEBUG: xml String: $p");
    errlog("Comviva DEBUG: xml String:", $p);
	
if (!$dom = simplexml_load_string($p)) 
 throw new Exception("Unable to parse XML input", 500);

 foreach($dom as $member=>$data) {
// 	$dtype=gettype($data);
// 	dump_errorlog("DOM obj: $member -", "datatype- $dtype - $data");
      $xmlA[$member]=$data;
 }
    //error_log("Comviva DEBUG: xml Array: $xmlA");
    errlog("Comviva DEBUG: xml Array: ", $xmlA);
 
return $xmlA;
}


//Construct the Array data structure
function comvivaRespA($cmd, $status, $xRef, $tid, $msg) 
{
    $rspA =  array ( "TYPE" => $cmd,
                "TXNSTATUS" => $status, 
                     "DATE" => comvivadate(), 
                "EXTREFNUM" => $xRef,
                    "TXNID" => $tid, 
                  "MESSAGE" => $msg);

    //error_log("Comviva DEBUG: Command: $rspA");
    errlog("Comviva DEBUG: Command: ", $rspA);
   return array("COMMAND" => $rspA);
}

//echo "dumping GLOBALS","<br/>"; 
//var_dump($GLOBALS);


function handleStatus($xrq) {

	//error_log("ComViva handleStatus - TBD");
	errlog("ComViva handleStatus - ", "TBD");

}// handleStatus

function handleBal($xrq) {
	//error_log("ComViva ThandleBal - TBD");
	errlog("ComViva ThandleBal - ", "TBD");

}//handleBal

function handleTrf($p)
{

     $funcName=__FUNCTION__;
     errlog("ComViva DEBUG: processing $funcName ", $funcName);

     $xref=$p['EXTREFNUM'];
     $keyA=array('x_account_number' => $p['MSISDN2']);

    $dbA=getDBdata1('IMTU_DATA', 'ComVTopUpData', $keyA);
    // added following 3 lines as per request from Dom Riggi on Feb 2, 2017
    if ($p['MSISDN2'] == '84464011') {
       $dbA->delay = 90;
    } else if ($p['MSISDN2'] == '84464022') {
       $dbA->delay = 45;
    }
//    error_log("Comviva DEBUG: dbA: $dbA");
// replace ^ with v
    errlog("Comviva DEBUG: dbA: ", $dbA);
    if ($dbA == FALSE) {
//error_log("INFO: Comviva handleTrf - no data found from DB\n");
errlog("INFO: Comviva handleTrf - no data found from DB", "\n");
        $dbA = array('MSISDN2' => '~DONTCARE');
       }

$retCode='200';
$retMsg='ERROR: user initiated';
$delay=0;
if (isset($dbA->r_TXNSTATUS)) $retCode=$dbA->r_TXNSTATUS;
if (isset($dbA->delay)) $delay=$dbA->delay;
if (preg_match('/T_NORSP/i',$retCode)) {
//error_log("WARN: $funcName received instruction $retCode - exiting in $delay seconds");
errlog("WARN: $funcName received instruction $retCode - exiting in", " $delay seconds");
time_sleep_until(time() + $delay);
exit(0);
}

/*
     $dbA= array('x_TYPE' => '~DONTCARE',
                 'x_DATE' => '~DONTCARE',
                 'x_EXTNWCODE' => '~DONTCARE',
             'x_MSISDN' => '~DONTCARE',
     'x_PIN' => '~DONTCARE',
     'x_LOGINID' => '~DONTCARE',
     'x_PASSWORD' => '~DONTCARE',
     'x_EXTCODE' => '~DONTCARE',
     'x_EXTREFNUM' => '~DONTCARE',
     'x_MSISDN2' => '~DONTCARE',
     'x_AMOUNT' => '~DONTCARE',
     'x_LANGUAGE1' => '~DONTCARE',
     'x_LANGUAGE2' => '~DONTCARE',
     'x_SELECTOR' => '~DONTCARE',
     'r_TYPE' => 'EXRCTRFRESP',
     'r_TXNSTATUS' => 200,
     'r_DATE' => comvivadate(),
     'r_EXTREFNUM' => $xref,
     'r_TXNID' => rand(),
     'r_MESSAGE' => "Your request is accepted for further processing"
       );

*/

//    error_log("Comviva DEBUG: about to process request dbA: $dbA");
// replace ^ with v
    errlog("Comviva DEBUG: dbA: ", $dbA);
    //error_log("Comviva DEBUG: about to process request p: $p");
    errlog("Comviva DEBUG: about to process request p:", $p);
      $rsp= processRequest($p, $dbA);
//    error_log("Comviva DEBUG: processed request dbA: $dbA");
    //error_log("Comviva DEBUG: processed request p:", $p);
    errlog("Comviva DEBUG: processed request p:", $p);

      if (!isset($rsp['TYPE'])) $rsp['TYPE']='EXRCTRFRESP';
      if (!isset($rsp['TXNSTATUS'])) $rsp['TXNSTATUS']=$retCode;
      if (!isset($rsp['DATE'])) $rsp['DATE']=comvivadate();
      if (!isset($rsp['EXTREFNUM'])) $rsp['EXTREFNUM']=$xref;
      if (!isset($rsp['TXNID'])) $rsp['TXNID']=rand();

if ($rsp['TXNSTATUS'] != '200') 
    unset($rsp['TXNID']);
else 
    $retMsg='Transaction Processed';

if (!isset($rsp['MESSAGE'])) $rsp['MESSAGE']=$retMsg;
   
      
//time_sleep_until(time() + $delay);
if ($delay>0) time_sleep_until(time() + $delay);
      return crResp(array("COMMAND" => $rsp));
  
    //error_log("Comviva DEBUG: response is: $rsp");
    errlog("Comviva DEBUG: response is:", $rsp);
}//process db instructions 

function handleLogin($p)
{
     $funcName=__FUNCTION__;
     errlog("DEBUG: processing $funcName \n", $funcName);

     $dbA = array('x_ChannelReceiverREQUEST_GATEWAY_CODE' => 'IDTTelGW',
               'x_REQUEST_GATEWAY_TYPE' => 'EXTGW',
               'x_LOGIN' => '~DONTCARE',
               'x_PASSWORD' => '~DONTCARE',
               'x_SOURCE_TYPE' => 'EXTGW',
               'x_SERVICE_PORT' => '~DONTCARE');

    processRequest($p, $dbA);
}

function handleComViva($p) {

$rspA = array();

errlog("DEBUG: handleComViva input param - ", $p);
$xA=xmls2a($p);

switch ($xA['TYPE']) {
   case "EXRCTRFREQ": handleTrf($xA);
            break;

   default: 
        throw new Exception("Invalid COMMAND TYPE <$cmdType->nodeValue>", 500); 
}
} //handleComViva

function crResp($rspA) {

$rp = buildxml($rspA);
$ht = new HttpResponse();
$ht->status(200);
$ht->setContentType('text/xml');
$ht->setData($rp);
errlog("INFO: crResp sending resp back: ", $rp);
$ht->send();

} //function sendResp

try {

if (!empty($HTTP_RAW_POST_DATA)) {
   $reqRev=$HTTP_RAW_POST_DATA;
   errlog("INFO: comvivaSvr: HTTP_RAW_POST_DATA", $HTTP_RAW_POST_DATA);
   
}
if (!empty($_POST)) {
   errlog("DEBUG: POST: ", $_POST);
   $reqRevP=array_pop($_POST);
} 
#else 
#throw new Exception("ERROR: Missing Transfer XML", 500);

if (!empty($_GET)) {
   $reqRevG=$_GET;
   errlog("DEBUG: GET ", $_GET);
}
#else 
#throw new Exception("ERROR: Missing Login URL", 500);

//echo "<br/><br/>Received query: <br/>";
//var_dump($reqRev);
//echo "<br/>From: ", $url, "<br/>";

if (isset($reqRevG) ) handleLogin($reqRevG);

if (isset($reqRev) ) handleComViva($reqRev);
else 
throw new Exception("ERROR: Missing Transfer XML", 500);

}
catch (SoapFault $ex) {
    throw new Exception($ex->faultstring, $ex->faultcode); 
}
catch (Exception $e) {
   $msg=$e->getMessage();
   $code=$e->getCode();
   //error_log("Caught exception: $msg, code=$code");
   errlog("Caught exception: $msg, code=", $code);
   $errA=comvivaRespA('EXRCTRFRESP', $code, 'NULL', 'NULL', $msg);
   crResp($errA);
}

?>
