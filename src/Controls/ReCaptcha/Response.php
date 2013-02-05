<?php

namespace Controls\ReCaptcha;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class Response extends \Nette\Object {

	private $challenge;
	private $valid;
	private $error;

	function __construct($challenge, $valid, $error = null) {
		$this->challenge = $challenge;
		$this->valid = $valid;
		$this->error = $error;
	}

	public function getChallenge() {
		return $this->challenge;
	}

	public function isValid() {
		return $this->valid;
	}

	public function getError() {
		return $this->error;
	}

}

