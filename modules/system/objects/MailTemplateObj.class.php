<?php
/**
 * This object represents an email template which will be used by send_tpl from Email
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category ModelObjects
 */
class MailTemplateObj extends AbstractDataManagment
{
	/**
	 * Define constances
	 */
	const TABLE = 'core_message_templates';

	/**
	 * Constructor
	 *
	 * @param string $id
	 *   the message template id (optional, default = '')
	 * @param string $language
	 *   the language (optional, default = 'en')
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($id = "", $language = 'en', $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(false);
		$this->db_struct->add_reference_key(array("id", 'language'));

		$this->db_struct->add_field("id", t('Message ID'), PDT_STRING, '', 120);
		$this->db_struct->add_field("language", t("Language"), PDT_STRING, '', 3);
		$this->db_struct->add_field("subject", t("The subject string"), PDT_STRING);
		$this->db_struct->add_field("body", t("The body string"), PDT_TEXT);
		if (!empty($id) && !empty($language)) {
			$this->load(array($id, $language), $force_db);
		}
	}

	/**
	 * Returns all available templates
	 *
	 * @return array the template ids
	 */
	public function get_mail_template_ids() {
		static $values = null;

		if ($values == null) {
			$values = DatabaseFilter::create(self::TABLE)
				->add_column('id')
				->group_by('id')
				->order_by('id')
				->select_all('id', true);
		}

		return $values;
	}

	/**
	 * Load the template if not already and replace all given key with the respective values
	 *
	 * @param array $tplvars
	 *   the template variable as an array in (key => value) the key is just the key without surrounding {}
	 */
	public function parse(Array $tplvars) {
		foreach ($tplvars AS $k => $v) {
			if (is_array($v)) {
				continue;
			}
			$this->subject = str_replace("{".$k."}", $v, $this->subject);
			$this->body = str_replace("{".$k."}", $v, $this->body);
		}
	}
}

