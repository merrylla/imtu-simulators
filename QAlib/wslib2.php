<?php

date_default_timezone_set('America/New_York');


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

function openSqliteDB()
{
     $db=new SQLite3('/u/debit/apps/htdocs/QAdata/db/QAImtu.db', SQLITE3_OPEN_READWRITE);
     if (!$db) {
           throw new Exception("ERROR: DB connect failed", 10114);
     }
     return $db;
}

function DBdeleteRecord($db, $app, $key)
{
     $stm1 = sprintf("DELETE from xmlData where app='%s' and key='%s'", $app, $key);
     $ok1 = $db->exec($stm1);
     if (!$ok1)
        throw new Exception("ERROR: DB DELETE failed", 10114);
}//DBdeleteRecord

function DBinsertRecord($db, $app, $key, $data)
{
    DBdeleteRecord($db, $app, $key);

    $stm1 = sprintf("INSERT INTO xmlData (app, key, xml, date) VALUES('%s','%s', '%s', DATETIME('now','localtime'))", $app, $key, $data);

    //errlog("INFO: stm1", $stm1);

    $ok1 = $db->exec($stm1);
    if (!$ok1)
        throw new Exception("ERROR: DB INSERT failed", 10114);
}//insertRecord

function DBretrieveRecord($db, $app, $key)
{
    $stm1 = sprintf("select xml from xmlData where app='%s' and key='%s'", $app, $key);
    //errlog("INFO: stm1", $stm1);

    $row = $db->querySingle($stm1);

    if ($row == NULL) return '';
        //throw new Exception("ERROR: no record found", 10114);

    return $row;

}//retrieveRecord



//httpResponse 
// $ctype is http content type header
function crResp($msg, $ctype='text/xml') 
{

    $rp = &$msg;
    $ht = new HttpResponse();
    $ht->status(200);
    $ht->setContentType($ctype);
    $ht->setData($rp);
    errlog("INFO: crResp sending resp back: ", $rp);
    $ht->send();

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
if (!$dom->loadXML($xmlinput) ) return $eA;
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

// get all elements 
function retrievXmlElems($xnode, $xmlinput)
{
   $eA=array();
   $dom = new DOMDocument();
   if (!$dom->loadXML($xmlinput) ) return $rA;

   $ndom=$dom->getElementsByTagName($xnode)->item(0)->childNodes;

   foreach ($ndom as $node) {
     $nodeName=preg_replace("/.*:(.*)$/", '$1', $node->tagName);
     $eA[$nodeName]= $node->nodeValue;
   }

   return $eA;

}//retrievXmlElems

                         

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
    $ret=array();
    $req=array();

    if (gettype($reqX) == 'object' ) {
       foreach($reqX as $member=>$data) $req[$member]=$data;
    }
    else
       $req = &$reqX;

    foreach ($ck_params as $key => $value) {

       if (!array_key_exists($key, $req) ) throw new SoapFault("9998", "Error: missing parameter for $key");
          if ($req[$key] != $value) throw new SoapFault("9999", "ERROR: $key expect $value found $req[$key]");
    }
}//processRequestX

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
