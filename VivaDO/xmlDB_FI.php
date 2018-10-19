<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__));

#include '../QAlib/wslib1.php';
include '../VivaDO/wslib1.php';

define("XMLVERSION", '<?xml version="1.0" encoding="ISO-8859-1"?>');

function openSqliteDB()
{
     $db=new SQLite3('/u/debit/apps/htdocs/QAdata/db/QAImtu.db', SQLITE3_OPEN_READWRITE);
     if (!$db) {
           throw new Exception("ERROR: DB connect failed", 10114);
     }
     return $db;
}

function deleteRecord($db, $app, $key)
{
     $stm1 = sprintf("DELETE from xmlData where app='%s' and key='%s'", $app, $key);
     $ok1 = $db->exec($stm1);
     //if (!$ok1)
     //   throw new Exception("ERROR: DB DELETE failed", 10114);
}

function insertRecord($db, $app, $key, $data)
{
    deleteRecord($db, $app, $key);

    $stm1 = sprintf("INSERT INTO xmlData (app, key, xml, date) VALUES('%s','%s', '%s', DATETIME('now','localtime'))", $app, $key, $data);

    errlog("INFO: stm1", $stm1);

    $cnt = 3;
    while( $cnt != 0 ) {
	$ok1 = $db->exec($stm1);
	if($ok1) {
		break;
		}
	
	sleep(1);
	$cnt--;
	}
    if( $cnt == 0 ) {
    //$ok1 = $db->exec($stm1);
    //if (!$ok1) {
        errlog("ERROR: insertRecord", $ok1);
        throw new Exception("ERROR: DB INSERT failed", 10114);
    }
       
}//insertRecord

function retrieveRecord($db, $app, $key)
{
    $stm1 = sprintf("select xml from xmlData where app='%s' and key='%s'", $app, $key);
    //errlog("INFO: stm1", $stm1);

    $row = $db->querySingle($stm1);

    if ($row == NULL)
        throw new Exception("ERROR: no record found", 10114);

    return XMLVERSION.$row;

}//retrieveRecord

function handler($p)
{
    errlog("INFO: handler", $p);
    $db=openSqliteDB();

    $pList= array('cmd', 'user', 'key');
    $rA=retrievXmlParm2A($p, $pList);

    $cmd=$rA['cmd'];
    $app=$rA['user'];
    $key=$rA['key'];

    $doc = new DOMDocument();
    $doc->loadXML($p);
    $qNode= $doc->getElementsByTagName('qryparams')->item(0);
    $data= $doc->saveXML($qNode);

FILOG("xmlDB_FI.php::handler: cmd is: " . $cmd . " key is: " . $key . " data is: " . $data . "\n");


    switch ($cmd) {

       case 'INSERT':
           insertRecord($db, $app, $key, $data);
           $m='<Result><status>0</status><message>INSERT ok</message></Result>';
           break;

       case 'DELETE':
           deleteRecord($db, $app, $key); 
           $m='<Result><status>0</status><message>DELETE ok</message></Result>';
           break;

       case 'RETRIEVE':
           $m=retrieveRecord($db, $app, $key);
FILOG("xmlDB_FI.php::handler: cmd is: " . $cmd . " key is: " . $key . " record is: " . $m . "\n");
           break;

       default:
           throw new Exception("ERROR: handler unknow cmd $cmd", 10114);
    }

    crResp($m); 

}//handler

function saveXML2DB($p) {

$db=new SQLite3('/u/debit/apps/htdocs/QAdata/db/QAImtu.db', SQLITE3_OPEN_READWRITE);
if (!$db) { 
   throw new Exception("ERROR: DB connect failed", 10114);
}

$pList= array('cmd', 'user', 'key');
$rA=retrievXmlParm2A($p, $pList);

$cmd=$rA['cmd'];
$app=$rA['user'];
$key=$rA['key'];

$doc = new DOMDocument();
$doc->loadXML($p);
$qNode= $doc->getElementsByTagName('qryparams')->item(0);
$m= $doc->saveXML($qNode);

$stm1 = sprintf("DELETE from xmlData where app='%s' and key='%s'", $app, $key);

$ok1 = $db->exec($stm1);
if (!$ok1) 
    throw new Exception("ERROR: DB DELETE failed", 10114);

if ($cmd =='DELETE') { 
     errlog("INFO: saveXML2DB: deleted entry: ", $app.'.'.$key);
     exit(0); 
   }

$stm1 = sprintf("INSERT INTO xmlData (app, key, xml, date) VALUES('%s','%s', '%s', DATETIME('now','localtime'))", $app, $key, $m);

//errlog("INFO: stm1", $stm1);


$ok1 = $db->exec($stm1);
if (!$ok1) 
    throw new Exception("ERROR: DB INSERT failed", 10114);

crResp('<status>ok</status>');
} //saveXML2DB

$reqRev='';

try {

if (!empty($HTTP_RAW_POST_DATA)) {
   $reqRev=$HTTP_RAW_POST_DATA;
   errlog("INFO: HTTP_RAW_POST_DATA", $HTTP_RAW_POST_DATA);

}
if (!empty($_POST)) {
   errlog("DEBUG: POST: ", $_POST);
   $reqRev=$_POST;
   throw new Exception("ERROR: Unexpected http POST Method used", 10113);
}
if (!empty($_GET)) {
   errlog("DEBUG: GET: ", $_GET);
   $reqRev=$_GET;
   throw new Exception("ERROR: Unexpected http GET Method used", 10114);
}

handler($reqRev);

}//try
catch (Exception $e) {
   $msg=$e->getMessage();
   $code=$e->getCode();
   errlog("Caught exception: $msg, code= ", $code);
   $m=XMLVERSION."<Result><status>$code</status><message>$msg</message></Result>";

   crResp($m);
}

?>
