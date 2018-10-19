<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__));
// The following combination is used for ImtuTopupClass because we need to 
//be able to do reversals
include '../QAlib/wslib1.php';
include '../ezetop/ImtuTopUpClass.php';

function findNumOrders($id) {
/* Simply return the number of Order line Items that exist for a particular 
   orderID */
    $xmld = getDB('MediaMind', $id);
    $xa = array('sFirst' => 'a0',
		'sLast' => 'a1',
		'sAddress' => 'a2',
                'sCity' => 'a3',
		'sState' => 'a4',
		'sCountry' => 'a5',
		'sEmail' => 'a6',
		'sPhone' => 'a7',
		'rFirst' => 'a8',
		'rLast' => 'a9',
		'rAddress' => 'a10',
                'rCity' => 'a11',
		'rState' => 'a12',
		'rCountry' => 'a13',
		'rEmail' => 'a14',
		'rPhone' => 'a15',
		'currency' => 'a16',
		'total' => 'a17',
		'numLineItems' => 'a18');
    //errlog("INFO - findNumOrders ", $xa);
     $ra = array();
     $xe = array();
     $sku = "";
     $ca=array_merge($xa,$ra, array('delay'=>'delay', 'abort'=>'abort'));
     $xA=retrievXmlParm2A($xmld, $ca);
     //errlog("INFO - findNumOrders  xA", $xA);
     if (sizeof($xA) > 0) {
        foreach($xa as $key => $val) {
       //errlog("INFO findNumOrders  - xA key: $key  val: ", $val);
          if (array_key_exists($val, $xA))  {
          //errlog("INFO findNumOrders  - found array key: $val for key:", $key);
             if (isset($xA[$val])) {
             //errlog("INFO findOrderDetails - setting xe[$key] to: ", $xA[$val]);
                $xe[$key]=$xA[$val];
                }
             }
          }
        return ($xe['numLineItems']);
     } else return 0;
} // end of findNumOrders

function reverse($id, $partial, $paramList)
/* This function is used for both full and partial reversals.  For parial orders
   the $parmList parameter will contain the POST data which will be an 
   associative arrray holding an element for SKU with the sku's value.  The
   Order Line Items are stored in the DB with the key as the orderID followed
   by the number of the line item.  Each line item would have to be searched
   to find the matching sku for a partial reversal.  For a full reversal, we
   still need to run through them all, reversing each line item regarless of 
   the SKU.*/
{   
     $reversed = false;
     $revStatus = 0;
     $numOrders = findNumOrders($id);
     if ($numOrders == 0) 
        $revStatus = 1; // nothing to reverse
     $sku = '';
     if ($partial) {
        $sku = $paramList['SKU'];
     }
     for ($i = 0; $i < $numOrders; ++$i) {
         $key = array ('app' => 'MediaMind', 'key' => $id.$i);
         $xmld = getDB('MediaMind', $id.$i);
          errlog("INFO - reverse - order xmld", $xmld);
         $xa = array('SKU' => 'a0',
		'Amount' => 'a1',
		'Quantity' => 'a2',
                'Reversed' => 'a3');
          $ra = array();
          $tpObj = new TopupObj($key, $xa, $ra);
          if (isset($tpObj->xe['SKU'])) {
             if (($xe['SKU'] == $sku) || (!$partial)) {
//update this order
                $xaNew= array('a0' => $tpObj->xe['SKU'],
			      'a1' => $tpObj->xe['Amount'],
			      'a2' => $tpObj->xe['Quantity'],
			      'a3' => 'true');
		$key = $id.$i;
		$reversed = true;
                updateTransDBRec($key, $xaNew, 'MediaMind');
                errlog("INFO MediaMind reversed - sku: ", $tpObj->xe['SKU']);
		}
          }
     }
     if (!$revStatus && $reversed) {
	$statusString = 'Success';
     } else {
	$statusString = 'Failure';
     }
     $rsp = (array ( ));
     $rsp=json_encode($rsp);
     errlog("DEBUG: sending resp from reverse", $rsp);
     sendRsp($rsp);
}//reverse

function sendRsp($a, $code=200)
/* This is used every time a JSON response is sent */
{
    $ht = new HttpResponse();
    $ht->status($code);
    //$ht->setContentType('application/xml');
    $ht->setContentType('application/json');
    $ht->setData($a);
    $ht->send();
}

function i_rand($min=0,$max=1000){
/* This random number generator is used to find a pin */
    if ($min>$max) return false;
    return mt_rand($min,$max);
}

function sendMissingParmError() {
/* The current API does not have the system send back any error responses
   in any case.  But, soemthing different is needed for those cases.  This is 
   done for now as a sort of place holder.  */
  $rsp = new SimpleXMLElement('<quippiApi/>');
  $rsp->addChild('operationStatus', 4);
  $rsp->addChild('discriptiion', 'Missing parameter(s). One or more of the mandatory input parameters was not found.'); 
  errlog ("xml to send", $rsp->asXML());
  sendRsp($rsp->asXML());
}

function showStatus($orderId)
/* This function is used to show the status when the command 'status' is  
   used in the URL.  In order to find the status the system needs to look 
   at the main DB record for the order and then when the num of line items
   is found, it goes through each DB record for each line item and finds 
   whether it was reversed so that it knows the full status. */
{
    //errlog("INFO - showStatus - for orderID", $OrderId);
     $partial = false;
     $full = true;
     $numOrders = findNumOrders($orderId);
    //errlog("INFO - showStatus - numOrders ", $numOrders);
     for ($i = 0; $i < $numOrders; ++$i) {
         $xmld = getDB('MediaMind', $orderId.$i);
         $xa = array('SKU' => 'a0',
		'Amount' => 'a1',
		'Quantity' => 'a2',
                'Reversed' => 'a3');
          $ra = array();
          $oli = array();
          $ca=array_merge($xa,$ra, array('delay'=>'delay', 'abort'=>'abort'));
          $xA=retrievXmlParm2A($xmld, $ca);
          //errlog("INFO - showStatus - order $i", $xA);
          foreach($xa as $key => $val) {
             //errlog("INFO showStatus - xA key: $key  val: ", $val);
             if (array_key_exists($val, $xA))  {
                //errlog("INFO showStatus - found array key: $val for key:", $key);
                if (isset($xA[$val])) {
                   //errlog("INFO showStatus - setting xe[$key] to: ", $xA[$val]);
                   $xe[$key]=$xA[$val];
                   }
                }
          }
          if (isset($xe['Reversed'])) {
    //errlog("INFO - showStatus - order $i reversed", $xe['Reversed']);
             if (strcmp($xe['Reversed'], 'true')== 0) {
		$partial = true;
    errlog("INFO - showStatus - order $i partial", $partial);
                } else {
                  $full = false;
                }
	     }
          } // end of for loop
    $statusMsg = 'New';
    $statusInt = 1;
    if ($full) {
       $statusInt = 4;
       $statusMsg = 'Reversed';
    } else if ($partial) {
      $statusInt = 2;
      $statusMsg = 'Processing';
    }
    $stat = array('PartialReversal' => $partial,
		'FullReversal' => $full,
		'OrderId' => $orderId,
                'Status' => $statusInt,
		'StatusMessage' => $statusMsg);
     $rsp=json_encode($stat);
     errlog("DEBUG: sending resp from showStatsu", $rsp);
     sendRsp($rsp);
} // end of showStatus
 
function setSender($largeArray) {
     $sender = array ('FirstName' => $largeArray['sFirst'],
		      'LastName' => $largeArray['sLast'],
		      'Address' => $largeArray['sAddress'],
		      'City' => $largeArray['sCity'],
		      'State' => $largeArray['sState'],
		      'Country' => $largeArray['sCountry'],
		      'Email' => $largeArray['sEmail'],
		      'Phone' => $largeArray['sPhone']);
     return $sender;
}

function setReceiver($largeArray) {
     $receiver = array ('FirstName' => $largeArray['rFirst'],
		      'LastName' => $largeArray['rLast'],
		      'Address' => $largeArray['rAddress'],
		      'City' => $largeArray['rCity'],
		      'State' => $largeArray['rState'],
		      'Country' => $largeArray['rCountry'],
		      'Email' => $largeArray['rEmail'],
		      'Phone' => $largeArray['rPhone']);
     return $receiver;
}

function findOrderLineItems ($id, $numItems) {
     $oLine = array();
     for ($i = 0; $i < $numItems; ++$i) {
         $xmld = getDB('MediaMind', $id.$i);
         $xa = array('SKU' => 'a0',
		'Amount' => 'a1',
		'Quantity' => 'a2',
                'Reversed' => 'a3');
          $ra = array();
          $oli = array();
          $ca=array_merge($xa,$ra, array('delay'=>'delay', 'abort'=>'abort'));
          $xA=retrievXmlParm2A($xmld, $ca);
          //errlog("INFO findOrderDetails - xA from xml", $xA);
          foreach($xa as $key => $val) {
           // errlog("INFO findOrderDetails - xA key: $key  val: ", $val);
            if (array_key_exists($val, $xA))  {
               //errlog("INFO findOrderDetails found array key: $val for key:", $key);
               if (isset($xA[$val])) {
                  //errlog("INFO findOrderDetails- setting xe[$key] to: ", $xA[$val]);
                  $oli[$key]=$xA[$val];
                  }
               }
            }
          $oLine[$i] = $oli;
          }
  return $oLine;
}

function findOrderDetails($id) {
/* This is the function that is called when the URL simply specifies the order
   number.  In that case, all of the information that we have about the 
   order is sent back in a JSON message. */
    errlog("INFO - findOrderDetails", $id);
    $xmld = getDB('MediaMind', $id);
    $xa = array('sFirst' => 'a0',
		'sLast' => 'a1',
		'sAddress' => 'a2',
                'sCity' => 'a3',
		'sState' => 'a4',
		'sCountry' => 'a5',
		'sEmail' => 'a6',
		'sPhone' => 'a7',
		'rFirst' => 'a8',
		'rLast' => 'a9',
		'rAddress' => 'a10',
                'rCity' => 'a11',
		'rState' => 'a12',
		'rCountry' => 'a13',
		'rEmail' => 'a14',
		'rPhone' => 'a15',
		'currency' => 'a16',
		'total' => 'a17',
		'numLineItems' => 'a18');
     $ra = array();
     $xe = array();
     $ca=array_merge($xa,$ra, array('delay'=>'delay', 'abort'=>'abort'));
     $xA=retrievXmlParm2A($xmld, $ca);
     //errlog("INFO findOrderDetails - xA from xml", $xA);
     foreach($xa as $key => $val) {
       //errlog("INFO findOrderDetails - xA key: $key  val: ", $val);
       if (array_key_exists($val, $xA))  {
          errlog("INFO findOrderDetails - found array key: $val for key:", $key);
          if (isset($xA[$val])) {
             errlog("INFO findOrderDetails - setting xe[$key] to: ", $xA[$val]);
             $xe[$key]=$xA[$val];
             }
          }
       }
     $sender = setSender($xe);
     $receiver = setReceiver($xe);
     $oLine = findOrderLineItems ($id, $xe['numLineItems']);
     $toSend = array('OrderId' => $id,
                      'Sender' => $sender,
                      'Receiver' => $receiver,
		      'OrderLineItems' => $oLine,
		      'Status' => 3,
		      'StatusMessage' => 'Complete',
		      'TotalAmount' => $xe['total'],
		      'Currency' => $xe['currency']);
     $rsp=json_encode($toSend);
     errlog("DEBUG: sending resp from findOrderDetails", $rsp);
     sendRsp($rsp);
} //findOrderDetails 

function setSenderInfo ($sender, &$xa) {
    //errlog("INFO createOrder   - sender:", $sender);
       if ((((!isset($sender['FirstName'])) || (!isset($sender['LastName']))) ||
         ((!isset($sender['Address'])) || (!isset($sender['City'])))) ||
         (((!isset($sender['State'])) || (!isset($sender['Country'])) ||
            (!isset($sender['Phone']))))) {
        $problemFound = true;
	sendMissingParmError();
        return true;
       } else {
         $xa ['a0'] = $sender['FirstName'];
         $xa ['a1'] = $sender['LastName'];
         $xa ['a2'] = $sender['Address'];
         $xa ['a3'] = $sender['City'];
         $xa ['a4'] = $sender['State'];
         $xa ['a5'] = $sender['Country'];
         $xa ['a6'] = $sender['Email'];
         $xa ['a7'] = $sender['Phone'];
         return false;
       }
}

function setRecipInfo ($recip, &$xa) {
    //errlog("INFO createOrder   - recip :", $recip);
       if ((((!isset($recip['FirstName'])) || (!isset($recip['LastName']))) ||
         ((!isset($recip['Address'])) || (!isset($recip['City'])))) ||
         (((!isset($recip['State'])) || (!isset($recip['Country'])) ||
	   (!isset($recip['Phone']))))) {
	sendMissingParmError();
        return true;
       } else {
         $xa ['a8'] = $recip['FirstName'];
         $xa ['a9'] = $recip['LastName'];
         $xa ['a10'] = $recip['Address'];
         $xa ['a11'] = $recip['City'];
         $xa ['a12'] = $recip['State'];
         $xa ['a13'] = $recip['Country'];
         $xa ['a14'] = $recip['Email'];
         $xa ['a15'] = $recip['Phone'];
         return false;
       }
}

function createOrder ($ap)
/* This routine is called to start up a new order after a request was sent
   to discover the current exchange rate. So, this routine verifies that
   there is a record added to the DB for the amount in question.  It could 
   be improved by checking the date as well....  */
{
    //parse_str($ap, $p);
    $jsonOrder = json_decode($ap, true);
    $orderId = i_rand(1,50000);
    errlog("INFO createOrder   - id:", $orderId);
    $recip= "";
    $problemFound = false;
    $xa =array();
    //errlog("INFO createOrder   - order:", $jsonOrder);
    if (isset($jsonOrder['Sender'])) {
       $sender = $jsonOrder['Sender'];
       $problemFound = setSenderInfo ($sender, $xa);
    }
    if (isset($jsonOrder['Receiver']) && !$problemFound) {
       $recip = $jsonOrder['Receiver'];
       $problemFound = setRecipInfo ($recip, $xa);
    }
    $problemFoundInOrderLineItem = false;
    $numLineItems = 0;
    $lineItems= "";
    if (isset($jsonOrder['OrderLineItems'])) {
       $lineItems = $jsonOrder['OrderLineItems'];
       $numLineItems = sizeof($lineItems);
       for ($i = 0; $i < $numLineItems; ++$i) {
           $lineItem = $lineItems[$i];
           if (((!isset($lineItem['SKU'])) || (!isset($lineItem['Amount']))) ||
               ((!isset($lineItem['Quantity'])) || (!isset($lineItem['Reversed'])))) { 
	sendMissingParmError();
        $problemFoundInOrderLineItem = true;
       } else {
           $sku[$i] = $lineItem['SKU'];
           $amt[$i] = $lineItem['Amount'];
           $qty[$i] = $lineItem['Quantity'];
           $rev[$i] = $lineItem['Reversed'];
      }
    }
    errlog("INFO createOrder   - dealt with missing Parms:", $problemFoundInOrderLineItem);
    if (isset($jsonOrder['Currency'])) { //todo coomplain if this is mandatory
       $xa ['a16'] = $jsonOrder['Currency'];
    }
    if (isset($jsonOrder['TotalAmount'])) { // todo .. mandatory?
       $xa ['a17'] = $jsonOrder['TotalAmount'];
    }
    $xa ['a18'] = $numLineItems;
    if (!$problemFound && !$problemFoundInOrderLineItem) {
       $xA = array('senderPhone' => 'a0', 'SKU' => 'a1', 'amount' => 'a2');
       $rA = array('status' => 'a3', 'testName' => 'a4', 'status2' => 'a5');
       $key1 = array('app' => 'MediaMind', 'key' => $orderId);
       $key2 = array('app' => 'MediaMind', 'key' => $sender['Phone']);
       $tpO = new TopupObj($key2, $xA, $rA);
       $massagedInput = array('senderPhone' => $sender['Phone'], 'SKU' => $sku[0], 'amount' => $amt[0]); 
       $tpO->validateReqInput($massagedInput);
       $statusString='Processing';
       $stat=2;
    errlog("INFO createOrder   - topup result:", $tpO->re);
       $result = $tpO->re;
       if (sizeof($result) > 0)  {
          $status = $result['status'];
       }  else
          $status = 0;
       if ($status == 0) {
        insertTransDB($key1, $xa);
        for ($i = 0; $i < $numLineItems; ++$i) {
            $key = array('app' => 'MediaMind', 'key' => $orderId.$i);
            $xa = array('a0' => $sku[$i],
		        'a1' => $amt[$i],
		        'a2' => $qty[$i],
		        'a3' => $rev[$i]);
           insertTransDB($key, $xa);
           }
        }
     $rsp = (array ('PartialReversal' => 'false', 
                    'FullReversal' => 'false', 
                    'OrderId' => $orderId,
                    'Status' => $stat, 
		    'StatusMessage' => $statusString));
     $rsp=json_encode($rsp);
     if (sizeof($result) > 0)  {
        $tpO->buildRspData($rsp);
     }
     $rsp = (array ('Message' => 'Success', 
             'OrderId' => $orderId));
     $rsp=json_encode($rsp);
     errlog("DEBUG: sending resp from topUp", $rsp);
     sendRsp($rsp);
    }
  }
} //end of  createOrder    

function parseAction($get, $p, $parms)
/* This function is used to make sure that all requests are directed
   appropriately to the correct routines. */
{
    errlog("parseAction: usr info - get: $get; p: $p; parms ", $parms);

    $id="";
    $cmd="";
    if (isset($get['id'])) {
       $id=$get['id'];
    }
    if (isset($get['cmd'])) {
       $cmd=$get['cmd'];
    }
    switch ($cmd) {

        case 'partial-reversal':
	   reverse ($id,true, $parms);
           break;
        case 'full-reversal': 
	   reverse ($id,false, $parms);
              break;
        case 'status':
              showStatus($id, $p);
              break;
        default:
               if (empty($id)) {
		 createOrder($p);
               } else {
                  findOrderDetails($id, $p);
               }
     } // end switch
}//parseAction

// The following code is the main driver that is run when a request is sent in

try {

$reqRev='';
$getRev='';
$putRev='';

//   errlog("INFO: GLOBALS: ", $GLOBALS);

if (!empty($_GET)) {
   errlog("INFO: GET: ", $_GET);
   $getRev=$_GET;
}
if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
   parse_str(file_get_contents("php://input"), $post_vars);
   errlog("INFO: PUT: ", $post_vars);
   $putRev=$post_vars;
}
if (!empty($HTTP_RAW_POST_DATA)) {
   $reqRev=$HTTP_RAW_POST_DATA;
   errlog("INFO: HTTP_RAW_POST_DATA", $HTTP_RAW_POST_DATA);

}
elseif (!empty($_POST)) {
   errlog("INFO: POST: ", $_POST);
   $reqRev=$_POST;
}


parseAction($getRev, $reqRev, $putRev);

}//try
catch (Exception $e) {
   $msg=$e->getMessage();
   $code=$e->getCode();

   if ($code > 1000) $code=400;
   
   $ersp=array('status' => $code, 'message' => $msg);
   errlog("Caught exception: $msg, code= ", $code);

   sendRsp($ersp, 400);
}
?>
