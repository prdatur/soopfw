<?php
/**
 * This object represents an email template which will be used by send_tpl from Email
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.system.objects
 * @category ModelObjects
 */
class MailTemplateObj extends MessageTemplateObj
{

	/**
	 * The mail subject
	 *
	 * @var string
	 */
	public $subject = "";

	/**
	 * The mail body text
	 *
	 * @var string
	 */
	public $body = "";

	/**
	 * Constructor
	 *
	 * @param string $id 
	 *   the mail template id (optional, default = "")
	 * @param boolean $force_db 
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	function __construct($id = "", $force_db = false) {
		parent::__construct($id, $force_db);
	}


	/**
	 * Load the template if not already and replace all given key with the respective values
	 * 
	 * @param array $tplvars 
	 *   the template variable as an array in (key => value) the key is just the key without surrounding {}
	 */
	public function parse(Array $tplvars) {
		$this->get_template_data();
		foreach ($tplvars AS $k => $v) {
			if (is_array($v)) {
				continue;
			}
			$this->subject = str_replace("{".$k."}", $v, $this->subject);
			$this->body = str_replace("{".$k."}", $v, $this->body);
		}
	}

	/**
	 * Setup the subject and body text from the given template
	 */
	private function get_template_data() {
		if (!empty($this->template)) {
			if (preg_match("/\[subject\](.*)\[\/subject\]/iUs", $this->template, $matches)) {
				$this->subject = $matches[1];
			}
			if (preg_match("/\[body\](.*)\[\/body\]/iUs", $this->template, $matches)) {
				$this->body = $matches[1];
			}
		}
	}

}

?>