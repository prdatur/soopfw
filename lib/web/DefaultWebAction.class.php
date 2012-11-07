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
		$template = $this->smarty->get_tpl(true) . 'frontpage.tpl';

		/**
		 * Provides hook: frontpage
		 *
		 * Allow other modules to do things before the frontpage is shown. also a module can change the static template file
		 * to display the specific frontpage template.
		 *
		 * @param string &$template
		 *   The template path which will be used.
		 */
		$this->core->hook('frontpage', array(&$template));
		if (!file_exists($template)) {
			throw new SoopfwErrorException(t('Frontpage can not be determined'));
		}
		$this->static_tpl = $template;
	}
}