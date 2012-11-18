<?php

/**
 * Provides a HTML-Selectfield which will direct filled with all available
 * email templates, it provides also the possiblity to direct add a template and use it.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @module System
 * @category Module
 */
class EmailTemplateSelectField extends Selectfield {

	/**
	 * Construct
	 *
	 * @param string $name
	 *   the input name
	 * @param array $available_variables
	 *   the array values will determine the available variables within this
	 *   email template (optional, default = array())
	 * @param array $options
	 *   the options as array(key => value, ...)
	 * @param string $selected
	 *   the selected key (optional, default = '')
	 * @param string $label
	 *   the input label (optional, default='')
	 * @param string $description
	 *   the input description (optional, default = '')
	 * @param string $class
	 *   the input css class (optional, default = '')
	 * @param string $id
	 *   the input id (optional, default = '')
	 */
 	public function __construct($name, $available_variables = array(), $selected = "", $label = "", $description = "", $class = "", $id = "") {
		$tpls = new MailTemplateObj();
		//Call normal construct but provide an empty value
		parent::__construct($name, $tpls->get_mail_template_ids(), $selected, $label, $description, $class, $id);

		if ($this->right_manager->has_perm('admin.system.config')) {
			$this->core->add_js('/modules/system/js/email_template_selector.js', Core::JS_SCOPE_USER, true);
			$this->config_array('css_class', 'email_template_selector');
			$this->core->js_config('system_email_template_available_variables', implode(",", $available_variables), true, $name);
		}
	}

	/**
	 * Returns the label field if label configuration is not empty
	 *
	 * @return string the label html code
	 */
	public function get_label() {
		$label = $this->config("label|pure");

		$create_template = $change_template = "";

		// If we have the permission to configurate the email templates, display direct edit buttons.
		if ($this->right_manager->has_perm('admin.system.config')) {
			$create_template = '<br/> <div did="' . $this->config("id") . '" class="form_button system_create_email_template">' . t('Create a new template') . '</div>';
			$change_template = '<div did="' . $this->config("id") . '" class="form_button system_change_email_template">' . t('Change selected template') . '</div>';
		}
		$output = '<div class="form-element-label"><label for="'.$this->config("id").'">';
		if(!empty($label)) {
			$output .= ''.$this->config("label").':</label> ';
		}
		return $output . $create_template . $change_template . '</div>';
	}
}

