<?php
require '/u/debit/lib/php/predis/autoload.php';

$predisMode = true;
$redis = null;
$redisConfig = array(
		     "scheme" => "tcp",
		     "host" => "10.227.2.16",
		     "port" => 6379,
		     "auth" => 'plm@devcent702.idt.net'
		     );

class DollarPhone {
  public $ResponseMessages = array(
				   '0'    => 'Success',
				   '-1'   => 'Invalid Login',
				   '-2'   => 'Invalid Login',
				   '-6'   => 'Invalid offering',
				   '-34'  => 'Account past due',
				   '-35'  => 'Transaction exceeds credit limit',
				   '-40'  => 'Invalid Phone number',
				   '-41'  => 'Invalid amount',
				   '-42'  => 'Invalid Product',
				   '-400' => 'Invalid phone number',
				   '-401' => 'Processing error',
				   '-402' => 'Transaction already completed',
				   '-403' => 'Invalid transaction amount',
				   '-404' => 'Invalid product',
				   '-405' => 'Duplicate transaction',
				   '-406' => 'Invalid Transaction Id',
				   '-407' => 'Denomination currently unavailable',
				   '-408' => 'Transaction amount limit exceeded',
				   '-409' => 'Destination Account is not prepaid',
				   '-410' => 'Handset was reloaded within the last 10 minutes',
				   '-411' => 'TopUp refused'
				   );

  public $ResultMap;

  function __construct() 
  {
    global $redis;
    global $redisConfig;
    global $predisMode;

    $results = dirname($_SERVER['SCRIPT_FILENAME']) . "/results.json";

    if (file_exists($results)) {
      $json = file_get_contents($results);

      if ($json) {
	$this->ResultMap = json_decode($json, true);

	if ($this->ResultMap == NULL) {
	  $this->FILOG(__FUNCTION__ . ':could not decode - ' . $results . "\n");
	}
      } else {
	$this->FILOG(__FUNCTION__ . ':could not be read - ' . $results . "\n");
      }
    } else {
      $this->FILOG(__FUNCTION__ . ': ' . $results . " not found.\n");
    }

    try {
      if ($predisMode) {
	$this->FILOG(__FUNCTION__ . ": new Predis\\Client()\n");
	$redis = new Predis\Client($redisConfig);
      } else {
	$this->FILOG(__FUNCTION__ . ": new Redis()\n");
	$redis = new Redis();
	$this->FILOG(__FUNCTION__ . ": redis connect()\n");
	$redis->connect($redisConfig['host'], $redisConfig['port']);
      }

      $redis->auth($redisConfig['auth']);

      $this->FILOG(__FUNCTION__ . ': ' . "Redis connection to " . $redisConfig['host'] . ':' . $redisConfig['port'] . " authorized.\n");
    } catch (Exception $e) {
      $this->FILOG(__FUNCTION__ . ': ' . $e->getMessage() . "\n");
    }
  }

  function FILOG( $str )
  {
    $now = date("M j G:i:s");
    $pid = getmypid();
    $FIlogfile='/tmp/DollarPhone.log';
    $FIlog = fopen($FIlogfile,'a+');
    
    fwrite($FIlog, "$now [$pid] $str");
    fflush($FIlog);
    fclose($FIlog);
  } // end FILOG

  function s_rand($numDigits) {
    $chars = '0123456789';
    $randStr = "";
    for ($i=0; $i<$numDigits; $i++) {
        $randInt = rand(0, 9);
        $randStr .= $chars[$randInt];
    }
    return $randStr;
  }

  function TopUpRequest($request) {
    global $redis;
    global $predisMode;

    $this->FILOG(__FUNCTION__ . ': ' . print_r($request, true));

    $msisdn = $request->TopUpReq->PhoneNumber;
    $transid = 0;
    $pin = null;
    $code = '-401';

    if (array_key_exists($msisdn, $this->ResultMap)) {
      if (strcasecmp($request->TopUpReq->Action, 'PurchasePin') == 0) {
	$pin = $this->s_rand(10);
      }
      $code = $this->ResultMap[$msisdn]['topup'];
      if ($code == '0') {
	$transid = getmypid();
      }
      if (isset($this->ResultMap[$msisdn]['delay'])) {
	$delay = $this->ResultMap[$msisdn]['delay'];
	$this->FILOG(__FUNCTION__ . ': Sleeping for ' . $delay . " seconds.\n");
	sleep($delay);
      }

      if (isset($this->ResultMap[$msisdn]['http_response_code'])) {
	http_response_code($this->ResultMap[$msisdn]['http_code']); 
      }
    } else {
      $this->FILOG(__FUNCTION__ . ': Unknown MSISDN=' . $msisdn . "\n");
#     $this->FILOG(__FUNCTION__ . ': ' . print_r($this->ResultMap, true) . "\n");
    }

    $msg = $this->ResponseMessages[$code];

    $response = array('TopUpRequestResult' =>
		      array('responseMessage' => $msg,
			    'responseCode' => $code,
			    'TransId' => $transid,
			    'Pin' => $pin
			    )
		      );

    if ($transid > 0) {
      $key = 'DollarPhone.TXN.' . $transid;
      if ($redis) {
	if ($predisMode) {
	  $redis->del($key);
	} else {
	  $redis->delete($key);
	}
	$redis->hSet($key, 'transid', $transid);
	$redis->hSet($key, 'msisdn', $msisdn);
	$redis->hSet($key, 'pin', $pin);
	$this->FILOG(__FUNCTION__ . ': ' . "Transaction stored under Redis key [" . $key . "]\n");
      }
    }

    $this->FILOG(__FUNCTION__ . 'Response: ' . print_r($response, true));

    return $response;
  }

  function TopupConfirm($request) {
    global $redis;

    $this->FILOG(__FUNCTION__ . ': ' . print_r($request, true));
    
    $status = 'Invalid';
    $code = -406;
    $pin = '';

    if ($request->TransID > 0) {
      if ($redis) {
	$key = 'DollarPhone.TXN.' . $request->TransID;

	if ($redis->hLen($key)) {
	  $msisdn = $redis->hGet($key, 'msisdn');
	  $pin = $redis->hGet($key, 'pin');

	  if (isset($this->ResultMap[$msisdn]['confirm'])) {
	    $code = $this->ResultMap[$msisdn]['confirm'];
	  }

	  if (isset($this->ResultMap[$msisdn]['http_response_code'])) {
	    http_response_code($this->ResultMap[$msisdn]['http_code']); 
	  }
	} else {
	  $this->FILOG(__FUNCTION__ . ': Key [' . $key . "] not found in Redis store.\n");
	}
      }
    }

    $msg = $this->ResponseMessages[$code];

    $response = array('TopupConfirmResult' =>
		      array('ID' => $request->TransID,
			    'Status' => $status,
			    'ErrorCode' => $code,
			    'ErrorMsg' => $msg,
			    'PIN' => $pin,
			    'CarrierTransId' => '')
		      );
    $this->FILOG(__FUNCTION__ . 'Response: ' . print_r($response, true));

    return $response;
  }

  function GetTopupProducts($request) {
    $this->FILOG(__FUNCTION__ . ': ' . print_r($request, true));

    $response = simplexml_load_file(dirname($_SERVER['SCRIPT_FILENAME']) . "/products.xml");
    $response = json_decode(json_encode($response), true);

    # $this->FILOG(__FUNCTION__ . 'Response: ' . print_r($response, true));

    return $response;
  }
}

class DollarPhoneProxy
{
  public function __call($methodName, $args) {
    $dollar = new DollarPhone();

    $dollar->FILOG(__FUNCTION__ . ': ' . print_r($args, true));

    $result = call_user_func_array(array($dollar, $methodName),  $args);

    if ($methodName != 'GetTopupProducts') {
      $dollar->FILOG(__FUNCTION__ . '(' . $methodName . ') - Result: ' . print_r($result, true));
    }

    return $result;  }
}

if (!function_exists('http_response_code'))
{
    function http_response_code($newcode = NULL)
    {
        static $code = 200;
        if($newcode !== NULL)
        {
            header('X-PHP-Response-Code: '.$newcode, true, $newcode);
            if(!headers_sent())
                $code = $newcode;
        }       
        return $code;
    }
}

date_default_timezone_set('America/New_York');

$server = new SoapServer("http://devcall02.ixtelecom.com/wsdl/DollarPhone.wsdl", 
                         array(
                               'uri' => "urn:https://dollarphone.com/PMAPI/PinManager",
                               'soap_version' => SOAP_1_2,
                               'trace' => 1));

$server->setClass("DollarPhoneProxy"); 
$server->handle(); 
?>
