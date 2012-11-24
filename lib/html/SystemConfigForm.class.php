<?php

/**
 * Provide a HTML-Form handler which automaticly stores the given value within the database
 * the module key is the modulname provided by the action_module
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form
 */
class SystemConfigForm extends Form
{

	/**
	 * The action module for the configuration
	 * @var ActionModul
	 */
	private $action_module = null;

	/**
	 * Constructor
	 *
	 * @param ActionModul $action_module
	 *   the action module for which the config is.
	 * @param string $form_name
	 *   the form name used for smarty
	 * @param string $title
	 *   The title for this form, this must be a translated string, it will not translate. (optional, default = '')
	 * @param string $submit
	 *   if a post element with this string exist the form is submitted,
	 *   can be set to "auto" as a special string that will setup a unique hidden submit handler field (optional, default = 'auto')
	 * @param string $is_post
	 *   Set to false if you do not want to check the $submit value against the $_POST value,
	 *   only check if $submit is not empty (optional, default = true)
	 */
 	public function __construct(ActionModul &$action_module, $form_name, $title = '', $submit = 'auto', $is_post = true) {
		parent::__construct($form_name, $title, $submit, $is_post);

		//Set the action module
		$this->action_module = $action_module;

		//We only want a form template so override the action module template
		$this->action_module->static_tpl = "form.tpl";
	}

	/**
	 * Checks if the form was submitted and valid, if so it will save the configurations into the database
	 * for the specified module
	 *
	 * @param mixed $user_callback_function
	 *   if provided the function will be called after success (optional, default = "")
	 *
	 * @return boolean true if form is submitted and valid or not
	 */
	public function execute($user_callback_function = "") {

		//Add a submit button
		$this->add(new Submitbutton("saveconfig", t("Save Config")));

		//Assign form to smarty
		$this->assign_smarty();

		//Check if form is valid (does not return anything but should always be called manually)
		$this->check_form();

		//Whether the form is submit and valid
		if ($this->is_submitted() && $this->is_valid()) {
			//Save values on valid form
			foreach ($this->get_values() AS $k => $v) {
				if ($this->elements[self::ELEMENT_SCOPE_VISIBLE][$k] instanceof Fieldset) {
					continue;
				}

				if ($this->elements[self::ELEMENT_SCOPE_VISIBLE][$k] instanceof Filefield) {
					/* @var $filefield Filefield */
					$filefield = $this->elements[self::ELEMENT_SCOPE_VISIBLE][$k];
					$file = $filefield->get_current_file();
					if (!empty($file) && empty($v)) {
						$file->delete();
					}
				}

				$this->core->dbconfig($this->action_module->modulname, $k, $v);
			}

			$this->core->message(t("Configuration saved"), Core::MESSAGE_TYPE_SUCCESS);
			if (!empty($user_callback_function)) {
				call_user_func($user_callback_function, true);
			}
			return true;
		}

		return false;
	}

}


