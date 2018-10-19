<?php

function getDB($a, $k)
{

$db=new SQLite3('/u/debit/apps/htdocs/QAdata/db/QAImtu.db', SQLITE3_OPEN_READWRITE);
if (!$db) {
   errlog("WARN: getDB connection failed", $db);
   return FALSE;
}

//app, key, xml, date, misc
errlog("INFO: getDB app + key", $a.':'.$k);
$stm1 = sprintf("SELECT xml, date, misc from xmlData where app='%s' and key='%s'", $a, $k);
$dbdata = $db->querySingle ($stm1, true);
if (!$dbdata) {
   errlog("WARN: getDB select failed", $dbdata.':'.$stm1);
   return FALSE;
}

return $dbdata['xml'];

}//getDB

function insertQuoteDB($k, $xmlArr)
{
	        errlog("Top of insertQuoteDB with key=", $k);
                errlog (" and xmlArr=", $xmlArr);
	$dbhandle=new SQLite3('/u/debit/apps/htdocs/QAdata/db/QAImtu.db', SQLITE3_OPEN_READWRITE);
	if (!$dbhandle) {
   	   errlog("WARN: insertQuoteDB connection failed", $dbhandle);
   	return FALSE;
	}

        $xmlStr = "<qryparams>";
        errlog("Initialized xmlStr=", $xmlStr);
        foreach ($xmlArr as $key => $val) {
                $myString = sprintf("INFO: inserting QuoteDB key= '%s' + val='%s'", $key, $val);
                $xmlStr .= "<" . $key . ">" . $val . "</" . $key . ">";
	        errlog($myString, $xmlStr);
                }
        $xmlStr .= "<delay>0</delay><abort>false</abort></qryparams>";
	errlog("INFO: insertQuoteDB xml=", $xmlStr);

	$stmt = "DELETE FROM xmlData WHERE App='Multired' and key='" . 
                 $k['key']. "';";
        errlog("Delete old quote first:", $stmt);
	$ok = $dbhandle->exec($stmt);
	if (!$ok) {
            errlog("ERROR: Cannot delete Quote from DB using: ", $stmt);
	} else {
            errlog("INFO: deleted Quote from DB", $stmt);
	}
	$ok = $dbhandle->exec($stmt);
	$stmt = "INSERT INTO xmlData VALUES ('" . $k['app'] . "', '" . $k['key'] . "', '";
        $stmt .= $xmlStr . "', date('now'), NULL)";
	errlog("INFO: Statemt to use", $stmt);
	$ok = $dbhandle->exec($stmt);
	if (!$ok) {
            errlog("ERROR: Cannot insert Quote into DB using: ", $stmt);
	} else {
            errlog("INFO: inserted Quote into DB", $stmt);
	}
	sqlite_close($dbhandle);
}

function insertTransDB($k, $xmlArr)
{
	errlog("Top of insertTransDB with key=", $k);
	errlog("Top of insertTransDB with xmlArr=", $xmlArr);
	$dbhandle=new SQLite3('/u/debit/apps/htdocs/QAdata/db/QAImtu.db', SQLITE3_OPEN_READWRITE);
	if (!$dbhandle) {
   	   errlog("WARN: insertTransDB connection failed", $dbhandle);
   	return FALSE;
	}
        $xmlStr = "<qryparams>";
        errlog("Initialized xmlStr=", $xmlStr);
        foreach ($xmlArr as $key => $val) {
                $val = (is_bool($val)) ? (($val) ? 'true' : 'false') : $val; 
		$myString = sprintf("INFO: inserting TransDB key= '%s' + val='%s'", $key, $val);
                $xmlStr .= "<" . $key . ">" . $val . "</" . $key . ">";
	        //errlog($myString, $xmlStr);
                }
        $xmlStr .= "<delay>0</delay><abort>false</abort></qryparams>";
	$stmt = "INSERT INTO xmlData VALUES ('" . $k['app'] . "', '" . $k['key'] . "', '";
        $stmt .= $xmlStr . "', date('now'), NULL)";
	$ok = $dbhandle->exec($stmt);
	if (!$ok) {
            errlog("ERROR: Cannot insert Trans into DB using: ", $stmt);
	} else {
            errlog("INFO: inserted Trans into DB", $stmt);
	}
	sqlite_close($dbhandle);
} // end of insertTransDB

function updateTransDBRec($k, $xmlArr, $app)
/* When a pin is cancelled a record in the DB needs to be updated with the 
   correct status.  Since there was no other routine t update a DB record and
   the update is very specific to the data that is already there and the 
   specific situation where a Pin is being cancelled for Quippi, it seemed
   that this routine belonged here instead of with the other DB 
   manipulation routines in ImtuTopUpClass. */
{
        errlog("Top of updateTransDBRec with key=", $k);
        errlog("Top of updateTransDBRec with xmlArr=", $xmlArr);
        $dbhandle=new SQLite3('/u/debit/apps/htdocs/QAdata/db/QAImtu.db', SQLITE3_OPEN_READWRITE);
        if (!$dbhandle) {
           errlog("WARN: updateTransDBRec connection failed", $dbhandle);
        return FALSE;
        }
        $xmlStr = "<qryparams>";
        errlog("Initialized xmlStr=", $xmlStr);
        foreach ($xmlArr as $key => $val) {
                $val = (is_bool($val)) ? (($val) ? 'true' : 'false') : $val; 
                $myString = sprintf("INFO: inserting TransDB key= '%s' + val='%s'", $key, $val);
                $xmlStr .= "<" . $key . ">" . $val . "</" . $key . ">";
                errlog($myString, $xmlStr);
                }
        $xmlStr .= "<delay>0</delay><abort>false</abort></qryparams>";
        $stmt = "UPDATE xmlData SET xml='" . $xmlStr . "' WHERE app='" . $app . "' and key='" . $k . "'";
//        $stmt .= $xmlStr . "', date('now'), NULL)";
        $ok = $dbhandle->exec($stmt);
        if (!$ok) {
            errlog("ERROR: Cannot update Trans into DB using: ", $stmt);
        } else {
            errlog("INFO: updated Trans into DB", $stmt);
        }
        sqlite_close($dbhandle);
} // end of updateTransDBRec

function deleteTransDBRec($k)
{
	errlog("Top of deleteTransDBRec with key=", $k);
	$dbhandle=new SQLite3('/u/debit/apps/htdocs/QAdata/db/QAImtu.db', SQLITE3_OPEN_READWRITE);
	if (!$dbhandle) {
   	   errlog("WARN: deleteTransDBRec connection failed", $dbhandle);
   	return FALSE;
	}

	$stmt1 = "DELETE FROM xmlData WHERE app='" . $k['app'] . "' and key='" . $k['key'] . "';'";
	$ok = $dbhandle->exec($stmt1);
	if (!ok) {
            errlog("INFO: Could not Delete  Trans record from DB with ", $stmt1);
	} else {
            errlog("INFO: Deleted  Trans record from DB with ", $stmt1);
	}
	sqlite_close($dbhandle);
} // end of deleteTransDBRec

//that is a parameter to this function.  It decides whether a reversal is allowed and if
// so how much the user gets back and if not, it returns the status code relating the problem.
function reversalAllowed($id)
{
	$tid = 'T' . $id;
	errlog("INFO: running reversalAllowed tid :' ", $tid);
        $rA=array();
        $xA=array('billing_ref' => 'a0',
                  'cost_usd' => 'a1',
		  'date' => 'a2');
        date_default_timezone_set('America/Mexico_City');
        $date = date('m-d-Y');
	$rA=array('date' => 'a2');
        $key= array('app' => 'Multired', 'key' => $tid);

	$tpObj= new TopupObj($key, $xA, $rA);
        if (array_key_exists ('date', $tpObj->xe)) {
           if (($tpObj->xe['date'] != $date) && (strcmp($tpObj->xe['date'],"today")!=0)) { 
              return 204;
	 }
       } 
        if (array_key_exists ('cost_usd', $tpObj->xe)) {
           if ($tpObj->xe['cost_usd'] == 0) { // reversal was alredy done
              return 302;
	 }
       } 
	  $len =strlen($tpObj->xe['billing_ref']);
          $billRef = $tpObj->xe['billing_ref'];
	errlog("INFO: running reversalAllowed found :' ", $billRef);
          $rA=array();
          $xA=array('cost_usd' => 'a0',
                  'sender' => 'a1',
		  'status' => 'a2',
		  'type' => 'a3');
          $key= array('app' => 'Multired', 'key' => $billRef);
	  $refObj= new TopupObj($key, $xA, $rA);
          if (array_key_exists ('type', $refObj->xe)) {
           if (strcmp($refObj->xe['type'],"giftCard") == 0) {
              return $refObj->xe['status'];
	      }
           } 
	  $len=$len -4;
          $lenm3 = $len - 3;
	  for ($i=$len; $i>$lenm3; $i--) {
	    if ($billRef[$i] != '0') {
	       errlog("INFO reversalAllowed validating BillRef failed with", $billRef);
               return 203;
               }
            }
          return 0;
} // reversalAllowed

// Simply delete the transaction when the reversal is applied so that it cannot 
// be applied again.
function reversalApplied($id)
{
	$k = 'T' . $id;
	errlog("Top of reversalApplied with key=", $k);
	$dbhandle=new SQLite3('/u/debit/apps/htdocs/QAdata/db/QAImtu.db', SQLITE3_OPEN_READWRITE);
	if (!$dbhandle) {
   	   errlog("WARN: reversalApplied connection failed", $dbhandle);
   	return FALSE;
	}
	$stmt = sprintf("DELETE FROM xmlData WHERE app='Multired' and key='%s';", $k);
	$ok = $dbhandle->exec($stmt);
        if (!$ok) {
           errlog("WARN: reversalApplied delete failed: ", $stmt);
           return FALSE;
        }
}
	
// breaking the multilevel object or array into a simple array
function complex2simpleA($p)
{
    $rA=array();

    foreach($p as $key=>$val) {
      $type = gettype($val);
      if ($type == 'array' || $type=='object') {

          $a=complex2simpleA($val);
          $rA=array_merge($rA, $a);
       }
      else { 
        $rA[$key] = $val;
       }
    }//end foreach 
   return $rA;
} //complex2simpleA

function rcursiveForEach(&$a, $o)
{

      foreach($a as $key => &$val) {

          if (gettype($val) == 'array') rcursiveForEach($val, $o);
          else {
             if( array_key_exists($key, $o->re)) {
                 $val=$o->re[$key];
               }
          }
      }//foreach 

}//rcursiveForEach

Class TopupObj {
   
   public $xe=array();  // query inputs that need validation
   public $re=array();  // response parameters instructions
   public $delay;
   public $abort;

   function TopupObj($k, $xa, $ra) {

       $this->delay=0;
       $this->abort='false';

       $xmld=getDB($k['app'], $k['key']);

           errlog("INFO: TopUp:xmld", $xmld);
       $xA=array();

       if ($xmld != FALSE) {

          $ca=array_merge($xa,$ra, array('delay'=>'delay', 'abort'=>'abort'));

          $xA=retrievXmlParm2A($xmld, $ca);
           errlog("INFO: TopUp:xA", $xA);

          foreach($xa as $key => $val) {
              if (array_key_exists($val, $xA)) 
                 if (isset($xA[$val])) $this->xe[$key]=$xA[$val]; 
          }

           errlog("INFO: Topup: xe", $this->xe);
          foreach($ra as $key => $val) {
              if (array_key_exists($val, $xA)) 
                 if (isset($xA[$val])) $this->re[$key]=$xA[$val]; 
          }

           errlog("INFO: Topup: re", $this->re);
          if (array_key_exists('delay', $xA)) 
                 if (isset($xA['delay'])) $this->delay= (int) $xA['delay'];
                
          if (array_key_exists('abort', $xA)) 
                 if (isset($xA['abort'])) $this->abort=$xA['abort'];

         errlog("INFO: dump DB: ",  array_merge($this->xe, $this->re));
      }//end if

     }//TopupObj constructor
           
   function validateReqInput($p) {

          return processRequestX($p, $this->xe);
   }


   function buildRspData(&$rspA) {

       $delay=$this->delay;
       if ($delay > 0) {
          errlog("WARN: Delaying resp for ", $delay." seconds");
          time_sleep_until(microtime(true) + $delay);
       }
       if ($this->abort == 'true') {

          errlog("WARN: Received abort instruction - exiting", "buildRspData");
          exit(0);
       }

      foreach($rspA as $key => $val) {

           if ( !array_key_exists($key, $this->re)) $this->re[$key]=$val;
      } 


      return $this->re;
   }//buildRspData

  function isAbort() {
   return $this->abort;
  }
  
  function getDelay() {
   return $this->delay;
  }
   function buildRspData1(&$rspA) {

       $delay=$this->delay;
       if ($delay > 0) {
          errlog("WARN: Delaying resp for ", $delay." seconds");
          time_sleep_until(microtime(true) + $delay);
       }
       if ($this->abort == 'true') {

          errlog("WARN: Received abort instruction - exiting", "buildRspData");
          exit(0);
       }

      rcursiveForEach($rspA, $this);

      return $rspA;
   }//buildRspData

}//TopupObj class


?>
