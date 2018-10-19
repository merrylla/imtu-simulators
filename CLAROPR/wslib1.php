<?php

date_default_timezone_set('America/New_York');

function FILOG( $str )
{
	$FIlogfile='/tmp/FI_inov8.log';
	$FIlog = fopen($FIlogfile,'a+');

	fwrite($FIlog, "FILOG: " . $str);
	fflush($FIlog);
	fclose($FIlog);

} // end FILOG


// php session handling
// need $sessKey
// session content is array()
function sessStart()
{
    global $sessKey;
    session_id($sessKey);

    if (!session_start() ) {

        errlog( "SESSION START failed. EXITING!!\n");
        throw new Exception("SYSTEM ERROR: unable to start session", 999);
    }

}

function sessStore($key, $elements)
{
   sessStart();

   if (isset($_SESSION[$key]) ) unset($_SESSION[$key]);

   $_SESSION[$key] = $elements;

}

function sessRetrive($key)
{
   sessStart();

   if (array_key_exists($key, $_SESSION) )
       return $_SESSION[$key];

   else
       return array();

}



//httpResponse 
function crResp($msg) {

$rp = &$msg;
$ht = new HttpResponse();
$ht->status(200);
$ht->setContentType('text/xml');
$ht->setData($rp);
errlog("INFO: crResp sending resp back: ", $rp);
$ht->send();

FILOG("wslib1.php::crResp: ");
FILOG($rp . "\n");


} //function crResp

function errlog($modName, $msgAnyType, $eNum=0)
{
     global $fn;

     if (gettype($msgAnyType) == 'string') {
        $errMsg=&$msgAnyType;
     }
     else {
        $errMsg=mix2str($msgAnyType);
     }
     sLog($fn, $modName, $errMsg, $eNum);
}

function parseSoapBody($xmlinput)
{
$rA=array();
$dom = new DOMDocument();
if (!$dom->loadXML($xmlinput) ) return $rA;
//if (!$dom->load($xmlinput) ) echo "load ".$xmlinput." failed"."\n";
//$Body = $dom->getElementsByTagName('Body');
$BodyParams = $dom->firstChild->firstChild->getElementsByTagName('*');

foreach ($BodyParams as $node) {
//   echo "node: ".$node->getNodePath()."\n";
   
       $nodeName=$node->tagName;
        $nodeName=preg_replace("/.*:(.*)/", "$1", $nodeName);
            $eA[$nodeName]= $node->nodeValue;
}
array_shift($eA);
return $eA;

}//parseSoapBody


function retrievXmlParm2A($xmlinput, $paramList)
{
$eA=array();
$dom = new DOMDocument();
if (!$dom->loadXML($xmlinput) ) return $rA;
$xmlparams = $dom->getElementsByTagName('*');  //find all nodes


foreach ($xmlparams as $node) {
//   echo "node: ".$node->getNodePath()."\n";
   foreach($paramList as $param) {

       $nodeName=$node->tagName;
//        echo $nodeName." ".$param."\n";
        $nodeName=preg_replace("/.*:(.*)/", "$1", $nodeName);
      if ($nodeName == $param )
            $eA[$param]= $node->nodeValue;
   }
}

return $eA;
}//retrievXmlParm2A
                         

function sLog($fname, $func, $errstr, $errno = 0 )
// system log
// $func = function name
// $fname = name of service
{
  $location="/u/debit/logs/".$fname.".log";
  $timstamp=date("M j G:i:s");
  $pid=getmypid();
  error_log("$timstamp -$pid- $func: [$errno] $errstr\n", 3, $location);
}

function getDBdata2($dbTable, $obj, $keyA)
// strip the remap
{
$db=new DBClass();
$dbElements = array(); //use to store elements to be populated back to db
$dataBuf[]=array();    //use to store elements retrieved from db

//$key= array('x_account_number'=> $racct);

$db->dbRetrieveEleAll($dbTable, '*', $keyA, $obj, $dataBuf);

$fcount=count($dataBuf);
if ($fcount>0) {

   $dbA = $dataBuf[0];
//   $dbA->remap();
//dump_errorlog("INFO: getDBdata dumping: ", $dbA);
   return $dbA;
   }
return FALSE;
}//getDBdata


function getDBdata1($dbTable, $obj, $keyA)
{
$db=new DBClass();
$dbElements = array(); //use to store elements to be populated back to db
$dataBuf[]=array();    //use to store elements retrieved from db

//$key= array('x_account_number'=> $racct);

$db->dbRetrieveEleAll($dbTable, '*', $keyA, $obj, $dataBuf);

$fcount=count($dataBuf);
if ($fcount>0) {

   $dbA = $dataBuf[0];
   $dbA->remap();
//dump_errorlog("INFO: getDBdata dumping: ", $dbA);
   return $dbA;
   }
return FALSE;
}//getDBdata

function mix2str($v)
{
  ob_start();
  var_dump($v);
  $str = ob_get_contents();
  ob_end_clean();
  return $str;
}

function dump_errorlog($msg, $v)
{
  ob_start();
  var_dump($v);
  $msg .= ob_get_contents();
  ob_end_clean();
  error_log($msg);
}

function dumpSession()
{
      foreach ($_SESSION as $key => $value) {

error_log("DEBUG: DUMPING SESSION $key,  -value $value \n");

      }
}

function createSession() {
      if (!session_start() ) throw new SoapFault(1, "Unable to start a new session");
dumpSession();
      return session_id();
}

function sessionSetParams($sid, $params) {
error_log("DEBUG: sessionSetParams call with sid= $sid");

      session_id($sid);

      if (!session_start() ) throw new SoapFault(1, "Unable to resume session with sid= $sid");

      foreach ($params as $key => $value) {
             $_SESSION[$key] = $value;
      }

}//sessionSetParams

function sessionValidation($sid) {
      session_id($sid);

      if (!session_start()) throw new SoapFault(1, "Unable to resume session with sid= $sid"); 

      return $_SESSION;
}//sessionValidation

//$params is array (x_fields => values and r_fields => values);
//$reqX (obj or array) data to be processed
//$req is the client request data
function processRequest($reqX, $ck_params)
{
    $ret=array();
    $req=array();

    if (gettype($reqX) == 'object' ) {
       foreach($reqX as $member=>$data) $req[$member]=$data;
    }
    else
       $req = &$reqX;

//dump_errorlog("DUMMPING processRequest ck_params ", $ck_params);
//dump_errorlog("DUMMPING processRequest reqX ", $req);

    foreach ($ck_params as $key => $value) {

       if (!isset($value)) continue;

       if (preg_match('/^x_/', $key)) {
              $k=preg_replace('/^x_(.*$)/', '${1}', $key);
              if (!array_key_exists($k, $req) ) throw new SoapFault("9998", "Error: found no match for $k");
              else {
                 if ($value == '~DONTCARE') continue;
                 if ($req[$k] != $value) throw new SoapFault("9999", "ERROR: $k expect $value found $req[$k]");
              }
       }
        else 
        if (preg_match('/^r_/', $key))
               {

                 $k=preg_replace('/^r_(.*$)/', '${1}', $key);
                 $ret[$k] = $value;
//error_log("DEBUG: processRequest returning $k => $value \n");
              }
   }//foreach

   return $ret;
}


//$ck_params has the request(X) fields only
//$reqX (obj or array) data to be processed
function processRequestX($reqX, $ck_params)
{

FILOG("wslib1.php::processRequestX:  " . "\n");

    $ret=array();
    $req=array();

FILOG("wslib1.php::processRequestX: gettype returns: " . gettype($reqX) . "\n");
FILOG("wslib1.php::processRequestX: gettype(gettype()) returns: " . gettype(gettype($reqX)) . "\n");
    if (gettype($reqX) == 'object' ) {

       foreach($reqX as $member=>$data) {
FILOG("wslib1.php::processRequestX: member is: " . $member . " data is: " . $data . " Type " . gettype($reqX) . "\n");
		$req[$member]=$data;
		} // end foreach
    }
    else
       $req = &$reqX;

    foreach ($ck_params as $key => $value) {

       if (!array_key_exists($key, $req) ) throw new SoapFault("9998", "Error: missing parameter for $key");
          if ($req[$key] != $value) throw new SoapFault("9999", "ERROR: $key expect $value found $req[$key]");
    }
}//processRequestX

//$ck_params has the request(X) fields only
//$reqX (obj or array) data to be processed
function processRequestINOV8($reqX, $ck_params)
{

FILOG("wslib1.php::processRequestINOV8:  " . "\n");

    $ret=array();
    $req=array();

FILOG("wslib1.php::processRequestINOV8: gettype returns: " . gettype($reqX) . "\n");
FILOG("wslib1.php::processRequestINOV8: gettype(gettype()) returns: " . gettype(gettype($reqX)) . "\n");
    if (gettype($reqX) == 'object' ) {

       foreach($reqX->topupServiceBean as $member=>$data) {
FILOG("wslib1.php::processRequestINOV8: member is: " . $member . " data is: " . $data . "\n");
		$req[$member]=$data;
		} // end foreach
    }
    else
       $req = &$reqX;

FILOG("wslib1.php::processRequestINOV8:ck_params:" . "\n");
FILOG("wslib1.php::processRequestINOV8:ck_params= " . print_r($ck_params,true) . "\n");
    foreach ($ck_params as $key => $value) {

       if (!array_key_exists($key, $req) ) {
FILOG("wslib1.php::processRequestINOV8:ck_params:Error: missing parameter for $key\n");
		 throw new SoapFault("9998", "Error: missing parameter for $key");
		 }
          if ($req[$key] != $value) {
FILOG("wslib1.php::processRequestINOV8:ck_params:ERROR: $key expect $value found $req[$key]\n");
		throw new SoapFault("9999", "ERROR: $key expect $value found $req[$key]");
		}
    }
}//processRequestINOV8

//$params is array (x_fields => values and r_fields => values);
//$reqX (obj or array) data to be processed
//$req is the client request data
function processRequest_org($reqX, $ck_params) 
//TB DELETED 3/28/2012
{
    $ret=array();
    $req=array();

    if (gettype($reqX) == 'object' ) {
       foreach($reqX as $member=>$data) $req[$member]=$data;
    }
    else
       $req = &$reqX;

//dump_errorlog("DUMMPING processRequest ck_params ", $ck_params);
//dump_errorlog("DUMMPING processRequest reqX ", $req);

    foreach ($ck_params as $key => $value) {

       if (!isset($value)) continue;

       if (preg_match('/^x_/', $key)) {
              $k=preg_replace('/^x_(.*$)/', '${1}', $key);
              if (!array_key_exists($k, $req) ) throw new Exception("Error: found no match for $k", 9998);             
              else {
                 if ($value == '~DONTCARE') continue;
                 if ($req[$k] != $value) throw new Exception("Error: $k expect $value found $req[$k]", 9999);
              }
       }
        else {
                 $k=preg_replace('/^r_(.*$)/', '${1}', $key);
                 $ret[$k] = $value;
//error_log("DEBUG: processRequest returning $k => $value \n");
              }
   }//foreach

   return $ret;
}
?>
