<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));

#include './overLoadedSoapServer.php';
include '../QAlib/wslib1.php';
include '../ezetop/ImtuTopUpClass.php';

$wsdl="http://devcall02.ixtelecom.com/Prasan/Prasan.wsdl";

function i_rand($min=0,$max=1000){
/* This random number generator is used to find a pin */
    if ($min>$max) return false;
    $rand= mt_rand($min,$max);
errlog("Inside Prasan.i_rand()= ",$rand);
    return $rand;
}  

function s_rand($numDigits) {
    $chars = '0123456789';
    $randStr = "";
    for ($i=0; $i<$numDigits; $i++) {
        $randInt = rand(0, 9);
        $char1 = $chars[$randInt];
        $randStr .= $char1;
   }
   return $randStr;
} 

function setRespCode ($username, $checkString, $checkSum) {
   $checkSha1 = sha1($checkString);
   errlog("Inside Prasan.setRespCode",$checkString);
   errlog("Inside Prasan.sha1",$checkSha1);
   $checkKey = md5($checkSha1);
   errlog("Inside Prasan.md5 ",$checkKey);
   $rspCode = '000';
   if ($username != 'IDT') {
      $rspCode = 001;
   } else if ($checkKey != $checkSum) {
      $rspCode = '005';
   }
   return $rspCode;
}

function setRespDesc ($rspCode) {
  $desc='Fail';
  switch ($rspCode) {
    case '000':
      $desc = 'Successful';
      break;
    case '001':
      $desc = 'Invalid Login';
      break;
    case '002':
      $desc = 'Login Inactive';
      break;
    case '003':
      $desc = 'Login Deleted';
      break;
    case '004':
      $desc = 'Credit Limit Exceed';
      break;
    case '005':
      $desc = 'Checksum Invalid';
      break;
    case '011':
      $desc = 'Batch Invalid';
      break;
    case '012':
      $desc = 'Batch Inactive';
      break;
    case '013':
      $desc = 'Batch Deleted';
      break;
    case '014':
      $desc = 'Amount Invalid';
      break;
    case '015':
      $desc = 'Product Inactive';
      break;
    case '016':
      $desc = 'Product Deleted';
      break;
    case '017':
      $desc = 'Invalid System Service ID';
      break;
    case '021':
      $desc = 'Mobile Number not valid';
      break;
    case '031':
      $desc = 'Provider side data invalid';
      break;
    case '032':
      $desc = 'Mobile Number not supported by provider';
      break;
    case '033':
      $desc = 'Provider Side Denomination Type Invalid';
      break;
    case '041':
      $desc = 'Provider Side Authentication Fail';
      break;
    case '042':
      $desc = 'Provider Side Platform Error';
      break;
    case '043':
      $desc = 'Provider Side Invalid Request Format';
      break;
    case '044':
      $desc = 'Provider Side Credit Limit Exceeded';
      break;
    case '045':
      $desc = 'Provider Side Invalid Subscriber Number';
      break;
    case '046':
      $desc = 'Provider Side Transaction Duplicate';
      break;
    case '047':
      $desc = 'Denomination not support by this provider';
      break;
    case '048':
      $desc = 'Provider Side Amount Format Invalid';
      break;
    case '049':
      $desc = 'Provider Side Response Time-Out';
      break;
    case '050':
      $desc = 'Provider Side Number not Prepaid';
      break;
    case '051':
      $desc = 'Top-Up is Not Supported';
      break;
    case '052':
      $desc = 'Provider Side Invalid Currency';
      break;
    case '082':
      $desc = 'Provider Transaction ID Invalid';
      break;
    case '084':
      $desc = 'Transaction Request ID not valid';
      break;
    case '091':
      $desc = 'Connection Error';
      break;
    case '092':
      $desc = 'Response Timeout';
      break;
    case '093':
      $desc = 'Provider Authentication Fail';
      break;
    case '100':
      $desc = 'Fail';
      break;
    case '101':
      $desc = 'Database Error';
      break;
    case '102':
      $desc = 'Invalid Argument';
      break;
    default:
      $desc = 'Fail';
    } // end switch
    //errlog("Inside Prasan setRespDesc desc= ",$desc);
    return $desc;
} // end setRespDesc 
    
class SoapHandler {

          var $pubKey = 'o53ixg2ca'; // from wallet password
        //function viva_topup_Recharge($p)
        function EchoCheck($p)
        {
          errlog("Inside Prasan EchoCheck.p= ",$p);
	  $username=$p->LoginId;
	  $msg =$p->Message;
          $checkString = $username . '|' . $msg . '|' . $this->pubKey;
	  $rspCode = setRespCode($username, $checkString, $p->Checksum);
	  $respDesc = setRespDesc($rspCode);
          $rsp = array('ResponseCode' => $rspCode,
			'ResponseDescription' => $respDesc,
			'Message' => $msg);
	  return $rsp;
	}

	function ResellerBalance($p)
	{
	  errlog("Inside Prasan.ResellerBalance p= ",$p);
	  $username=$p->LoginId;
	  $expDate=$p->TillDate;
	  $chkSum=$p->Checksum;
          $checkString = $username . '|' . $expDate. '|' . $this->pubKey;
	  errlog("Inside Prasan.ResellerBalance checkString ",$checkString);
	  $rspCode = setRespCode($username, $checkString, $p->Checksum);
	  errlog("Inside Prasan.ResellerBalance rspCode ",$rspCode);
	  $respDesc = setRespDesc($rspCode);
	  errlog("Inside Prasan.ResellerBalance respDesc ",$respDesc);
          $rsp = array('ResponseCode' => $rspCode,
			'ResponseDescription' => $respDesc, 
			'TillDateBalance' => date("d-m-Y H:i:s"),
                        'CurrentBalance' => 2000);
	  return $rsp;
	}

	function FlexiTransactionDetail($p)
        /*  This seems to be the getStatus function that we need.  
         *  This one is for open range products and the next is for fixed
         *  price products. */
	{
	  errlog("Inside Prasan.FlexiTransactionDetail p= ",$p);
	  $username=$p->LoginId;
	  $req=$p->RequestId;
	  $chkSum=$p->Checksum;
          $checkString = $username . '|' . $req . '|' . $this->pubKey;
	  $rspCode = setRespCode($username, $checkString, $p->Checksum);
	  $respDesc = setRespDesc($rspCode);
          $xA=array('phoneNumber' => 'a0',
                      'batch' => 'a1',
		      'topUpAmount' => 'a2',
		      'transCode' => 'a3',
		      'transRsp' => 'a4');
	  $key = array('app' => 'Prasan', 'key' => $req);
          $rA=array('confCode' => 'a3',
		    'auditNum' => 'a4',
		    'providerResp' => 'a5');
          $tpObj = new TopupObj($key, $xA, $rA);
	  if (isset ($tpObj->xe['phoneNumber'])) {
		$rsp = array('ResponseCode' => $rspCode,
			'ResponseDescription' => $respDesc,
  // My guess is that this is the resp code from the FlexiRecharge 
			'TransactionResponseCode' => $tpObj->xe['transCode'], 
			'TransactionResponseDescription' => 
				$tpObj->xe['transRsp'],
			'ConfirmationCode' => $tpObj->xe['confCode'],
			'AuditNo' => $tpObj->xe['auditNum'],
			'TopUpAmount' => $tpObj->xe['topUpAmount']);
                 return $rsp;
          } else {
		$rspCode = 101;
		$rspDesc = 'Database Error';
		$rsp = array('ResponseCode' => $rspCode,
			'ResponseDescription' => $respDesc,
			'TransactionResponseCode' => '102', 
			'TransactionResponseDescription' => 
				'Invalid Argument',
			'ConfirmationCode' => '100',
			'AuditNo' => '00',
			'TopUpAmount' => '0.00');
          }
        }

	function FixTransactionDetail($p)
        /*  This is the getStatus function for fixed range products */
	{
	  errlog("Inside Prasan.FixTransactionDetail p= ",$p);
	  $username=$p->LoginId;
	  $req=$p->RequestId;
	  $chkSum=$p->Checksum;
          $checkString = $username . '|' . $req . '|' . $this->pubKey;
	  $rspCode = setRespCode($username, $checkString, $p->Checksum);
	  $respDesc = setRespDesc($rspCode);
          $xA=array('phoneNumber' => 'a0',
                      'batch' => 'a1',
		      'topUpAmount' => 'a2',
		      'transCode' => 'a3',
		      'transRsp' => 'a4');
	  $key = array('app' => 'Prasan', 'key' => $req);
          $rA=array('confCode' => 'a3',
		    'auditNum' => 'a4',
		    'providerResp' => 'a5');
          $tpObj = new TopupObj($key, $xA, $rA);
	  if (isset ($tpObj->xe['phoneNumber'])) {
		$rsp = array('ResponseCode' => $rspCode,
			'ResponseDescription' => $respDesc,
  // My guess is that this is the resp code from the FixRecharge 
			'TransactionResponseCode' => $tpObj->xe['transCode'], 
			'TransactionResponseDescription' => 
				$tpObj->xe['transRsp'],
			'ConfirmationCode' => $tpObj->xe['confCode'],
			'AuditNo' => $tpObj->xe['auditNum'],
			'TopUpAmount' => $tpObj->xe['topUpAmount']);
                 return $rsp;
          } else {
		$rspCode = 101;
		$rspDesc = 'Database Error';
		$rsp = array('ResponseCode' => $rspCode,
			'ResponseDescription' => $respDesc,
			'TransactionResponseCode' => '102', 
			'TransactionResponseDescription' => 
				'Invalid Argument',
			'ConfirmationCode' => '100',
			'AuditNo' => '00',
			'TopUpAmount' => '0.00');
                 return $rsp;
            }
        }

	function FlexiRecharge($p)
        /*  This is the topUp function for open  range products */
	{
	  errlog("Inside Prasan.FlexiRecharge p= ",$p);
	  $username=$p->LoginId;
	  $req=$p->RequestId;
	  $batch=$p->BatchId;
	  $sysSrv=$p->SystemServiceID;
	  $topUpNum=$p->ReferalNumber;
	  $amt=$p->Amount;
	  $blank=$p->FromANI;
	  $email=$p->Email;
	  $chkSum=$p->Checksum;
          $checkString = $username . '|' . $req . '|' . $batch . '|';
          $checkString .= $sysSrv . '|' . $topUpNum . '|' . $amt . '|||';
          $checkString .= $this->pubKey;
	  errlog("Inside Prasan.FlexiRecharge checkString ",$checkString);
	  $rspCode = setRespCode($username, $checkString, $p->Checksum);
	  $confirmationCode = i_rand(0, 10000);
	  $auditNo = i_rand(0, 10000);
	  $respDesc = setRespDesc($rspCode);
	  $provRsp = i_rand(0, 1000);
          $xA=array('phoneNumber' => 'a0',
                      'batch' => 'a1',
		      'topUpAmount' => 'a2',
		      'transactionCode' => 'a3',
		      'transactionResponse' => 'a4');
	  $statKey = array('app' => 'Prasan', 'key' => $req);
	  $delayKey = array('app' => 'Prasan', 'key' => $topUpNum);
          $rA=array('confCode' => 'a5',
		    'auditNum' => 'a6',
		    'providerResp' => 'a7');
	  $delayTopObj = new TopupObj($delayKey, $xA, $rA);
	  $statTopObj = new TopupObj($statKey, $xA, $rA);
          errlog("Inside Prasan.FlexiRecharge  TopUp",$statTopObj);
          errlog("Inside Prasan.FlexiRecharge  delay TopUp",$delayTopObj);
	  $massagedInput = array('phoneNumber' => $topUpNum,
				'batch' => $batch,
				'topUpAmount' => $amt,
				'transactionCode' => $rspCode,
				'transactionResponse' => $respDesc,
				'confCode' => $confirmationCode,
				'auditNum' => $auditNo,
				'providerResp' => $provRsp);
          $xArr = array ('a0' =>  $topUpNum,
			'a1' => $batch,
			'a2' => $amt,
			'a3' => $rspCode,
			'a4' => $respDesc,
			'a5' => $confirmationCode,
			'a6' => $auditNo,
			'a7' => $provRsp);
	  insertTransDB($statKey, $xArr);
	  $statTopObj->validateReqInput($massagedInput);
	  errlog ("INFO FlexiRecharge - topup result:" , $statTopObj->re);
	  $raD = array();
	  if ($delayTopObj->isAbort() == 'false') {
	     $delayTopObj->buildRspData($raD);
             errlog("Inside Prasan.FlexiRecharge did delay if needed",$delayTopObj->re);
             if ((sizeof($delayTopObj->xe)> 0) && 
	         ($rspCode == '000')) {
                $rspCode = $delayTopObj->xe['phoneNumber'];
	        $respDesc = setRespDesc($rspCode);
                }
             $rsp = array('ResponseCode' => $rspCode,
			'ResponseDescription' => $respDesc,
			'ConfirmationCode' => $confirmationCode,
			'AuditNo' => $auditNo, 
			'TopUpAmount' => $amt,
			'ProviderResponse' => $provRsp);
             errlog("Inside Prasan.FlexiRecharge rsp = ",$rsp);
             return $rsp;
          } // took delay into account  
	} // FlexiRecharge end 
	
	function FixRecharge($p)
        /*  This is the topUp function for fixed price products */
	{
	  errlog("Inside Prasan.FixRecharge p= ",$p);
	  $username=$p->LoginId;
	  $req=$p->RequestId;
	  $batch=$p->BatchId;
	  $sysSrv=$p->SystemServiceID;
	  $topUpNum=$p->ReferalNumber;
	  $amt=$p->Amount;
	  $blank=$p->FromANI;
	  $email=$p->Email;
	  $chkSum=$p->Checksum;
          $checkString = $username . '|' . $req . '|' . $batch . '|' . $sysSrv;
          $checkString .= $topUpNum . '|' . $amt . '|||' . $this->pubKey;
	  $rspCode = setRespCode($username, $checkString, $p->Checksum);
	  $confirmationCode = i_rand(0, 10000);
	  $auditNo = i_rand(0, 10000);
	  $respDesc = setRespDesc($rspCode);
	  $provRsp = i_rand(0, 1000);
          $xA=array('phoneNumber' => 'a0',
                      'batch' => 'a1',
		      'topUpAmount' => 'a2',
		      'transactionCode' => 'a3',
		      'transactionResponse' => 'a4'
                        );
	  $statKey = array('app' => 'Prasan', 'key' => $req);
	  $delayKey = array('app' => 'Prasan', 'key' => $topUpNum);
          $rA=array('confCode' => 'a5',
		    'auditNum' => 'a6',
		    'providerResp' => 'a7');
	  $delayTopObj = new TopupObj($delayKey, $xA, $rA);
          $statTopObj = new TopupObj($statKey, $xA, $rA);
	  errlog("Inside Prasan.FixRecharge  TopUp",$statTopObj);
	  $massagedInput = array('phoneNumber' => $topUpNum,
				'batch' => $batch,
				'topUpAmount' => $amt,
				'transactionCode' => $rspCode,
				'transactionResponse' => $respDesc,
				'confCode' => $confirmationCode,
				'auditNum' => $auditNo,
				'providerResp' => $provRsp);
          $xArr = array ('a0' =>  $topUpNum,
			'a1' => $batch,
			'a2' => $amt,
			'a3' => $rspCode,
			'a4' => $respDesc,
			'a5' => $confirmationCode,
			'a6' => $auditNo,
			'a7' => $provRsp);
	  insertTransDB($statKey, $xArr);
	  $statTopObj->validateReqInput($massagedInput);
          errlog("Inside Prasan.FixRecharge did validate topup result:" , 
							$statTopObj->re);
	  $raD = array();
	  if ($delayTopObj->isAbort() == 'false') {
	     $delayTopObj->buildRspData($raD);
             errlog("Inside Prasan.FixRecharge did delay if needed",$delayTopObj->re);
             $rsp = array('ResponseCode' => $rspCode,
			'ResponseDescription' => $respDesc,
			'ConfirmationCode' => $confirmationCode,
			'AuditNo' => $auditNo, 
			'TopUpAmount' => $amt,
			'ProviderResponse' => $provRsp);
             errlog("Inside Prasan.FixRecharge rsp = ",$rsp);
             return $rsp;
          } // took delay into account  
	} // FixRecharge end 
	
	function FlexiVoid($p)
       /*  This function is never called as Prasan does not really want to 
           support reversals.  Who knows.  It might be used eventually.
           This is the cancel topUp function for fixed price products */ 
	{
	  errlog("Inside Prasan.FlexiVoid p= ",$p);
	  $username=$p->LoginId;
	  $req=$p->RequestId;
	  $chkSum=$p->Checksum;
          $checkString = $username . '|' . $req . '|' . $this->pubKey;
          $xA=array('phoneNumber' => 'a0',
                      'batch' => 'a1',
		      'topUpAmount' => 'a2',
		      'transCode' => 'a3',
		      'transRsp' => 'a4');
	  $key = array('app' => 'Prasan', 'key' => $req);
          $rA=array('confCode' => 'a5',
		    'auditNum' => 'a6',
		    'providerResp' => 'a7');
          $tpObj = new TopupObj($key, $xA, $rA);
	  $confirmationCode = 'None';;
	  $auditNum = 'None';;
	  if (isset ($tpObj->xe['phoneNumber'])) {
             if ($tpObj->xe['topUpAmount'] == 0) {
                $rspCode = '046';
             } else {  
	       $rspCode = setRespCode($username, $checkString, $p->Checksum);
             }
             if ($rspCode = '000') {
	        $confirmationCode = i_rand(0, 10000);
	        $auditNo = i_rand(0, 10000);
                $xANew=array('a0' => $tpObj->xe['phoneNumber'],
			'a1' => $tpObj->xe['batch'],
			'a2' => 0,
			'a3' => $tpObj->xe['transCode'],
			'a4' => $tpObj->xe['transRsp'],
			'a5' => $tpObj->xe['confCode'],
			'a6' => $tpObj->xe['auditNum'],
			'a7' => $tpObj->xe['providerResp']);
	        updateTransDBRec($req, $xANew, 'Prasan');
	      }	
	   } else {
              // record not found in DB	
                $rspCode = '041';
           }
	   $respDesc = setRespDesc($rspCode);
	   $rsp = array('ResponseCode' => $rspCode,
			'ResponseDescription' => $respDesc,
  // My guess is that this is the resp code from the FixRecharge 
			'TransactionResponseCode' => $tpObj->xe['transCode'], 
			'TransactionResponseDescription' => 
				$tpObj->xe['transRsp'],
			'ConfirmationCode' => $confirmationCode, 
			'AuditNo' => $auditNum,
			'TopUpAmount' => $tpObj->xe['topUpAmount']);
        return $rsp;
        } //FlexiVoid

	function FixVoid($p)
       /*  This function is never called as Prasan does not really want to 
           support reversals.  Who knows.  It might be used eventually.
           This is the cancel topUp function for fixed price products */ 
	{
	  errlog("Inside Prasan.FixVoid p= ",$p);
	  $username=$p->LoginId;
	  $req=$p->RequestId;
	  $chkSum=$p->Checksum;
          $checkString = $username . '|' . $req . '|' . $this->pubKey;
          $xA=array('phoneNumber' => 'a0',
                      'batch' => 'a1',
		      'topUpAmount' => 'a2',
		      'transCode' => 'a3',
		      'transRsp' => 'a4');
	  $key = array('app' => 'Prasan', 'key' => $req);
          $rA=array('confCode' => 'a5',
		    'auditNum' => 'a6',
		    'providerResp' => 'a7');
          $tpObj = new TopupObj($key, $xA, $rA);
	  $confirmationCode = 'None';;
	  $auditNum = 'None';;
	  if (isset ($tpObj->xe['phoneNumber'])) {
             if ($tpObj->xe['topUpAmount'] == 0) {
                $rspCode = '046';
             } else {  
	       $rspCode = setRespCode($username, $checkString, $p->Checksum);
             }
             if ($rspCode = '000') {
	        $confirmationCode = i_rand(0, 10000);
	        $auditNo = i_rand(0, 10000);
                $xANew=array('a0' => $tpObj->xe['phoneNumber'],
			'a1' => $tpObj->xe['batch'],
			'a2' => 0,
			'a3' => $tpObj->xe['transCode'],
			'a4' => $tpObj->xe['transRsp'],
			'a5' => $tpObj->xe['confCode'],
			'a6' => $tpObj->xe['auditNum'],
			'a7' => $tpObj->xe['providerResp']);
	        updateTransDBRec($req, $xANew, 'Prasan');
	      }	
	   } else {
              // record not found in DB	
                $rspCode = '041';
           }
	   $respDesc = setRespDesc($rspCode);
	   $rsp = array('ResponseCode' => $rspCode,
			'ResponseDescription' => $respDesc,
  // My guess is that this is the resp code from the FixRecharge 
			'TransactionResponseCode' => $tpObj->xe['transCode'], 
			'TransactionResponseDescription' => 
				$tpObj->xe['transRsp'],
			'ConfirmationCode' => $confirmationCode, 
			'AuditNo' => $auditNum,
			'TopUpAmount' => $tpObj->xe['topUpAmount']);
        return $rsp;
        } //FixVoid
} //SoapHandler

errlog('Main', $HTTP_RAW_POST_DATA);

//
// notice $server is started differently here...
//
$server = new SoapServer($wsdl, 
                array('uri' => "http://www.simxpins.com/WebServices/",
		      'soap_version' => SOAP_1_2,
		      'trace' => 1));

#$server = new SoapServer(null, array('uri' =>$wsdl));
$server->setClass("SoapHandler");
$server->handle();

?>
