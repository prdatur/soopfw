<?php
/**
 * Provide a Captcha security field
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 * @category Form.Inputs
 */
require(SITEPATH . "/plugins/recaptchalib.php");

class Captcha extends AbstractHtmlInput
{

	/**
	 * The private key
	 *
	 * @var string
	 */
	private $privatekey = "6LfhidQSAAAAAGhXGrsmAsCAaHhxwLhMO8EAgJ6Q";

	/**
	 * The public key
	 *
	 * @var string
	 */
	private $publickey = "6LfhidQSAAAAAAOYoYmolqHLdQY2bxlqrUvx2mOT";

	/**
	 * constructor
	 *
	 * @param string $label
	 *   the input label (optional, default='')
	 * @param string $description
	 *   the input description (optional, default = '')
	 * @param string $publickey
	 *   The public key (optional, default = '')
	 * @param string $privatekey
	 *   The private key (optional, default = '')
	 */
 	public function __construct($label = '', $description = '', $publickey = '', $privatekey = '') {
		parent::load_core();

		//Set the label for the element, if $label not provided use $name instead
		$this->config("label", $label);

		//It can happen that an input will override config('label') so to have the original label store it
		$this->config("orig_label", (empty($label)) ? '' : $label);

		//Set the element description
		$this->config("description", $description);

		if (empty($privatekey) && empty($publickey)) {
			$privatekey = $this->core->dbconfig('system', system::CONFIG_RECAPTCHA_PRIVATE_KEY);
			$publickey = $this->core->dbconfig('system', system::CONFIG_RECAPTCHA_PUPLIC_KEY);
		}
		if (!empty($privatekey) && !empty($publickey)) {
			$this->privatekey = $privatekey;
			$this->publickey = $publickey;
		}
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
		return array(t("You have entered a wrong captcha, please try again."));
	}

	/**
	 * Init the captcha filter
	 */
	public function init() {
		$this->config("template", recaptcha_get_html($this->publickey));
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

