<?php
namespace Controls\ReCaptcha;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class ReCaptcha extends \Nette\ComponentModel\Component implements \Nette\Forms\IControl {
	
	const RESPONSE_FIELD = "recaptcha_response_field";
	const CHALLENGE_FIELD = "recaptcha_challenge_field";

	/**
	 * @var Service
	 */
	protected $service = null;

	/**
	 * @var \Nette\Forms\Rules
	 */
	protected $rules;

	/**
	 * @var Components\ReCaptcha\Response
	 */
	protected $response;

	/**
	 * @var array user options 
	 */
	private $options = array();

	/**
	 * @var array text errors
	 */
	private $errors = array();

	/**
	 * @var string caption [unused]
	 */
	private $caption = "Captcha";

	/**
	 * @param IService $service
	 * @param string $errorText 
	 */
	function __construct(IService $service, $errorText) {
		$this->service = $service;
		$this->rules = new \Nette\Forms\Rules($this);
		$this->rules->addRule(callback($this, "isValid"), $errorText);
	}

	/**
	 * Is current response valid?
	 * @return bool
	 */
	public function isValid() {
		return $this->getResponse()->isValid();
	}

	/**
	 * Get service
	 * @return IService
	 */
	public function getService() {
		return $this->service;
	}

	/**
	 * Get response
	 * @return Response
	 */
	public function getResponse() {
		if ($this->response === null)
			$this->loadHttpData();
		return $this->response;
	}

	/**
	 * Caption of control
	 * @return string
	 */
	public function getCaption() {
		return $this->caption;
	}

	/**
	 * Caption of control
	 * @param string $caption 
	 */
	public function setCaption($caption) {
		$this->caption = $caption;
	}

	/**
	 * Returns form.
	 * @param  bool   throw exception if form doesn't exist?
	 * @return Nette\Forms\Form
	 */
	public function getForm($need = TRUE) {
		return $this->lookup('Nette\Forms\Form', $need);
	}

	/**
	 * Loads HTTP data.
	 * @return void
	 */
	public function loadHttpData() {
		$challenge = \Nette\Utils\Arrays::get($this->getForm()->getHttpData(), self::CHALLENGE_FIELD, NULL);
		$response = \Nette\Utils\Arrays::get($this->getForm()->getHttpData(), self::RESPONSE_FIELD, NULL);
		$this->response = $this->service->getResponse($challenge, $response);
	}

	/**
	 * Sets control's value.
	 * @param  mixed
	 * @return void
	 */
	public function setValue($value) {
		throw new \Nette\InvalidStateException("Can't set value of reCaptcha !");
	}

	/**
	 * Returns control's value.
	 * @return mixed
	 */
	public function getValue() {
		return $this->getResponse()->getChallenge();
	}

	/**
	 * @return Rules
	 */
	public function getRules() {
		return $this->rules;
	}

	/**
	 * Returns errors corresponding to control.
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Is control disabled?
	 * @return bool
	 */
	public function isDisabled() {
		return false;
	}

	/**
	 * Returns translate adapter.
	 * @return Nette\Localization\ITranslator|NULL
	 */
	public function getTranslator() {
		return $this->getForm(FALSE) ? $this->getForm()->getTranslator() : NULL;
	}

	/**
	 * Returns translated string.
	 * @param  string
	 * @param  int      plural count
	 * @return string
	 */
	public function translate($s, $count = NULL) {
		$translator = $this->getTranslator();
		return $translator === NULL || $s == NULL ? $s : $translator->translate($s, $count); // intentionally ==
	}

	/**
	 * Sets user-specific option.
	 * @param  string key
	 * @param  mixed  value
	 * @return BaseControl  provides a fluent interface
	 */
	public function setOption($key, $value) {
		if ($value === NULL) {
			unset($this->options[$key]);
		} else {
			$this->options[$key] = $value;
		}
		return $this;
	}

	/**
	 * Returns user-specific option.
	 * @param  string key
	 * @param  mixed  default value
	 * @return mixed
	 */
	public function getOption($key, $default = NULL) {
		return isset($this->options[$key]) ? $this->options[$key] : $default;
	}

	/**
	 * Returns user-specific options.
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}

	public function isRequired() {
		return true;
	}

	public function getLabelPrototype() {
		return \Nette\Utils\Html::el();
	}
	
	public function getControlPrototype() {
		return \Nette\Utils\Html::el();
	}
	
	public function getControl() {
		$this->setOption('rendered', TRUE);

		$error = $this->getForm()->isSubmitted() ? $this->getResponse()->getError() : null;
		
		$jsSrc = $this->service->getJavascriptSrc($error);
		
		return \Nette\Utils\Html::el("script")->type("text/javascript")->src($jsSrc);
//		$iframeSrc = $this->service->getIframeSrc($error);

//		$container = Html::el();
//		
//		$container->add(Html::el("script")->type("text/javascript")->src($jsSrc));
//		
//		$noscript = Html::el("noscript");
//		$noscript->add(Html::el("iframe")->src($iframeSrc)->height(300)->width(500)->frameborder(0));
//		$noscript->add(Html::el("br"));
//		$noscript->add(Html::el("textarea")->name(self::CHALLENGE_FIELD)->rows(3)->cols(40));
//		$noscript->add(Html::el("hidden")->name(self::RESPONSE_FIELD)->value("manual_challenge"));
//		$container->add($noscript);
		
//		return $container;
	}

	/**
	 * Get (empty) label of string
	 * @return Html
	 */
	public function getLabel() {
		return \Nette\Utils\Html::el("");
	}

	/**
	 * Get errors of current control
	 * @param string $message 
	 */
	public function addError($message) {
		if (!in_array($message, $this->errors, TRUE)) {
			$this->errors[] = $message;
		}
		$this->getForm()->addError($message);
	}

	// -------------------------------------------------------------------------
	
	/** @var IService */
	protected static $globalService = null;
	
	/** @var string */
	protected static $globalErrorText = null;
	
	public static function Container_prototype_addReCaptcha(\Nette\Forms\Container $obj, $service = null, $errorText = null) {
		if($service === null && self::$globalService === null) {
			throw new \Nette\InvalidStateException('Service is null!');
		}
		if($errorText === null && self::$globalErrorText === null) {
			throw new \Nette\InvalidStateException('Error text is null!');
		}
		return $obj["reCaptcha"] = new ReCaptcha($service === null ? self::$globalService : $service, $errorText === null ? self::$globalErrorText : $errorText);
	}

	public static function register($service = null, $methodName = "addCaptcha", $errorText = "Opište správně captchu prosím.") {
		self::$globalService = $service;
		self::$globalErrorText = $errorText;
		\Nette\Forms\Container::extensionMethod($methodName, "Controls\ReCaptcha\ReCaptcha::Container_prototype_addReCaptcha");
	}	
	
}