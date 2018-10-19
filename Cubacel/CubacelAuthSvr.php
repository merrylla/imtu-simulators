<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));

//include '../QAlib/ImtuTopUpClass.php';
include '../QAlib/wslib1.php';
$authwsdl = "http://devcall02.ixtelecom.com/wsdl/Cubacel_AuthenticationService.wsdl";

/* this was taken from Prasan and it is getting changed over according
   to the wsdl for Cubacel */

date_default_timezone_set('America/New_York');

function getDBCubacel()
{

$db=new SQLite3('/u/debit/apps/htdocs/QAdata/db/QAImtu.db', SQLITE3_OPEN_READWRITE);
if (!$db) {
   errlog("WARN: getDB connection failed", $db);
   return FALSE;
}

//app, key, xml, date, misc
errlog("INFO: getDBCubacel ", $a.':'.$k);
$stm1 = sprintf("SELECT xml from xmlData where app='Cubacel' and key = '5346850909' and date>date('now');");
$dbdata = $db->querySingle ($stm1, true);
if (!$dbdata) {
   errlog("WARN: getDB select failed", $dbdata.':'.$stm1);
   return FALSE;
}
errlog("INFO: getDB returning xml", $dbdata['xml']);

return $dbdata['xml'];

}//getDB


function randTicket() {
  $ret = '';
  $hex = '0123456789ABCDEF';
  for ($i=0; $i<24; $i++) {
      $d = rand(0,15);
      if ($i > 0 && $i % 4 == 0) {
	$newChar = '-';
      } else $newChar = $hex[$d];
      $ret .= $newChar;
      }
   errlog("randTicket: retString = ", $ret);
   return $ret;
} //end randTicket

class SoapHandler {

//for Cubacel AuthenticationEndpoint web service
   function ChangeAccountPassword($p)
   {
    errlog("INFO: ChangeAccountPassword $p = ", $p);
    $rspA = array();
   }

   function GetPrivilegesInfo($p)
   {
    errlog("INFO: GetPrivilegesInfo $p = ", $p);
    $rspA = array();
   }

   function GetDistributorInfo($p)
   {
    errlog("INFO: GetDistributorInfo $p = ", $p);
    $rspA = array();
   }

   function GetSessionTicket($p)
   {
    errlog("INFO: GetSessionTicket p = ", $p);
    // get the latest record for Cubacel
    $xml = getDBCubacel();
    // take out the queryparms
    $xml = substr($xml, 10);
    // find out if 504 is one of the xml values  
    $pos = strpos($xml, '>504<');
    $pos2 = strpos($xml, '>500<');
    $pos3 = strpos($xml, '>503<');
    $pos4 = strpos($xml, '>501<');
    $pos5 = strpos($xml, '>502<');
    errlog("INFO: GetSessionTicket xml= %s", $xml, $pos);
    errlog("INFO: GetSessionTicket pos = %s", $pos);
    errlog("INFO: GetSessionTicket pos2 = %s", $pos2);
    errlog("INFO: GetSessionTicket pos3 = %s", $pos3);
    errlog("INFO: GetSessionTicket pos4 = %s", $pos4);
    errlog("INFO: GetSessionTicket pos5 = %s", $pos5);
    $day = date ('d');
    errlog("INFO: GetSessionTicket day= ", $day);
    $rspA = array();
    $accountID = $p->AccountId;
    errlog("INFO: GetSessionTicket account= ", $accountID);
    $pwd = $p->Password;
    errlog("INFO: GetSessionTicket pwd= ", $pwd);
    if ($accountID != 'salesidt') { 
       errlog("GetSessionTicket failed on AccountId ", $accountID);
       $rspA = array( 'ValueOK' => 'false', 
                   'Message' => 'USER ' + $p['AccountId'] . ' NOT AUTHENTICATED',
                   'RequestTime' => new DateTime('NOW'),
                   'ResponseTime' => new DateTime('NOW')
                 );
    } elseif ($pwd != 'SalKinIdt.05') {
       errlog("GetSessionTicket failed on Password", $pwd);
       $rspA = array( 'ValueOK' => 'false', 
                   'Message' => 'USER ' + $p['AccountId'] . ' NOT AUTHENTICATED',
                   'RequestTime' => new DateTime('NOW'),
                   'ResponseTime' => new DateTime('NOW')
                 );
    } elseif (((($pos === false) && ($pos2 === false)) && 
             (($pos3 === false)  && ($pos4 === false))) && ($pos5 === false)) {
       errlog("GetSessionTicket about to create ticket for ", $accountID);
       $ticket = randTicket();
       errlog("GetSessionTicket ticket", $ticket);
       $resArr = array ('ValueOk' => 'true',
			'Message' => 'USER ' . $accountID . ' AUTHENTICATED',
                      //  'RequestTime' => 0);
			'RequestTime' => date("Y-m-d\TH:i:sO"),
			'ResponseTime' => date("Y-m-d\TH:i:sO"));
       errlog("GetSessionTicket result", $resArr);
			
       $rspA = array('Result' => $resArr, 
		'SessionTicket' => array('Ticket' => $ticket));
    } elseif ($pos == 4)  {  // 503 was in the latest DBrec for Cubacel .. return nothing 
       errlog("GetSessionTicket exiting without code or XML");
        $rspA = array();
	exit();
         //header("HTTP/1.0 503 Service Unavailable");
    } elseif ($pos4 == 23)  {  // 503 was in the latest DBrec for Cubacel .. return nothing 
       errlog("WARN: Delaying resp for 61 seconds");
          time_sleep_until(microtime(true) + 61);
       errlog("GetSessionTicket returning xml with 503 HTTP code");
        header("HTTP/1.0 500 Service Unavailable");
        $rspA = array("503");
    } elseif ($pos5 == 23)  {  // 502 was in the latest DBrec for Cubacel .. return nothing 
       errlog("WARN: Delaying resp for 21 seconds");
          time_sleep_until(microtime(true) + 21);
       errlog("GetSessionTicket returning xml with 503 HTTP code");
       $ticket = randTicket();
       errlog("GetSessionTicket ticket", $ticket);
       $resArr = array ('ValueOk' => 'true',
			'Message' => 'USER ' . $accountID . ' AUTHENTICATED',
                      //  'RequestTime' => 0);
			'RequestTime' => date("Y-m-d\TH:i:sO"),
			'ResponseTime' => date("Y-m-d\TH:i:sO"));
       errlog("GetSessionTicket result", $resArr);
       $rspA = array('Result' => $resArr, 
		'SessionTicket' => array('Ticket' => $ticket));
			
    } elseif ($pos3 == 4)  {  // 503 was in the latest DBrec for Cubacel .. return nothing 
       errlog("GetSessionTicket returning xml with no HTTP code");
        header("HTTP/1.0 503 Service Unavailable");
        $rspA = array();
    } else { // pos2
       errlog("GetSessionTicket trigger error 500 ");
       $rspA = array();
        header("HTTP/1.0 500 Service Unavailable");
        //trigger_error("500", E_USER_ERROR);
	//header("HTTP/1.0 500");
       //exit();
    }
    errlog("GetSessionTicket rspA = ", $rspA);
    return $rspA;
   }

} //SoapHandler

  $day = date ('d');
  if (strcmp($day, '5') == 0) 
     errlog("Main: having a bad day = ", $day);
   
  errlog('Main', $HTTP_RAW_POST_DATA);

  $authServer = new SoapServer($authwsdl, 
       array('uri' => "http://200.13.144.57:5976/VirtualPayment/",
	     'soap_version' => SOAP_1_2,
	     'trace' => 1));
  error_log("Main: created server\n", 3, "/u/debit/logs/".$fn.".log");

  $authServer->setClass("SoapHandler");
  error_log("Main: setClass\n", 3, "/u/debit/logs/".$fn.".log");
  $authServer->handle();
  error_log("Main: handling Class\n", 3, "/u/debit/logs/".$fn.".log");
?>
