<?php

/**
 * Provide a HTML-Tab
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form
 */
class HtmlTabs extends AbstractHtmlElement
{

	/**
	 * The Container id
	 *
	 * @var string
	 */
	public $id = "";

	/**
	 * Container for all tabs
	 *
	 * @var array
	 */
	public $tabs = array();

	/**
	 * The transition effect if a tab is changed
	 *
	 * @var string
	 */
	public $effect = "";

	/**
	 * Construct
	 *
	 * @param string $id 
	 *   The container id
	 */
 	public function __construct($id) {
		parent::__construct();
		$this->id = $id;
	}

	/**
	 * Assign the form to smarty.
	 * It will also assign a javascript configuration key that can jquery use
	 * to laod the tabs (provide the id and effect)
	 *
	 * @param string $name 
	 *   the smarty variable
	 */
	public function assign_smarty($name) {
		$this->core->js_config("load_tabs", array(
			'id' => $this->id,
			'effect' => $this->effect,
			), true);
		parent::assign_smarty($name);
	}

	/**
	 * Adds a tab to this container
	 *
	 * @param string $title 
	 *   the title of the tab
	 * @param string $name 
	 *   the internal name
	 * @param string $link 
	 *   the link to navigate
	 * @param string $right 
	 *   the right which is needed to show this tab (optional, default = '')
	 */
	public function add($title, $name, $link, $right = "") {

		/**
		 * If the link ends not with the specified extension we append .ajax_html so that the content is loaded with
		 * the ajax template
		 */

		if (!preg_match("/(\.php|\.html|\.htm|\.jpeg|\.jpg|\.gif|\.png|\.js|\.css)$/is", $link, $matches)) {
			$link .= ".ajax_html";
		}

		//Check if we provide a required right, if yes check the right against the current user, if not allowed we do not add the tab
		if (!empty($right) && !$this->right_manager->has_perm($right)) {
			return;
		}

		//Add the tab
		$this->tabs[] = array(
			'title' => $title,
			'link' => $link,
			'name' => $name
		);
	}

}

