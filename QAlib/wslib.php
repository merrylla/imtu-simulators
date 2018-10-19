<?php

date_default_timezone_set('America/New_York');

function sLog($fname, $func, $errstr, $errno = 0 )
// system log
// $func = function name
// $fname = name of service
{
  $location="/u/debit/logs/".$fname.".log";
  $timstamp=date("M j G:i:s");
  error_log("$timstamp $func: [$errno] $errstr\n", 3, $location);
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
dump_errorlog("INFO: getDBdata dumping: ", $dbA);
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
    // Note that error_log can be found in /var/log/httpd/error_log
    $ret=array();
    $req=array();

    if (gettype($reqX) == 'object' ) {
       //dump_errorlog("setting $member to something missing data", $member);
       foreach($reqX as $member=>$data) $req[$member]=$data;
    }
    else {
       //dump_errorlog("setting $req to ", &$reqX);
       $req = &$reqX;
    }
dump_errorlog("DUMMPING processRequest ck_params ", $ck_params);
dump_errorlog("DUMMPING processRequest reqX ", $req);

    foreach ($ck_params as $key => $value) {

       dump_errorlog("processing $key and ", $value);
       if (!isset($value)) continue;

       if (preg_match('/^x_/', $key)) {
              dump_errorlog("match for ", $key);
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
error_log("DEBUG: processRequest returning $k => $value \n");
              }
   }//foreach

   return $ret;
}

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
              if (!array_key_exists($k, $req) ) throw new SoapFault("9998", "Error: found no match for $k");             
              else {
                 if ($value == '~DONTCARE') continue;
                 if ($req[$k] != $value) throw new SoapFault("9999", "ERROR: $k expect $value found $req[$k]");
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
