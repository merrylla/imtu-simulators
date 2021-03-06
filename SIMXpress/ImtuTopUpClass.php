<?php

function getDB($a, $k)
{

//FILOG("ImtuTopUpClass.php:getDB:\n");

$db=new SQLite3('/u/debit/apps/htdocs/QAdata/db/QAImtu.db', SQLITE3_OPEN_READWRITE);
if (!$db) {
//FILOG("ImtuTopUpClass.php:getDB: db connection failed\n");
   errlog("WARN: getDB connection failed", $db);
   return FALSE;
}

//app, key, xml, date, misc
errlog("INFO: getDB app + key", $a.':'.$k);
//$stm1 = sprintf("SELECT xml, date, misc from xmlData where app='%s' and key like '%s'", $a, $k);
$stm1 = sprintf("SELECT xml, date, misc from xmlData where app='%s' ", $a);
$dbdata = $db->querySingle ($stm1, true);
//FILOG("ImtuTopUpClass.php:getDB:query: " . $stm1 . "\n");
if (!$dbdata) {
   errlog("WARN: getDB select failed", $dbdata.':'.$stm1);
//FILOG("ImtuTopUpClass.php:getDB: select failed\n");
   return FALSE;
}

return $dbdata['xml'];

}//getDB

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

       errlog("FI Inside TopupObj Constructor", "TopupObj");
//FILOG("FI Inside TopupObj Constructor\n");


       $this->delay=0;
       $this->abort='false';

       $xmld=getDB($k['app'], $k['key']);
//FILOG("TopupObj: xmld= " . $xmld . "\n");

       $xA=array();

       if ($xmld != FALSE) {

          $ca=array_merge($xa,$ra, array('delay'=>'delay', 'abort'=>'abort'));
//FILOG("ImtuTopUpClass.php:TopupObj: ca= " . print_r($ca, true) . "\n");

          $xA=retrievXmlParm2A($xmld, $ca);
           errlog("INFO: getDB: ", $xA);
// see what we got from db:
//FILOG("ImtuTopUpClass.php:TopupObj: xA= " . print_r($xA, true) . "\n");


          foreach($xa as $key => $val) {
           errlog("Inside TopU[: looking at xa $key: ", $val);
              if (array_key_exists($val, $xA)) 
           //errlog("Inside TopU[: keyExists for $val $xA", array_key_exists($val, $xA));
                 if (isset($xA[$val])) $this->xe[$key]=$xA[$val]; 
           //errlog("Inside TopUp: setting xe[$key]", $this->xe[$key]);
          }

          foreach($ra as $key => $val) {
           //errlog("Inside TopUp: looking at ra $key: ", $val);
              if (array_key_exists($val, $xA)) 
           //errlog("Inside TopUp: keyExists for $val $xA", array_key_exists($val, $xA));
                 if (isset($xA[$val])) $this->re[$key]=$xA[$val]; 
           //errlog("Inside TopUp: setting re[$key]", $this->re[$key]);
          }

          if (array_key_exists('delay', $xA)) {
                 if (isset($xA['delay'])) $this->delay= (int) $xA['delay'];
                 errlog("Inside TopUp: setting delay", $this->delay);
                 }      
          if (array_key_exists('abort', $xA)) { 
                 if (isset($xA['abort'])) $this->abort=$xA['abort'];
                 errlog("Inside TopUp: setting abort", $this->abort);
                 }      

        errlog("INFO: dump DB: ",  array_merge($this->xe, $this->re));
      }//end if

     }//TopupObj constructor
           
   function validateReqInputINOV8($p) {

//FILOG("FI Inside validateReqInputINOV8 \n");
        errlog("Inside validateReqInputINOV8 : Top", $p);
        return processRequestINOV8($p, $this->xe);
   }
           
   function validateReqInput($p) {

//FILOG("FI Inside validateReqInput \n");
        errlog("Inside validateReqInput: Top", $p);
          return processRequestX($p, $this->xe);
   }


   function buildRspData(&$rspA) {

          errlog("FI Inside buildRspData", "buildRspData");

       $delay=$this->delay;
       if ($delay > 0) {
          errlog("WARN: Delaying resp for ", $delay." seconds");
          time_sleep_until(microtime(true) + $delay);
       }
       if ($this->abort == 'true') {

          errlog("WARN: Received abort instruction - exiting", "buildRspData");
          exit(0);
       }
      errlog("Inside buildRspData: looking to update : ", $this->re);

      foreach($rspA as $key => $val) {

           errlog("Inside buildRspData: looking at rspA $key: ", $val);
           if ( !array_key_exists($key, $this->re)) $this->re[$key]=$val;
           errlog("Inside buildRspData: this->re[$key]  ",  $this->re[$key]);
      } 


      return $this->re;
   }//buildRspData


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
