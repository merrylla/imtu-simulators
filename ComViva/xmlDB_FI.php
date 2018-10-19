<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__));

include 'wslib.php';

define("XMLVERSION", '<?xml version="1.0" encoding="ISO-8859-1"?>');

function openSqliteDB()
{
     $db=new SQLite3('/u/debit/apps/htdocs/QAdata/db/QAImtu.db', SQLITE3_OPEN_READWRITE);
     if (!$db) {
           throw new Exception("ERROR: DB connect failed", 10114);
     }
     return $db;
}

function deleteRecord($db, $key)
{
     $stm1 = sprintf("DELETE from IMTU_Data where X_ACCOUNT_NUMBER='%s'", $key);
     $ok1 = $db->exec($stm1);
} //deleteIMTURecord

function insertRecord($db, $key, $amt, $msisdn, $pin, $user, $pwd, $xCode,
		      $xCode2, $lang1, $lang2, $utc, $refId, $retCode,
			$retMess, $dateFrom, $transID, $delay)
  { 
    deleteRecord($db, $key);
    $stm1 = sprintf("INSERT INTO IMTU_Data 
      (x_account_number, x_Amount, x_sourceMSISDN, x_PIN, x_UserName,        
      x_Password, x_EXTCODE, x_EXTCODE2, x_LANGUAGE1, x_LANGUAGE2, 
     x_utcDateTime, r_ReferenceID, r_ReturnCode, r_ReturnMessage, 
     x_DateFrom, r_TransactionID, delay)
 VALUES('%s', '%s', '%s', '%s', '%s','%s', '%s', '%s', '%s','%s','%s','%s', 
         '%s','%s', '%s', '%s', '%s')", $key, $amt, $msisdn, $pin, $user, 
          $pwd, $xCode, $xCode2, $lang1, $lang2, $utc, $refId, $retCode, 
          $retMess, $dateFrom, $transID, $delay);

FILOG("xmlDB_FI.php::insertRecord stmt: " . $stm1 . "\n");

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

/* function retrieveRecord($db, $app, $key)
{
    $stm1 = sprintf("select x_account_number, x_Amount, x_UserName, x_Password, x_DealerTransactionID, x_EXTCODE, x_sourceMSISDN, x_SELECTOR, x_DateFrom, x_PersonalMessage, r_TransactionID, r_BusinessAccount, r_FeeAccount, r_TransactionAmount, r_ExchangeRate, x_DateTo r_symbol, r_Result,  r_ReturnCode,  r_ReturnMessage, delay from IMTU_Data where app='%s' and key='%s'", $app, $key);
    errlog("INFO: stm1", $stm1);

    $row = $db->querySingle($stm1);

    errlog("INFO: row", $row);
    if ($row == NULL)
        throw new Exception("ERROR: no record found", 10114);

    return XMLVERSION.$row;

} */ //retrieveRecord

function handler($p)
{
    errlog("INFO: handler", $p);
FILOG("xmlDB_FI.php::TopB: " . $p . "\n");
    $db=openSqliteDB();
FILOG("xmlDB_FI.php::opened the DB: " . "\n");

    $pList= array('cmd', 'user', 'key');
FILOG("xmlDB_FI.php::calling retrievXmlParm2A: " . "\n");
    $rA=retrievXmlParm2A($p, $pList);
FILOG("xmlDB_FI.php::setup rA: " . $rA . "\n");

    $cmd=$rA['cmd'];
FILOG("xmlDB_FI.php::setup cmd: " . $cmd . "\n");
    $app=$rA['user'];
FILOG("xmlDB_FI.php::setup app: " . $app . "\n");
    $key=$rA['key'];
FILOG("xmlDB_FI.php::setup key: " . $key . "\n");

    $doc = new DOMDocument();
    $doc->loadXML($p);
    $qNode= $doc->getElementsByTagName('qryparams')->item(0);
    $data= $doc->saveXML($qNode);
    foreach($qNode->childNodes as $node) {
FILOG("xmlDB_FI.php::nodeName: " . $node->nodeName . " nodeValue: " . $node->nodeValue . "\n");
    switch ($node->nodeName) {
       case 'amt':
        $amt = $node->nodeValue;
        break;
       case 'usr':
        $user = $node->nodeValue;
        break;
       case 'pwd':
        $pwd = $node->nodeValue;
        break;
       case 'refID': 
        $refId = $node->nodeValue;
        break;
       case 'retCode':
        $retCode = $node->nodeValue;
        break;
       case 'retMess':
        $retMess = $node->nodeValue;
        break;
       case 'msisdn':
        $msisdn = $node->nodeValue;
        break;
       case 'xCode':
        $xCode = $node->nodeValue;
        break;
       case 'xCode2':
        $xCode2 = $node->nodeValue;
        break;
       case 'transID':
        $transID = $node->nodeValue;
        break;
       case 'pin':
        $pin = $node->nodeValue;
        break;
       case 'lang1':
        $lang1 = $node->nodeValue;
        break;
       case 'lang2':
        $lang2 = $node->nodeValue;
        break;
       case 'date':
        $utcDate = $node->nodeValue;
        break;
       case 'dateFrom':
        $dateFrom = $node->nodeValue;
        break;
       case 'delay':
        $delay = $node->nodeValue;
        break;
       default:
   FILOG("xmlDB_FI.php::nodeName missing in switch statement: " . $node->nodeName . " nodeValue: " . $node->nodeValue . "\n");
   FILOG("xmlDB_FI.php::after switch statement sym: " . $sym . "\n");
    } // end switch
  } // end foreach node as child
      
    /*$aNode = $qNode->getElementsByTagName('amt')->item(0);
FILOG("xmlDB_FI.php::aNode: " . $aNode->nodeName . " : " . $aNode->nodeValue . "\n");
    $amt = $aNode->nodeValue;
FILOG("xmlDB_FI.php::amt: " . $amt " . "\n");
    $aNode= $qNode->getElementsByTagName('user')->item(0);
    $usr = $aNode->nodeValue;
FILOG("xmlDB_FI.php::user: " . $usr " . "\n");
    $aNode= $qNode->getElementsByTagName('pwd')->item(0);
    $pwd = $aNode->nodeValue;
FILOG("xmlDB_FI.php::pwd: " . $pwd " . "\n");
    $aNode= $qNode->getElementsByTagName('transIDDeal')->item(0);
    $transIDDeal = $aNode->nodeValue;
FILOG("xmlDB_FI.php::transIDDeal: " . $transIDDeal " . "\n"); */

FILOG("xmlDB_FI.php::handler: cmd is: " . $cmd . " key is: " . $key . " data is: " . $data . "\n");

    switch ($cmd) {

       case 'INSERT':
           insertRecord($db, $key, $amt, $msisdn, $pin, $user, $pwd, $xCode,
		        $xCode2, $lang1, $lang2, $utcDate, $refId, $retCode,
			$retMess, $dateFrom, $transID, $delay);
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

    crResp($m, true); 

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

crResp('<status>ok</status>', true);
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

   crResp($m, true);
}

?>
