<?php

include '../Remittance/rmttObjBase.php';
include 'mno_login.php';
include 'mno_data.php';
include '../Remittance/errClass.php';
include '../Remittance/sevSession.php';
include '../Remittance/respMsg.php';
include '../Remittance/dbClass.php';

date_default_timezone_set('America/New_York');

$rmttRspMsg= new RespMsg;

class GlobeMsgHandler {

function createsessioncmd($parameters) {

	global $rmttRspMsg;

        $serviceSession = new SvcSession;
        $serviceSession->OpenSession('1234');
        $retArg['sessionResultCode']=0;
        $retArg['session']=$serviceSession->getSessId();
        $rmttRspMsg->setRetArg($retArg);

error_log("DEBUG: GetSessionId");

        try {

           return $rmttRspMsg;
        }
        catch (SoapFault $ex) {

error_log("DEBUG: $ex->faultcode, $ex->faultstring, $ex->faultactor, $ex->detail, GetSessionId, GetSessionId Failed. \n");
              $retArg['sessionResultCode']=1;
              return $rmttRspMsg;
	}
}


function logincmd ($parameters) {

error_log("DEBUG: Received logincmd method.\n");

      global $rmttRspMsg;
      $retArg=array();

      $user=$parameters->user;
      $uPassword=$parameters->password;
      $ssID=$parameters->session;

//throw new SoapFault('code', 'string', 'actor', 'detail', 'name', 'header');

       try {
            //validate session ID
            $rmttSess = new SvcSession('1234');
            $pin= $rmttSess->RestoreSession($ssID);

            
            $retArg['sessionResultCode']=0;
            $code = array();

            if (preg_match('/session/i',$user)&&preg_match('/[0-9]{1,4}/', $user, $code)) {

                 $retArg['sessionResultCode']= $code[0];
                 if ($code[0]>0) {
                    $rmttRspMsg->setRetArg($retArg);
                    return $rmttRspMsg;
                  }
            }


            if (preg_match('/login/i',$user)&&preg_match('/[0-9]{1,4}/', $user, $code)) {
                 $retArg['loginResultCode']= $code[0];
            }
            else { 
                 $retArg['transactionCode']=rand(0, 20000000)*100;
//                 $retArg['transactionCode']=rand()*100;
                 $retArg['loginResultCode']=0;
            }

            $rmttRspMsg->setRetArg($retArg);
            return $rmttRspMsg;
        }
        catch (SoapFault $ex) {

error_log("$ex->faultcode, $ex->faultstring, $ex->faultactor, $ex->detail, logincmd failed. \n");
              $retArg['sessionResultCode']=1;
              return $rmttRspMsg;
        }

} //logincmd

function cashincmd ($parameters) {

error_log("DEBUG: Received cashincmd method.\n");

      global $rmttRspMsg;

      $db=new DBClass();
      $dbElements = array(); //use to store elements to be populated back to db
      $dataBuf[]=array();    //use to store elements retrieved from db

      $retArg=array();
      $amount=$parameters->amount;
      $to=$parameters->to;
      $subString =NULL;

      if (isset( $parameters->remitterfirstname)) {
          $subString = $parameters->remitterfirstname;
          $dbElements['remitterfirstname'] = $parameters->remitterfirstname;
       }
      if (isset( $parameters->remitterlastname)) {
          $dbElements['remitterlastname']= $parameters->remitterlastname;
       }
      if (isset( $parameters->remitteraddress)) {
          $dbElements['remitteraddress']= $parameters->remitteraddress;
       }
      if (isset( $parameters->remitterphone)) {
          $dbElements['remitterphone'] = $parameters->remitterphone;
       }
       
            //validate session ID
            $rmttSess = new SvcSession();
            $ssID=$parameters->session;
            $pin= $rmttSess->RestoreSession($ssID);
            $retArg['sessionResultCode']=0;

      $key= array('PaymentAccountNumber'=> $to);

      try {

      $db->dbRetrieveEleAll('MNO_DATA', '*', $key, 'MnoData', $dataBuf);

      $fcount=count($dataBuf);
      error_log("DEBUG: dbRetrieveEleAll for MNO_DATA - fcount: $fcount entry\n");

      if ($fcount>0) {
         $mnoRc = $dataBuf[0];         

        $dbElements['LastUpdate'] = date("D M j G:i:s T Y");
        $dbElements['AccessCount']= $mnoRc->AccessCount +1;

        if ($mnoRc->PassedTragetCommand=='cashincmd') $dbElements['PassFail'] = 'P'; 

        $dbElements['cashincmdLastUpdate'] = date("D M j G:i:s T Y");

         $retArg['sessionResultCode']=0;
         if (isset($mnoRc->cashincmdRC))
               $retArg['resultCode']= $mnoRc->cashincmdRC;
         else
               $retArg['resultCode']=0;

         if (isset($mnoRc->cashincmdSessionRC)) {
           $retArg['sessionResultCode']= $mnoRc->cashincmdSessionRC;
           $dbElements['cashincmdSessionRC']=NULL;

         }

         if (isset($mnoRc->cashincmdRC1)) {
             $retArg['resultCode']= $mnoRc->cashincmdRC1;      
             $dbElements['cashincmdRC1']=NULL;
         }
         else
         if (isset($mnoRc->cashincmdRC2)) {
             $retArg['resultCode']= $mnoRc->cashincmdRC2;
             $dbElements['cashincmdRC2']=NULL;
         }
         else
         if (isset($mnoRc->cashincmdRC3)) {
             $retArg['resultCode']= $mnoRc->cashincmdRC3;
             $dbElements['cashincmdRC3']=NULL;
         }
         else
         if ($mnoRc->cashincmdRetry >0) {
             $dbElements['cashincmdRetry'] = (int) $mnoRc->cashincmdRetry - 1;
             if (isset( $mnoRc->cashincmdRC)) $retArg['resultCode']= $mnoRc->cashincmdRC;
          }

        if ($retArg['resultCode']==0) {

           if ($mnoRc->IDT_INIT_BAL > $amount) {
               $mnoRc->IDT_BALANCE= $mnoRc->IDT_INIT_BAL - $amount;
               $retArg['gcash']=$amount;
               $dbElements['PaymentAmount'] = $amount;
           }
           else {
               $retArg['resultCode']= 5;
               $retArg['gcash']=0;
           }
        }

        $retArg['transactionCode']= rand(0, 20000000)*100; 

        $dbElements['cashincmdState'] = $retArg['resultCode'];
        $dbElements['IDT_BALANCE'] = $mnoRc->IDT_BALANCE;
        $dbElements['cashincmdTransactionCode'] = $retArg['transactionCode'];
        $dbElements['state']='cshin';

        $db->dbUpdateEle('MNO_DATA', $key, $dbElements);

           $rmttRspMsg->setRetArg($retArg);

           return $rmttRspMsg;

      } // handle cashincmd based on DB instructions
      else {

            $code= array();

            if (preg_match('/session/i',$subString)&&preg_match('/[0-9]{1,4}/', $subString, $code)) {

                 $retArg['sessionResultCode']= $code[0];
                 if ($code[0]>0) {
                    $rmttRspMsg->setRetArg($retArg);
                    return $rmttRspMsg;
                  }
            }


            if (preg_match('/result/i',$subString)&&preg_match('/[0-9]{1,4}/', $subString, $code)) {
                 $retArg['resultCode']= $code[0];

                 if ($code[0]>0) {
                    $rmttRspMsg->setRetArg($retArg);
                    return $rmttRspMsg;
                  }
            }
            else {
                 $retArg['transactionCode']=rand(0, 20000000)*100;
                 $retArg['resultCode']=0;
            }

            if (preg_match('/commit/i',$subString)&&preg_match('/[0-9]{1,4}/', $subString, $code)) {
                $retArg['transactionCode'] +=$code[0];
            }

            $retArg['gcash']=$amount;
            $rmttRspMsg->setRetArg($retArg);
ob_start();
var_dump($rmttRspMsg);
error_log(ob_get_contents());

            return $rmttRspMsg;
        } //cashincmd no DB handling
        } // end try
        catch (SoapFault $ex) {

error_log("$ex->faultcode, $ex->faultstring, $ex->faultactor, $ex->detail, logincmd failed. \n");
              $rmttRspMsg->SetError($ex->faultcode, $ex->faultstring);
              return $rmttRspMsg;
        }

} //cashincmd


function commitcashcmd ($parameters) {

error_log("DEBUG: Received commitcashcmd method.\n");

      global $rmttRspMsg;
      $db=new DBClass();
      $dbElements = array(); //use to store elements to be populated back to db
      $dataBuf[]=array();    //use to store elements retrieved from db
      $retArg=array();

      $ssID=$parameters->session;
      $transaction= (string) $parameters->transaction;

      $key= array('cashincmdTransactionCode'=> $transaction);

      $db->dbRetrieveEleAll('MNO_DATA', '*', $key, 'MnoData', $dataBuf);

      $fcount=count($dataBuf);
      error_log("DEBUG commitcashcmd: dbRetrieveEleAll for MNO_DATA - Retrieved $fcount entry\n");

      try {

      if ($fcount>0) {
         $mnoRc = $dataBuf[0];

         $dbElements['LastUpdate'] = date("D M j G:i:s T Y");
         $dbElements['AccessCount']= $mnoRc->AccessCount +1;

         if ($mnoRc->PassedTragetCommand=='commitcashcmd')  $dbElements['PassFail'] = 'P';

         $dbElements['commitcashcmdLastUpdate'] = date("D M j G:i:s T Y");

         $retArg['sessionResultCode']=0;
         $retArg['resultCode']=0;

         if (isset($mnoRc->commitcashcmdSessionRC)) {
             $retArg['sessionResultCode']= $mnoRc->commitcashcmdSessionRC;
             $dbElements['commitcashcmdSessionRC']=NULL;
         }
         
         if (isset($mnoRc->commitcashcmdRC1)) {
             $retArg['resultCode']= $mnoRc->commitcashcmdRC1;
             $dbElements['commitcashcmdRC1']=NULL;
         }
         else
         if (isset($mnoRc->commitcashcmdRC2)) {
             $retArg['resultCode']= $mnoRc->commitcashcmdRC2;
             $dbElements['commitcashcmdRC2']=NULL;
         }
         else
         if (isset($mnoRc->commitcashcmdRC3)) {
             $retArg['resultCode']= $mnoRc->commitcashcmdRC3;
             $dbElements['commitcashcmdRC3']=NULL;
         }
         else
         if ($mnoRc->commitcashcmdRetry >0) {
             $dbElements['commitcashcmdRetry'] = (int) $mnoRc->commitcashcmdRetry - 1;
             if (isset( $mnoRc->commitcashcmdRC)) $retArg['resultCode']= $mnoRc->commitcashcmdRC;
          }

        $retArg['transactionCode']=$transaction;

        $dbElements['commitcashcmdState'] = $retArg['resultCode'];
        $dbElements['commitcashcmdTransactionCode'] = $retArg['transactionCode'];
        $dbElements['state']='cmcsh';

        $db->dbUpdateEle('MNO_DATA', $key, $dbElements);

         $rmttRspMsg->setRetArg($retArg);
ob_start();
var_dump($rmttRspMsg);
error_log(ob_get_contents());
         return $rmttRspMsg;

      } // handle commitcashcmd based on DB instructions
      else {
            //validate session ID
            $rmttSess = new SvcSession();
            $pin= $rmttSess->RestoreSession($ssID);


            $retArg['sessionResultCode']=0;
            $retArg['resultCode']=0;
//            $retArg['resultCode']=$transaction[strlen($transaction)-2].$transaction[strlen($transaction)-1];

            if ($retArg['resultCode'] == 0)
                 $retArg['transactionCode']=$transaction;

            $rmttRspMsg->setRetArg($retArg);

ob_start();
var_dump($rmttRspMsg);
error_log(ob_get_contents());

            return $rmttRspMsg;
        } // handle commitcashcmd by default
        } // try block
        catch (SoapFault $ex) {

error_log("$ex->faultcode, $ex->faultstring, $ex->faultactor, $ex->detail, logincmd failed. \n");
              $rmttRspMsg->SetError($ex->faultcode, $ex->faultstring);
              return $rmttRspMsg;
        }

} //commitcashcmd


function balancecmd ($parameters) {

error_log("DEBUG: Received balancecmd method.\n");

      global $rmttRspMsg;

      $db=new DBClass();
      $dbElements = array(); //use to store elements to be populated back to db
      $dataBuf[]=array();    //use to store elements retrieved from db
      $retArg=array();

      $ssID=$parameters->session;

       try {
            //validate session ID
            $rmttSess = new SvcSession();
            $pin= $rmttSess->RestoreSession($ssID);


            $retArg['sessionResultCode']=0;
            $retArg['resultCode']=0;

            if ($retArg['resultCode'] == 0)
                 $retArg['transactionCode']=0;

            if ($retArg['transactionCode'] == 0)
                 $retArg['balance'] = '1000';

            $rmttRspMsg->setRetArg($retArg);
            return $rmttRspMsg;
        }
        catch (SoapFault $ex) {

error_log("$ex->faultcode, $ex->faultstring, $ex->faultactor, $ex->detail, logincmd failed. \n");
              return $rmttRspMsg;
        }

} //balancecmd



function rollbackcashcmd($parameters) 
{

error_log("DEBUG: Received rollbackcashcmd method.\n");

      global $rmttRspMsg;

      $retArg=array();

      $ssID=$parameters->session;
      $transaction=$parameters->transaction;

       try {
            //validate session ID
            $rmttSess = new SvcSession();
            $pin= $rmttSess->RestoreSession($ssID);


            $retArg['sessionResultCode']=0;
            $retArg['resultCode']=0;
            $retArg['transactionCode']=$transaction;

            $rmttRspMsg->setRetArg($retArg);
            return $rmttRspMsg;
        }
        catch (SoapFault $ex) {

error_log("$ex->faultcode, $ex->faultstring, $ex->faultactor, $ex->detail, logincmd failed. \n");
              $rmttRspMsg->SetError($ex->faultcode, $ex->faultstring);
              return $rmttRspMsg;
        }

} //rollbackcashcmd


} //GlobeMsgHandler

error_log("Dumping HTTP_RAW_POST_DATA: $HTTP_RAW_POST_DATA\n");
$data= file_get_contents('php://input');
error_log("INPUT stream: $data\n\n");

$server = new SoapServer("GlobeGCash.wsdl");
$server->setClass("GlobeMsgHandler");

//$allFunctions = $server->getFunctions();
//error_log( implode(",",$allFunctions));

$server->handle($HTTP_RAW_POST_DATA);
?>
