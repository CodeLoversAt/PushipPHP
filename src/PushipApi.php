<?php
if (!function_exists('curl_init')) {
	throw new Exception('Puship needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
	throw new Exception('Puship needs the JSON PHP extension.');
}

class PushipApi {
	const VERSION = '1.2.3';
    const API_URL = 'http://puship.cloudapp.net/ServiceAPIAuth/PushipAPI.svc/';


public static $CURL_OPTS = array(
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_USERAGENT      => 'puship-php-1.2.2',
    CURLOPT_VERBOSE        => 0,      //Enable for debugging
);

    /**
    * The Application ID.
    *
    * @var string
    */
	protected $appId;
    protected $username;
    protected $password;
    public $EnableDebug = false;

    /**
    * Set the Application ID.
    *
    * @param string $appId The Application ID
    * @return BasePushipApi
    */
	public function setAppId($appId) {
		$this->appId = $appId;
		return $this;
	}
    public function setUsername($username) {
		$this->username = $username;
		return $this;
	}
    public function setPassword($password) {
		$this->password = $password;
		return $this;
	}
    public function setEnableDebug($EnableDebug) {
		$this->EnableDebug = $EnableDebug;
		return $this;
	}
    /**
    * Get the Application ID.
    *
    * @return string the Application ID
    */
	public function getAppId() {
		return $this->appId;
	}
    public function getUsername() {
		return $this->username;
	}
    public function getPassword() {
		return $this->password;
	}
     public function getEnableDebug() {
		return $this->EnableDebug;
	}
	public function PushipApi($config) {
		$this->setAppId($config['AppId']);
        $this->setUsername($config['Username']);
        $this->setPassword($config['Password']);
	}

    function timezoneDoesDST($tzId) {
        $tz = new DateTimeZone($tzId);
        $trans = $tz->getTransitions();
        return ((count($trans) && $trans[count($trans) - 1]['ts'] > time()));
    }

	public function _graph($path, $method = 'GET', $params = array()) {
		if (is_array($method) && empty ($params)) {
			$params = $method;
			$method = 'GET';
		}

        //$params['method'] = $method; // method override as we always do a POST
		$result = json_decode($this->pushipRequest($path,$params), true);

        // results are returned, errors are thrown
		if (is_array($result) && isset ($result['error'])) {
			//$this->throwAPIException($result);
			$e = new PushipApiException(array('error_code' => '500', 'error' => array('message' => 'General response error', 'type' => 'ResponseExceptionon',),));
			throw $e;
            // @codeCoverageIgnoreStart
		}
        // @codeCoverageIgnoreEnd
		return $result['d'];
	}

	protected function pushipRequest($path, $params) {
		if (!isset ($params['AppId'])) {
			$params['AppId'] = $this->getAppId();
		}
        if (!isset ($params['Username'])) {
			$params['Username'] = $this->getUsername();
		}
        if (!isset ($params['Password'])) {
			$params['Password'] = $this->getPassword();
		}

        // json_encode all params values that are not strings
		foreach ($params as $key => $value) {
			if (!is_string($value)) {
			  if($this->EnableDebug === True){
    			  echo "Key: " .   $key;
              }
				$params[$key] = json_encode($value);
			}

		}
		return $this->makeRequest(self::API_URL . $path, $params);
	}

	protected function makeRequest($url, $params, $ch = null) {
		if (!$ch) {
			$ch = curl_init();
		}
		$opts = self :: $CURL_OPTS;

	    $opts[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&');

        if($this->EnableDebug === True){
            echo("CURLOPT_POSTFIELDS:" . $opts[CURLOPT_POSTFIELDS] . "<br>");
        }

		$opts[CURLOPT_URL] = $url;


       //Contains encoded string to pass along for basic authentication purposes
       $auth_token = 'Basic '. base64_encode($params['Username'] . ':' . $params['Password']);

       $headers = array(
            "Authorization: Basic " . base64_encode($params['Username'] . ':' . $params['Password'])
        );
        //$opts["Authorization"] = 'Authorization: ' . $auth_token ;

        if($this->EnableDebug === True){
            echo("CURLOPT_URL:" . $opts[CURLOPT_URL]. "<br>");
        }
        // disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
        // for 2 seconds if the server does not support this header.
		if (isset ($opts[CURLOPT_HTTPHEADER])) {
			$existing_headers = $opts[CURLOPT_HTTPHEADER];
			$existing_headers[] = 'Expect:';
			$opts[CURLOPT_HTTPHEADER] = $existing_headers;
		}
		else {
			$opts[CURLOPT_HTTPHEADER] = array('Expect:');
		}

		curl_setopt_array($ch, $opts);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt( $ch, CURLOPT_ENCODING, "UTF-8" );

		$result = curl_exec($ch);

		$errno = curl_errno($ch);

        // CURLE_SSL_CACERT || CURLE_SSL_CACERT_BADFILE
		if ($errno == 60 || $errno == 77) {
			self :: errorLog('Invalid or no certificate authority found, ' . 'using bundled information');
			curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/fb_ca_chain_bundle.crt');
			$result = curl_exec($ch);
		}

        // With dual stacked DNS responses, it's possible for a server to
        // have IPv6 enabled but not have IPv6 connectivity.  If this is
        // the case, curl will try IPv4 first and if that fails, then it will
        // fall back to IPv6 and the error EHOSTUNREACH is returned by the
        // operating system.
		if ($result === false && empty ($opts[CURLOPT_IPRESOLVE])) {
			$matches = array();
			$regex = '/Failed to connect to ([^:].*): Network is unreachable/';
			if (preg_match($regex, curl_error($ch), $matches)) {
				if (strlen(@ inet_pton($matches[1])) === 16) {
					self :: errorLog('Invalid IPv6 configuration on server, ' . 'Please disable or get native IPv6 on your server.');
					self :: $CURL_OPTS[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
					curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
					$result = curl_exec($ch);
				}
			}
		}
		if ($result === false) {
			$e = new PushipApiException(array('error_code' => curl_errno($ch), 'error' => array('message' => curl_error($ch), 'type' => 'CurlException',),));
			curl_close($ch);
			throw $e;
		}
        if($this->EnableDebug === True){
            echo($result);
        }
		curl_close($ch);
		return $result;
	}

    function time_to_ticks($time) {
        return number_format(($time * 10000000) + 621355968000000000 , 0, '.', '');
    }

    public function GetPushMessages($params) {
      return $this->_graph('GetPushMessagesPost?$format=json',$params);
    }

    public function GetPushMessagesByDevice($params) {
      return $this->_graph('GetPushMessagesByDevicePost?$format=json',$params);
    }

    public function DeletePushMessage($params) {
        $pushresult = $this->_graph('DeletePushMessagesPost?$format=json',$params);
        if (is_array($pushresult)){
            $pushresult= $pushresult["DeletePushMessagesPost"];
        }
        return $pushresult;
    }

    public function GetDevices($params= array()) {
      if (isset ($params['P1Latitude']) && isset ($params['P1Longitude'])
          && isset ($params['P2Latitude']) && isset ($params['P2Longitude']))
      {
          $params['ParamM1'] = $params['P1Latitude'] . '|' . $params['P1Longitude'];
          $params['ParamM2'] = $params['P2Latitude'] . '|' . $params['P2Longitude'];
      }
      if (isset ($params['LastPositionDate']))
      {
          $params['LastPositionDate'] =  $this->time_to_ticks($params['LastPositionDate']);
      }
      return $this->_graph('GetDevicesPost?$format=json',$params);
    }

    public function SendPushMessageByDevice($params) {
        $pushresult = $this->_graph('SendPushMessageByDevicePost?$format=json',$params);
        if (is_array($pushresult)){
            $pushresult= $pushresult["SendPushMessageByDevicePost"];
        }
        return $pushresult;
    }

    public function SendPushMessage($params) {
        if (!isset ($params['SendAndroid'])) { //not needed but useful for server speed up
            $params['SendAndroid'] = 'False';
		}
        if (!isset ($params['SendIOS'])) {
            $params['SendIOS'] = 'False';
		}
        if (!isset ($params['SendBB'])) {
            $params['SendBB'] = 'False';
		}
        if (!isset ($params['SendWP'])) {
            $params['SendWP'] = 'False';
		}

        if (isset ($params['P1Latitude']) && isset ($params['P1Longitude'])
          && isset ($params['P2Latitude']) && isset ($params['P2Longitude']))
        {
            $params['ParamM1'] = $params['P1Latitude'] . '|' . $params['P1Longitude'];
            $params['ParamM2'] = $params['P2Latitude'] . '|' . $params['P2Longitude'];
        }
        if (isset ($params['LastPositionDate']))
        {
            $params['LastPositionDate'] =  $this->time_to_ticks($params['LastPositionDate']);
        }

        $pushresult = $this->_graph('SendPushMessagePost?$format=json',$params);
        if (is_array($pushresult)){
            $pushresult= $pushresult["SendPushMessagePost"];
        }
        return $pushresult;
    }

    public function GetAppTagFilters($params = array()) {
      return $this->_graph('GetAppTagFiltersPost?$format=json',$params);
    }

}
class PushipApiException extends Exception {
/**
* The result from the API server that represents the exception information.
*/
	protected $result;
/**
* Make a new API Exception with the given result.
*
* @param array $result The result from the API server
*/
	public function __construct($result) {
		$this->result = $result;
		$code = isset ($result['error_code']) ? $result['error_code'] : 0;
		if (isset ($result['error_description'])) {
// OAuth 2.0 Draft 10 style
			$msg = $result['error_description'];
		}
		else
			if (isset ($result['error']) && is_array($result['error'])) {
// OAuth 2.0 Draft 00 style
				$msg = $result['error']['message'];
			}
			else
				if (isset ($result['error_msg'])) {
// Rest server style
					$msg = $result['error_msg'];
				}
				else {
					$msg = 'Unknown Error. Check getResult()';
		}
		parent :: __construct($msg, $code);
	}
/**
* Return the associated result object returned by the API server.
*
* @return array The result from the API server
*/
	public function getResult() {
		return $this->result;
	}
/**
* Returns the associated type for the error. This will default to
* 'Exception' when a type is not available.
*
* @return string
*/
	public function getType() {
		if (isset ($this->result['error'])) {
			$error = $this->result['error'];
			if (is_string($error)) {
// OAuth 2.0 Draft 10 style
				return $error;
			}
			else
				if (is_array($error)) {
// OAuth 2.0 Draft 00 style
					if (isset ($error['type'])) {
						return $error['type'];
					}
				}
		}
		return 'Exception';
	}
/**
* To make debugging easier.
*
* @return string The string representation of the error
*/
	public function __toString() {
		$str = $this->getType() . ': ';
		if ($this->code != 0) {
			$str .= $this->code . ': ';
		}
		return $str . $this->message;
	}
}
?>