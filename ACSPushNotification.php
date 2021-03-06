<?php
/**
 * Appcelerator Cloud Service PHP Push Notification
 * 
 * Send push notification from a PHP application to ACS
 * 
 * Require: cURL, write access to temp folder
 */
class ACSPushNotification {

	protected $appKey = '';
	protected $adminName = '';
	protected $adminPass = '';
	protected $vibrate = false;
	protected $log = array();
	protected $path = '';
	protected $cookieJar = '';
	protected $ch;
	protected $errorCode = 0;
	protected $maxRetry = 5;
	protected $retryDelay = 1;

	/**
	 * @param array $options
	 */
	public function __construct($options) {
		$this->path = dirname(__FILE__);
		$this->cookieJar = sys_get_temp_dir() . '/cookie.txt';
		$this->appKey = $options['appKey'];
		$this->adminName = $options['adminName'];
		$this->adminPass = $options['adminPass'];
		if (isset($options['vibrate'])) {
			$this->vibrate = $options['vibrate'];
		}
		$this->initRequest();
	}

	/**
	 * @return array
	 */
	public function getLog() {
		return $this->log;
	}

	/**
	 * @param string $title
	 * @param string $message
	 * @param string $channel
	 * @param int $retry
	 */
	public function send($title, $message, $channel, $retry=0) {
		if ($retry < $this->maxRetry) {
			if ($retry > 0) {
				sleep($this->retryDelay * $retry);
			}
			$result = $this->sendRequest('https://api.cloud.appcelerator.com/v1/push_notification/notify.json', array(
				'channel' => $channel,
				'payload' => json_encode(array(
					'badge' => 1,
					'sound' => 'default',
					'alert' => $message,
					'title' => $title,
					'icon' => 'appicon',
					'vibrate' => $this->vibrate
				))
			));
			if (!$result) {
				$r = false;
				if ($this->errorCode == 401) { // not logged in
					$r = $this->loginAdmin();
				}
				if ($this->errorCode == 404) {
					$z = @unlink($this->cookieJar);
					$this->log[] = 'delete cookieJar = '. $z;
					$r = $this->loginAdmin();
				}
				if ($r) {
					$this->send($title, $message, $channel, ++$retry);
				}
			}
		}
		$this->log[] = 'send(), retry = '. $retry .', result = ';
		$this->log[] = $result;
	}
	
	/**
	 * close curl handler
	 */
	public function close() {
		if ($this->ch) {
			curl_close($this->ch);
			$this->ch = null;
		}
	}
	
	/**
	 * POST https://api.cloud.appcelerator.com/v1/users/login.json
	 * - login
	 * - password
	 * @param int $retry
	 */
	protected function loginAdmin($retry=0) {
		if ($retry < $this->maxRetry) {
			if ($retry > 0) {
				sleep($this->retryDelay * $retry);
			}
			$result = $this->sendRequest('https://api.cloud.appcelerator.com/v1/users/login.json', array(
				'login' => $this->adminName,
				'password' => $this->adminPass
			));
			$this->log[] = 'loginAdmin(), retry = '. $retry .', result = ';
			$this->log[] = $result;
			if ($result) {
				return true;
			} else {
				if ($this->errorCode == 404) {
					$r = @unlink($this->cookieJar);
					$this->log[] = 'delete cookieJar = '. $r;
				}
				return $this->loginAdmin(++$retry);
			}
		}
		return false;
	}
	
	/**
	 * initialize curl handler
	 */
	protected function initRequest() {
		if ($this->ch === null) {
			$this->ch = curl_init();
			$opt = array(
				CURLOPT_TIMEOUT => 60,
				// set ssl certificate
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_SSL_VERIFYHOST => 2,
				CURLOPT_CAINFO => $this->path . '/cacert.pem',
				// set post parameter
				CURLOPT_POST => true,
				// store cookie
				CURLOPT_COOKIEFILE => $this->cookieJar,
				CURLOPT_COOKIEJAR => $this->cookieJar,
				// return result as string
				CURLOPT_RETURNTRANSFER => true
			);
			curl_setopt_array($this->ch, $opt); 
		}
	}
	
	/**
	 * @param string $url
	 * @param array $postfields
	 * @return boolean
	 */
	protected function sendRequest($url, $postfields) {
		$this->errorCode = 0;
		$url = $url . '?key=' . $this->appKey;
		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
		$response = curl_exec($this->ch);
		$this->log[] = 'curl_error = '; $this->log[] = curl_error($this->ch);
		$this->log[] = 'curl_errno = '; $this->log[] = curl_errno($this->ch);
		$this->log[] = 'sendRequest(), response = ';
		$this->log[] = $response;
		$json = json_decode($response, true);
		$this->log[] = 'sendRequest(), json = ';
		$this->log[] = $json;
		if (is_array($json) && isset($json['meta'])) {
			if ($json['meta']['code'] == 200) { // successful
				return true;
			}
			$this->errorCode = $json['meta']['code'];
		} else {
			$this->errorCode = 500;
		}
		return false;
	}

}
?>