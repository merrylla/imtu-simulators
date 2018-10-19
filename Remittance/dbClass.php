<?php
#set debug up here
$debugDB=1;
function dblog($modName, $msgAnyType, $eNum=0)
{
     global $debugDB;
     $fn=preg_replace('/\.php/', '', basename(__FILE__));

     if (gettype($msgAnyType) == 'string') {
        $errMsg=&$msgAnyType;
     }
     else {
        $errMsg=mix2str($msgAnyType);
     }
     if ($debugDB)
        sLog($fn, $modName, $errMsg, $eNum);
}

Class DBClass {
    private $conn;

    function DBClass() {
 
          dblog("DB:", "DBClass - Top\n");

          $this->conn=new SQLite3('/u/debit/apps/htdocs/QAdata/db/QAImtu.db', SQLITE3_OPEN_READWRITE);
if (!$this->conn) {
   errlog("DB DBClass WARN: getDB connection failed db:", $db);
   return FALSE;
}
         //$this->conn= $db;
    } // end DBClass constructor

   function __destruct() {

         dblog("DEBUG: DBClass - destruct", "\n");

   }

function dbinsert($table, $obj) 
    {
$keys="";
$bkV="";
$count=0;
$bVExp="";
$bbNA= array();
 dblog("DB: dbinsert Top table: $table\n");
 dblog("DB: dbinsert Top obj $obj\n");

foreach ($obj as $key => $value) {
   $bkV=":bkV".$count++;
   $bbNA[$bkV] = $value;
   $keys .=", $key";
   $bVExp .= ", $bkV"; 

 dblog("DB: dbinsert $bkV: $key - $value \n");
}

$keys[0]=" ";
$bVExp[0]=" ";

  $sql="insert into " .$table. " (". $keys . ") values (" . $bVExp . ")";

  dblog("DB: dbinsert sql $sql\n");

        $ok = $this->conn->exec($sql);
        if (!$ok) {
            error_log( "DB: dbInsert ERROR: Cannot insert record into DB using:  $sql\n");
            throw new SoapFault('999999', 'Internal error', 'exec', $ok); 
        } else {
            dblog( "DB: dbInsertTop no problem \n");
            //sqlite_close($dbhandle);
        }
            dblog( "DB: dbInsertTop end \n");

} //dbInsert/


function dbUpdateEle($table, $keyA, $updKey) 
    {

$bkV="";
$count=0;
$bVExp="~";
$bbNA= array();

     dblog( "DB: dbUpdateEle Top table ", "$table\n");
     dblog( "DB: dbUpdateEle Top key ", "$keyA\n");
     dblog( "DB: dbUpdateEle Top updKey ", "$updKey\n");
     //creating the key1 = :bv1 amd key2 = :bv2 format

      foreach ($keyA as $key => $value) {
          $bkV=":bkV".$count++;
          $bbNA[$bkV] = $value;
          $bVExp .= "and $key = '$value'";
      }

      $bVExp1= str_replace("~and", "", $bVExp);

//retrieve the attribute names of the $objName
      $nameAttr ="";
      foreach ($updKey as $name=>$value) {
           $nameAttr .= ", $name = '$value'";
      }
      $nameAttr[0]=" ";

      $sql="update $table set $nameAttr where $bVExp1";

     dblog( "DB: dbUpdateEle middle sql:", " $sql\n");

        $ok = $this->conn->exec($sql);
        if (!$ok) {
            errlog("DB: dbUpdateEle ERROR: Cannot update record into DB using: ", $sql);
            throw new SoapFault('999999', 'Internal error', 'exec', $ok); 
        } else {
            dblog("DB: dbUpdateEle: inserted record into DB", $sql);
            //sqlite_close($dbhandle);
        }

    dblog ("DB: dbUpdateEle End", " \n!!");

 }//dbUpdateEle



function dbRetrieveEleAll($table, $all, $keyA, $objName, &$buff) 
    {

  $bkV="";
  $count=0;
  $bVExp="~";
  $bbNA= array();

  dblog( "DB: dbRetrieveEleAll Top; table:", $table);
  dblog( "DB: dbRetrieveEleAll Top; all, ", $all);
  dblog( "DB: dbRetrieveEleAll Top; key:", $keyA);
  dblog( "DB: dbRetrieveEleAll Top; objName:", $objName);
  dblog( "DB: dbRetrieveEleAll Top; buff:", $buff);
     //creating the key1 = :bv1 amd key2 = :bv2 format

  foreach ($keyA as $key => $value) {
          $bkV=":bkV".$count++;
          $bbNA[$bkV] = $value;
          $bVExp .= "and $key = '$value'";
  }

  $bVExp1= str_replace("~and", "", $bVExp);

//retrieve the attribute names of the $objName
   
  $nameAttr ="";

  if ($all == '*') {
          $nameAttr .= ' *'; 
          $dbPrefix = NULL;
      } else {
      $objvar= new $objName;
      $dbPrefix = $objvar->getPrefix();
      //8/18/10 making up data for now. Need further work
      foreach ($objvar as $name=>$dummy) {
           if ( ($name=='PaymentAccountNumber') || 
                ($name=='PaymentServiceNumber') ||
                ($name=='PaymentLocationCode')  ||
                ($name=='PayerCode') ||
                ($name=='PaymentType') ||
                ($name=='PaymentCurrency') ||
                ($name=='PaymentCountry')
               ) $nameAttr .= ", $name";
           else 
           if ( ($name=='IsValidBranch') ||
                ($name=='FxRate')
               ) $nameAttr .= "";
           else 
             $nameAttr .= ", $dbPrefix$name";
           }
      $nameAttr[0]=" ";
      } // not using * card

  $sql="select $nameAttr from $table where $bVExp1";
  dblog( "DB: dbRetrieveEleAl sql:", $sql);
  $found = false;
  $row = $this->conn->querySingle($sql, true); 
  dblog( "DEBUG: row:", $row);
  $buff[0] = new $objName;
  foreach ($row as $key => $elements) {
    dblog( "DEBUG: looking at key : $key, and value :", $elements);
   //getting around empty string as NULL
    if (is_string($elements)) { 
       if (strlen($elements) > 0) { 
          $buff[0]->setElement($key, $elements, $dbPrefix);
          dblog( "DEBUG: buff[0]->setElement: $key, $elements,", $dbPrefix);
          $found = true;
          }
       } else if (!is_null($elements)) {
           $buff[0]->setElement($key, $elements, $dbPrefix);
           $found = true;
           dblog( "DEBUG: buff[0]->setElement: $key, $elements, ", $dbPrefix);
       } // end else if not null
    } // end foreach

  if ($found==false) {
     dblog( "DEBUG: did not find a row unsetting buff ", $buff);
     unset ($buff[0]);
     }
  dblog ("DEBUG: returning buffer size of ", sizeof($buff));
  dblog ("DEBUG: returning buffer ", $buff);
  dblog ("DB: End dbRetrieveEleAll", "\n!!");
  if (empty($buff)) {
     return FALSE;
  } else if (!$buff) {
        throw new SoapFault('999999', 'Internal error', 'SQL select', $buff); 
        return FALSE;
  } else {
        return TRUE;
  } 

// be done in the __destruct
// oci_close($this->conn);


 }//dbRetrieveEleAll


function dbRetrieveEle($table, $keyA, $objName, &$buff) 
    {
    dblog ("DB: End dbRetrieveEle Top table:", $table);
    dblog ("DB: End dbRetrieveEle Top key:", $keyA);
    dblog ("DB: End dbRetrieveEle Top objName:", $objName);

$bkV="";
$count=0;
$bVExp="~";
$bbNA= array();

     //creating the key1 = :bv1 amd key2 = :bv2 format

      foreach ($keyA as $key => $value) {
          $bkV=":bkV".$count++;
          $bbNA[$bkV] = $value;
          $bVExp .= "and $key = '$value'";
      }

      $bVExp1= str_replace("~and", "", $bVExp);

//retrieve the attribute names of the $objName
   
      $objvar= new $objName;
      $dbPrefix = $objvar->getPrefix();
      $nameAttr ="";
      //8/18/10 making up data for now. Need further work
      foreach ($objvar as $name=>$dummy) {
           if ( ($name=='PaymentAccountNumber') || 
                ($name=='PaymentServiceNumber') ||
                ($name=='PaymentLocationCode')  ||
                ($name=='PayerCode') ||
                ($name=='PaymentType') ||
                ($name=='PaymentCurrency') ||
                ($name=='PaymentCountry')
               ) $nameAttr .= ", $name";
           else 
           if ( ($name=='IsValidBranch') ||
                ($name=='FxRate')
               ) $nameAttr .= "";
           else 
             $nameAttr .= ", $dbPrefix$name";
      }
      $nameAttr[0]=" ";

      $sql="select $nameAttr from $table where $bVExp1";
      dblog( "DB: dbRetrieveEle sql:", $sql);

      $buff= $this->conn->querySingle($sql, true);
      dblog ("DB: dbRetrieveEle  buff:", $buff);
      if (!$buff) {
        throw new SoapFault('999999', 'Internal error', 'SQL select', $buff); 
        return FALSE;
      }

// be done in the __destruct
// oci_close($this->conn);

    dblog( "DB: dbRetrieveEle end", "\n");

}//dbRetrieveEle



function dbRetrieveObj($table, $keyA, $objName, &$buff) 
    {

$bkV="";
$count=0;
$bVExp="~";
$bbNA= array();
    dblog ("DB: End dbRetrieveObj Top table:", "$table\n");
    dblog ("DB: End dbRetrieveObj Top key:", "$keyA\n");
    dblog ("DB: End dbRetrieveObj Top objName:", "$objName\n");

     //creating the key1 = :bv1 amd key2 = :bv2 format

      foreach ($keyA as $key => $value) {
          $bkV=":bkV".$count++;
          $bbNA[$bkV] = $value;
          $bVExp .= "and $key = '$value'";
      }

      $bVExp1= str_replace("~and", "", $bVExp);

      $sql="select * from $table where $bVExp1";

      dblog ("DB: End dbRetrieveObj sql:", "$sql\n");

        $buff= $this->conn->querySingle($sql, true);
    dblog ("DB: dbRetrieveObj  buff:", "$buff\n");
      if (!$buff) {
        throw new SoapFault('999999', 'Internal error', 'SQL select', $buff); 
        return FALSE;
      }

// be done in the __destruct
// oci_close($this->conn);

      dblog ("DB: End dbRetrieveObj bottom", "\n");

}//dbRetrieve

} // end of DBClass
?>
