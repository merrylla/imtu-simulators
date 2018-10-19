<?php

$fn=preg_replace('/\.php/', '', basename(__FILE__));

#include './overLoadedSoapServer.php';
include './ImtuTopUpClass.php';
include '../QAlib/wslib1.php';

$wsdl="http://devcall02.ixtelecom.com/SIMXpress/SIMExpressR17.wsdl";

function i_rand($min=0,$max=1000){
/* This random number generator is used to find a pin */
    if ($min>$max) return false;
    $rand= mt_rand($min,$max);
errlog("Inside SIMXpress.getPins i_rand()= ",$rand);
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
#
##################################################
#
class SoapHandler {
 
	//function viva_topup_Recharge($p)
	function getBalance($p)
	{
		errlog("Inside SIMXpress.GetBalance p= ",$p);
                //$bal = i_rand(0, 20000) + i_rand(0,100)/100;
		//errlog("Inside SIMXpress.GetBalance bal = ",$bal);
                $rsp = array('getBalanceResult' => "98765402.13");
		return $rsp;
	}

	function getPins($p)
	{
          errlog("Inside SIMXpress.getPins p= ", $p);
	  $username=$p->username;
	  $password=$p->password;
	  $ProductID=$p->ProductInformation->ProductID;
     // intlTopUp will only ask for 1 max
          $serial = s_rand(8); //"559576-0001-3113';
          $pin = s_rand(10);
          $noArg = array();
          $rsp=array('productSerial' => $serial,
		'productPin' => $pin);
          errlog("Inside SIMXpress.getPins rsp = ",$rsp);
          $xA=array('phoneNumber' => 'a2',
                      'countryCode' => 'a3',
                      'amount' => 'a4',
                      'accepted' => 'a5',
                      'currencyCode' => 'a6',
                      'transactionId' => $serial,
		      'topUpAmount' => 'a7',
		      'isAmtActual' => 'a8',
		      'message' => 'a9'
                        );
          
          $rA=array('productSerial' => $serial,
		    'productPin' => $pin,
		    'code' => 'a10'
                       );

          $rA1=array('productSerial' => $serial,
                       'productPin' => $pin,
                       'code' => 0);
	  $key = array('app' => 'SIMXPRESS', 'key' => '%');
	  $tpO= new TopupObj($key, $xA, $rA);
          errlog("Inside SIMXpress.getPins did TopUp",$rsp);
	  //$tpO->validateReqInput($p);
          errlog("Inside SIMXpress.getPins did validate",$rsp);
	  $rsp=$tpO->buildRspDatA($rA1);
          $msg = "";
	  errlog('topupRequest', $rsp);
          if ($rsp['code'] != 0) {
             switch ($rsp['code']) {
             case -6102 : $msg = "The specified User Login ID does not exist";
                          errlog("Found error code $msg",$rsp['code']);
                          break;
             case -6105 : $msg = "The specified Login Password is incorrect";
                          errlog("Found error code $msg",$rsp['code']);
                          break;
	     case -6107 : $msg = "The specified User Login account is disabled";
                          errlog("Found error code $msg",$rsp['code']);
                          break;
	     case -7101 : $msg = "Invalid product face value";
                          errlog("Found error code $msg",$rsp['code']);
                          break;
	     case -7102 : $msg = "No stock for this product is available";
                          errlog("Found error code $msg",$rsp['code']);
                          break;
	     case -7103 : $msg = "Requested quantity for this product is not available";
                          errlog("Found error code $msg",$rsp['code']);
                          break;
	     case -7104 : $msg = "You have no rights for online shopping";
                          errlog("Found error code $msg",$rsp['code']);
                          break;
	     case -7105 : $msg = "You have not sufficient balance to proceeed this shopping";
                          errlog("Found error code $msg",$rsp['code']);
                          break;
	     case -7106 : $msg = "Your reseller have no sufficient balance to proceed this shopping";
                          errlog("Found error code $msg",$rsp['code']);
                          break;
	     case -7107 : $msg = "No serial number found against this product";
                          errlog("Found error code $msg",$rsp['code']);
                          break;
	     case -7108 : $msg = "No pin code found against this product";
                          errlog("Found error code $msg",$rsp['code']);
                          break;
	      default: $msg = 'Unknown error. Program should be fixed.';
                       errlog("Found error code $msg",$rsp['code']);
             } // end switch 
               $e = new SoapFault ($rsp['code'], $msg);
		return $e;
             } //end if 
          else {
             $wholeRsp=array('getPinsResult' =>
                           array('tblShopping_Get_APIResult' => $rsp)); 

             errlog("Inside SIMXpress.getPins rsp=\n",$wholeRsp);
             }
   	  return $wholeRsp;
	} // getPins
	
	function productListing($p)
	{
          errlog("Inside SIMXpress.productListing= ", $p);
	
	##############FIX#########################3
	
	   $rsp=array(
                  array('productID' => 100, 
	                'productName' => 'product1', 
	                'productSalesPrice' => '15.00', 
	                'isProductAvailable' => '1', 
	                'productDiscount' => '0.0'), 
                  array('productID' => 200, 
	                'productName' => 'product2', 
	                'productSalesPrice' => '25.00', 
	                'isProductAvailable' => '1', 
	                'productDiscount' => '0.0'), 
                  array('productID' => 300, 
	                'productName' => 'product3', 
	                'productSalesPrice' => '50.00', 
	                'isProductAvailable' => '1', 
	                'productDiscount' => '0.0')); 
	
	   return array('productListingResult' =>
		     array('tblProduct_GetLiveShop_APIResult' => $rsp));
	}//productListing    
	
} //SoapHandler

errlog('Main', $HTTP_RAW_POST_DATA);

//
// notice $server is started differently here...
//
$server = new SoapServer($wsdl, 
                array('uri' => "http://www.gtlpins.com/WebServices/",
		      'soap_version' => SOAP_1_2,
		      'trace' => 1));

#$server = new SoapServer(null, array('uri' =>$wsdl));
$server->setClass("SoapHandler");
$server->handle();

?>
