<?php

namespace Controls\ReCaptcha;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class DummyService extends \Nette\Object implements IService {

	function getResponse($challenge, $response) {
		return new Response(null, true, null);
	}

	function getJavascriptSrc($error) {
		return "";
	}

}
