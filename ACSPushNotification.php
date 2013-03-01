<?php
/**
 * Appcelerator Cloud Service PHP Push Notification
 * 
 * Send push notification from a PHP application to ACS
 */
class ACSPushNotification {

	protected $appKey = '';
	protected $consumerKey = '';
	protected $secret = '';
	protected $adminName = '';
	protected $adminPass = '';
	protected $channel = '';
	protected $log = array();
	protected $path = '';
	protected $ch;

	/**
	 * @param array $options
	 */
	public function __construct($options) {
		$this->path = dirname(__FILE__);
		$this->appKey = $options['appKey'];
		$this->consumerKey = $options['consumerKey'];
		$this->secret = $options['secret'];
		$this->adminName = $options['adminName'];
		$this->adminPass = $options['adminPass'];
		$this->channel = $options['channel'];
		$this->initRequest();
		$this->loginAdmin();
	}

	/**
	 * @return array
	 */
	public function getLog() {
		return $this->log;
	}

	/**
	 * @param string $message
	 */
	public function send($title, $message) {
		$result = $this->sendRequest('https://api.cloud.appcelerator.com/v1/push_notification/notify.json', array(
			'channel' => $this->channel,
			'payload' => json_encode(array(
				'badge' => 1,
				'sound' => 'default',
				'alert' => $title,
				'title' => $message,
				'vibrate' => false
			))
		));
		$this->log[] = 'send(), result = ';
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
	 */
	protected function loginAdmin() {
		$result = $this->sendRequest('https://api.cloud.appcelerator.com/v1/users/login.json', array(
			'login' => $this->adminName,
			'password' => $this->adminPass
		));
		$this->log[] = 'loginAdmin(), result = ';
		$this->log[] = $result;
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
				CURLOPT_COOKIEFILE => $this->path . '/cookie.txt',
				CURLOPT_COOKIEJAR => $this->path . '/cookie.txt',
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
		$url = $url . '?key=' . $this->appKey;
		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
		$response = curl_exec($this->ch);
		$this->log[] = 'curl_error = '; $this->log[] = curl_error($ch);
		$this->log[] = 'curl_errno = '; $this->log[] = curl_errno($ch);
		$this->log[] = 'sendRequest(), response = ';
		$this->log[] = $response;
		$json = json_decode($response, true);
		$this->log[] = 'sendRequest(), json = ';
		$this->log[] = $json;
		if (is_array($json) && isset($json['meta']) && isset($json['response'])) {
			if ($json['meta']['status'] == 'ok') { // successful
				return true;
			}
		}
		return false;
	}

}
?>