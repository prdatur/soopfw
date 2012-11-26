<?php

/**
 * Provides a HTML-Submitbutton which cancels the current dialog.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form.Inputs
 */
class CancelButton extends Submitbutton
{

	/**
	 * Constructor
	 *
	 * @param string $cancel_url
	 *   The url where the user will be redirected if he submit this cancel button.
	 *   If you provide no cancel url and the request type is not html the cancel ajax return code will be sended.
	 *   (optional, default = '')
	 * @param string $name
	 *   The input name. (optional, default = 'btn_cancel')
	 * @param string $value
	 *   The value for this input. If not provided it will be set to t('Cancel'). (optional, default='')
	 * @param string $class
	 *   The input css class. (optional, default = '')
	 * @param string $id
	 *   The input id. (optional, default = '')
	 */
	public function __construct($cancel_url = '', $name = 'btn_cancel', $value = '', $class = '', $id = '') {
		if (empty($value)) {
			$value = t('Cancel');
		}
		parent::__construct($name, $value, $class, $id);

		if ($this->is_submitted()) {
			if ($this->core->init_type != Core::INIT_TYPE_HTML) {
				if (empty($cancel_url)) {
					WebAction::close_dialog();
				}

				AjaxModul::return_code(AjaxModul::SUCCESS_REDIRECT, $cancel_url);
			}
			else {
				if (empty($cancel_url)) {
					$cancel_url = '/';
				}
				$this->core->location($cancel_url);
			}
		}
	}

}