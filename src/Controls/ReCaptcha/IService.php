<?php

namespace Controls\ReCaptcha;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
interface IService {

	function getResponse($challenge, $response);

	function getJavascriptSrc($error);
}