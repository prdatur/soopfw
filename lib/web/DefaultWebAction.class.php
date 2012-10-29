<?php

/**
 * Provides a class to handle the default page.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Web
 */
class DefaultWebAction extends ActionModul
{

	//Default method
	protected $default_methode = 'display';

	/**
	 * Display the default page.
	 */
	public function display() {
		$template = 'frontpage.tpl';
		if (!file_exists($this->smarty->get_tpl(true) . $template)) {
			throw new SoopfwErrorException(t('Frontpage can not be determined'));
		}

		/**
		 * Provides hook: frontpage
		 *
		 * Allow other modules to do things before the frontpage is shown.
		 */
		$this->core->hook('frontpage');

		$this->static_tpl = $template;
	}
}