<?php

namespace Controls\ReCaptcha;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class Service extends \Nette\Object implements IService {

	const API_SERVER = "http://www.google.com/recaptcha/api";
	const API_SECURE_SERVER = "https://www.google.com/recaptcha/api";
	const VERIFY_SERVER = "www.google.com";

	private $useSsl = false;
	private $privateKey;
	private $publicKey;
	private $remoteIp;

	function __construct($privateKey, $publicKey) {
		$this->privateKey = $privateKey;
		$this->publicKey = $publicKey;
		$this->remoteIp = $_SERVER["REMOTE_ADDR"];
	}

	public function setUseSsl($useSsl) {
		$this->useSsl = $useSsl;
	}

	public function setRemoteIp($remoteIp) {
		$this->remoteIp = $remoteIp;
	}

	protected function getServer() {
		return ($this->useSsl) ? self::API_SECURE_SERVER : self::API_SERVER;
	}

	public function getJavascriptSrc($error = null) {
		if ($this->publicKey == null || $this->publicKey == '')
			throw new \Exception("To use reCAPTCHA you must get an API key from <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a>");

		$errorpart = ($error === null) ? "" : "&amp;error=" . $error;
		return $this->getServer() . '/challenge?k=' . $this->publicKey . $errorpart;
	}

	public function getIframeSrc($error = null) {
		if ($this->publicKey == null || $this->publicKey == '')
			throw new \Exception("To use reCAPTCHA you must get an API key from <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a>");

		$errorpart = ($error === null) ? "" : "&amp;error=" . $error;
		return $this->getServer() . '/noscript?k=' . $this->publicKey . $errorpart;
	}

	/**
	 * Calls an HTTP POST function to verify if the user's guess was correct
	 * @param string $challenge
	 * @param string $response
	 * @return Response
	 */
	function getResponse($challenge, $response) {
		if ($this->privateKey == null || $this->privateKey == '')
			throw new \Exception("To use reCAPTCHA you must get an API key from <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a>");

		if ($this->remoteIp == null || $this->remoteIp == '')
			throw new \Exception("For security reasons, you must pass the remote ip to reCAPTCHA");

		//discard spam submissions
		if ($challenge == null || strlen($challenge) == 0 || $response == null || strlen($response) == 0)
			return new Response((string) $challenge, false, 'incorrect-captcha-sol');

		$response = $this->httpPost(self::VERIFY_SERVER, "/recaptcha/api/verify", array(
			'privatekey' => $this->privateKey,
			'remoteip' => $this->remoteIp,
			'challenge' => $challenge,
			'response' => $response
				));

		$answers = explode("\n", $response[1]);

		if (trim($answers[0]) == 'true')
			return new Response($challenge, true);
		else
			return new Response($challenge, false, $answers[1]);
	}

	/**
	 * Submits an HTTP POST to a reCAPTCHA server
	 * @param string $host
	 * @param string $path
	 * @param array $data
	 * @param int port
	 * @return array response
	 */
	protected function httpPost($host, $path, $data, $port = 80) {

		$req = $this->qsenCode($data);

		$http_request = "POST $path HTTP/1.0\r\n";
		$http_request .= "Host: $host\r\n";
		$http_request .= "Content-Type: application/x-www-form-urlencoded;\r\n";
		$http_request .= "Content-Length: " . strlen($req) . "\r\n";
		$http_request .= "User-Agent: reCAPTCHA/PHP\r\n";
		$http_request .= "\r\n";
		$http_request .= $req;

		$response = '';
		if (false == ( $fs = @fsockopen($host, $port, $errno, $errstr, 10) )) {
			throw new \Exception('Could not open socket');
		}

		fwrite($fs, $http_request);

		while (!feof($fs))
			$response .= fgets($fs, 1160); // One TCP-IP packet
		fclose($fs);
		$response = explode("\r\n\r\n", $response, 2);

		return $response;
	}

	/**
	 * Encodes the given data into a query string format
	 * @param $data - array of string elements to be encoded
	 * @return string - encoded request
	 */
	protected function qsenCode($data) {
		$req = "";
		foreach ($data as $key => $value)
			$req .= $key . '=' . urlencode(stripslashes($value)) . '&';

		// Cut the last '&'
		$req = substr($req, 0, strlen($req) - 1);
		return $req;
	}

}