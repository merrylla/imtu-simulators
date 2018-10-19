<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__));

include '../ezetop/ImtuTopUpClass.php';
include '../QAlib/wslib1.php';

$wsdl = "http://devcall02.ixtelecom.com/wsdl//TCMerchantService_1.wsdl"; 

function s_rand($numDigits) {
    $chars = '0123456789abcdef';
    $randStr = "";
    for ($i=0; $i<$numDigits; $i++) {
        $randInt = rand(0, 15);
        $char1 = $chars[$randInt];
        if ($i > 0 && $i % 4 == 0) {
          $randStr .= '-';
        }
        $randStr .= $char1;
   }
   return $randStr;
}

class TCetra {
  function EchoString($request) {
    errlog("EchoString request =", $request); 
    $response = array('EchoResult' => $request->echoString); 
    errlog("EchoString response = ", $response);
    return $response;
  }

  function CheckTransactionStatus($request) {
    errlog("CheckTransactionStatus request =", $request);
    $req = $request->request;
    $refID = $req->ProviderRefID;
    $dlayKey = array('app' => 'TCetra', 'key' => $refID);
    $xA = array('a0' => $phone,
			'a1' => $refID,
			'a2' => $prodID);
    $xA=array('phoneNumber' => 'a0',
              'refID' => 'a1',
               'prodID' => 'a2');
    $rA=array(
              'refID' => 'a1',
               'prodID' => 'a2');
    $topObj = new TopupObj($dlayKey, $xA, $rA);
    $phone = $topObj->xe[phoneNumber];
    errlog("CheckTransactionStatus phone =", $phone);
    $statKey = array('app' => 'TCetra', 'key' => $phone);
    $xA=array('phoneNumber' => 'a0',
              'refID' => 'a1',
               'prodID' => 'a2',
	       'ErrorMessage' => 'a3',
	       'ErrorCode' => 'a4',
		'topUpAmount' => 'a5',
                'delayAmt' => 'delay',
                'abortVal' => 'abort');
 
    $rA=array(
              'refID' => 'a1',
               'prodID' => 'a2',
	       'ErrorMessage' => 'a3',
	       'ErrorCode' => 'a4',
		'topUpAmount' => 'a5',
                'delayAmt' => 'delay',
                'abortVal' => 'abort');
    $topObj = new TopupObj($statKey, $xA, $rA);
    $delayAmt = $topObj->xe[delayAmt];
    $abortVal = $topObj->xe[abortVal];
    errlog("CheckTransactionStatus delay =", $delayAmt);
    errlog("CheckTransactionStatus abort =", $abortVal);
    if (($abortVal == "false") && ($delayAmt < 60))
    $response = array('CheckTransactionStatusResult' => 
		      array('Error' => array('ErrorCode' => '0', 'ErrorMessage' => 'OK'),
			    'LoadingInstructions' => 'TBD',
			    'OrderNo' => '1000',
			    'OrderStatus' => 'OK',
			    'SerialNumbers' => array(s_rand(16)))
                      );
    errlog("CheckTransactionStatus response=", $response);
    return $response;
  }

  function ExpressBuy($request) {
    errlog("ExpressBuy request =", $request);
    $req = $request->request;
    $addOnList = $req->AddonList;
    $servMod = $addOnList->AddonServiceModel;
    $apiKey = $addOnList->ApiKey;
    $prodID = $req->ProductID;
    $refID = $req->ProviderRefID;
    $quant = $req->Quantity;
    $name = $servMod[0]->AddonName;
    errlog("ExpressBuy prod =", $prodID);
    errlog("ExpressBuy refID=", $refID);
    $val = $servMod[0]->AddonValue;
    errlog("ExpressBuy value =", $val);
    $name2 = $servMod[1]->AddonName;
    $phone = $servMod[1]->AddonValue; 
    $xA=array('phoneNumber' => 'a0',
              'refID' => 'a1',
               'prodID' => 'a2',
	       'ErrorMessage' => 'a3',
	       'ErrorCode' => 'a4',
		'topUpAmount' => 'a5');
    $statKey = array('app' => 'TCetra', 'key' => $phone);
    $dlayKey = array('app' => 'TCetra', 'key' => $refID);
    errlog("ExpressBuy name2 =", $name2);
    errlog("ExpressBuy value =", $phone);
    $rA=array(
              'refID' => 'a1',
               'prodID' => 'a2',
	       'ErrorMessage' => 'a3',
	       'ErrorCode' => 'a4',
		'topUpAmount' => 'a5');
    $addedInfo = array('a0' => $phone,
			'a1' => $refID,
			'a2' => $prodID);
    updateTransDBRec($phone, $addedInfo, 'TCetra');
    insertTransDB($dlayKey, $addedInfo);
    $statTopObj = new TopupObj($statKey, $xA, $rA);
    errlog("Inside TCetra.ExpressBuy: TopUpObj=", $statTopObj);
    if ($statTopObj->re[ErrorCode]) {
        $error = $statTopObj->re[ErrorCode];
        $errMsg = $statTopObj->re[ErrorMessage];
        errlog("DoTopUp set errMsg & errCode= $error", $errMsg);
    } else {
        $error = 0;
        $errMsg = $statTopObj->re[ErrorMessage];
    }
    errlog("Inside TCetra.ExpressBuy before setting massagedInput: errMsg=", $errMsg);
    $massagedInput = array('phoneNumber' => $phone,
			'refID' => $refID,
			'prodID' => $prodID,
			'ErrorMessage' => $errMsg,
			'ErrorCode' => $error,
			'topUpAmount' => $val);
    errlog ("INFO TCetra.ExpressBuy - before validate topup result:" , $statTopObj->re);
    $statTopObj->validateReqInput($massagedInput);
    errlog ("INFO TCetra.ExpressBuy - topup result:" , $statTopObj->re);
    $raD = array();
    $statTopObj->buildRspData($raD);
    errlog ("INFO TCetra.ExpressBuy -did delay if needed",$statTopObj->re);
    $instructions = <<<EOT
&lt;P class=MsoNormal style="MARGIN: 0in 0in 0pt; LINE-HEIGHT: normal"&gt;&lt;SPAN style='FONT-SIZE: 10.5pt; FONT-FAMILY: "Open Sans","sans-serif"; COLOR: black'&gt;To redeem your pin number to an account follow these steps:&lt;?xml:namespace prefix = "o" ns = "urn:schemas-microsoft-com:office:office" /&gt;&lt;o:p&gt;&lt;/o:p&gt;&lt;/SPAN&gt;&lt;/P&gt;&#xD;&lt;P class=MsoListParagraphCxSpFirst style="MARGIN: 0in 0in 0pt 0.5in; LINE-HEIGHT: normal; TEXT-INDENT: -0.25in; mso-add-space: auto; mso-list: l0 level1 lfo1"&gt;&lt;SPAN style="FONT-SIZE: 10.5pt; FONT-FAMILY: Symbol; COLOR: black; mso-fareast-font-family: Symbol; mso-bidi-font-family: Symbol"&gt;&lt;SPAN style="mso-list: Ignore"&gt;·&lt;SPAN style='FONT: 7pt "Times New Roman"'&gt;&amp;nbsp;&amp;nbsp;&amp;nbsp;&amp;nbsp;&amp;nbsp;&amp;nbsp;&amp;nbsp;&amp;nbsp; &lt;/SPAN&gt;&lt;/SPAN&gt;&lt;/SPAN&gt;&lt;SPAN style='FONT-SIZE: 10.5pt; FONT-FAMILY: "Open Sans","sans-serif"; COLOR: black'&gt;Please dial &lt;SPAN class=yshortcuts&gt;1-877-836-2368&lt;/SPAN&gt; and follow the voice prompts&lt;o:p&gt;&lt;/o:p&gt;&lt;/SPAN&gt;&lt;/P&gt;&#xD;&lt;P class=MsoListParagraphCxSpLast style="MARGIN: 0in 0in 0pt 0.5in; LINE-HEIGHT: normal; mso-add-space: auto; tab-stops: 261.5pt"&gt;&lt;SPAN style='FONT-SIZE: 10.5pt; FONT-FAMILY: "Open Sans","sans-serif"; COLOR: black'&gt;&lt;BR&gt;&lt;U&gt;Customer Service: &lt;SPAN class=yshortcuts&gt;&lt;B style="mso-bidi-font-weight: normal"&gt;1-877-836-2368&lt;/B&gt;&lt;/SPAN&gt;&lt;/U&gt;&lt;/SPAN&gt;&lt;SPAN style='FONT-SIZE: 10.5pt; FONT-FAMILY: "Open Sans","sans-serif"'&gt;&lt;o:p&gt;&lt;/o:p&gt;&lt;/SPAN&gt;&lt;/P&gt;
EOT;

    $response = array('ExpressBuyResult' =>
		      array('Error' => array('ErrorCode' => $error, 'ErrorMessage' => $errMsg),
			    'LoadingInstructions' => $instructions,
			    'OrderNo' => getmypid(),
			    'OrderStatus' => 'OK',
			    'SerialNumbers' => array(s_rand(16)))
		      );
    errlog("ExpressBuy response=", $response);
    return $response;
  } // end of ExpressBuy

  function Refund($request) {
    errlog("Refund request =", $request);
    $response = array('RefundResult' =>
		      array('Error' => array('ErrorCode' => '0', 'ErrorMessage' => 'OK'),
			    'OrderNo' => '1000')
                      );
    errlog("Refund response=", $response);
    return $response;
  }

  function GetProductList($request) {
    errlog("GetProductAddons request =", $request);
    $response = array('GetProductListResult' => 
		      array('CategoryList' => array('CategoryServiceModel' => array('CategoryName' => 'CategoryA'),
array('ProductList' => 
array('ProductServiceModel' => array('BasePrice' => 1,
  'Discount' => 1,
  'DiscountType' => 'final',
  'HasAddons' => 'false',
  'ProductID' => 3,
  'ProductName' => 'HardCard',
  'ProductType' => 'Service'))))),
			    'Error' => array('ErrorCode' => '0', 'ErrorMessage' => ''));
    errlog("GetProductList responese=", $response);

    return $response;
  }
 
  function GetProductAddons($request) {
    errlog("GetProductAddons request =", $request);
    $response = array('GetProductAddonsResult' => 
		      array('AddonList' => array('AddonServiceModel' => array(
									      array('AddonID' => 8,
										    'AddonName' => 'Phone_Number',
										    'AddonType' => 'PhoneNumberType',
										    'Required' => true),
									      array('AddonID' => 4,
										    'AddonName' => 'Amount',
										    'AddonType' => 'AmountType',
										    'Required' => true)
									      )
						 ),
			    'Error' => array('ErrorCode' => '0', 'ErrorMessage' => 'OK')
			    )
		      );
    errlog("GetProductAddons responese=", $response);

    return $response;
  }
} // end class TCetra      


class TCetraProxy
{
  public function __call($methodName, $args) {
    errlog("__call methodName ", $methodName);
    errlog("__call args", $args);
    if ($methodName == "Echo") $methodName = "EchoString";

    $tcetra = new TCetra();


    $result = call_user_func_array(array($tcetra, $methodName),  $args);

    errlog("__call result", $result);
    return $result;
  }
} // end of class

errlog("Main", $HTTP_RAW_POST_DATA);
$server = new SoapServer($wsdl, 
                         array(
				'uri' => "http://tempuri.org/",
                               'soap_version' => SOAP_1_2,
				'trace' => 1));

$server->setClass("TCetraProxy"); 
$server->handle(); 
?>
