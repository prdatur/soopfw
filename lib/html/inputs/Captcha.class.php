<?php
/**
 * Provide a Captcha security field
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 */
require("plugins/recaptchalib.php");

class Captcha extends AbstractHtmlInput
{

	/**
	 * The private key
	 *
	 * @var string
	 */
	private $privatekey;

	/**
	 * The public key
	 *
	 * @var string
	 */
	private $publickey;

	/**
	 * constructor
	 *
	 * @param string $publickey The public key
	 * @param string $privatekey The private key
	 */
	function __construct($publickey, $privatekey) {
		$this->privatekey = $privatekey;
		$this->publickey = $publickey;
		$this->init();
	}

	/**
	 * Checks if all Validators are valid
	 *
	 * @return boolean on success true, else false
	 */
	function is_valid() {
		//Check if the provided captcha code matched the image
		$resp = recaptcha_check_answer($this->privatekey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
		return $resp->is_valid;
	}

	/**
	 * Get all errors after checking against validator
	 *
	 * @return array the errors
	 */
	public function get_errors() {
		return array("");
	}

	/**
	 * Init the captcha filter
	 */
	public function init() {
		$this->config("template", "forms/captcha.tpl");
		$this->config("type", "captcha");
	}

	/**
	 * Get Templates vars
	 *
	 * @return array Addidtional template vars
	 */
	public function get_tpl_vars() {
		$conf['pubkey'] = $this->publickey;
		return $conf;
	}

}

?>