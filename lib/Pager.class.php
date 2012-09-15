<?php

/**
 * Provide an HTML Pager
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Tools
 */
class Pager extends Object
{
	/**
	 * Effect how the new results will be displayed after an ajax call update the connect
	 */
	const AJAX_EFFECT_REPLACE = "replace"; //Just replace
	const AJAX_EFFECT_FADE = "fade"; //fade

	/**
	 * The complete entries
	 * @var int
	 */
	private $entries = 0;

	/**
	 * The current page
	 * @var int
	 */
	private $current_page = 0;

	/**
	 * The maximum shown entries per page
	 * @var int
	 */
	private $max_entries_per_page = 0;

	/**
	 * The maximum displayed elements in the middle of the pager
	 * @var int
	 */
	private $range = 10;

	/**
	 * This number determines how much pages we always show at the beginning
	 * @var int
	 */
	private $front_range = 3;

	/**
	 * This number determines how much pages we always show at the end
	 * @var int
	 */
	private $end_range = 3;

	/**
	 * The link template, this will be placed within href location or called within ajax request
	 */
	private $link_template = "";

	/**
	 * Wether this pager should call ajax requests or navigate to the page link
	 * @var boolean
	 */
	private $is_ajax = false;

	/**
	 * The replacement effekt, when an ajax results replace the container content
	 * @var string
	 */
	private $ajax_effect = "replace";

	/**
	 * This is a jquery selector for the container where ajax results will be replaced
	 * @var string
	 */
	private $ajax_replace_element = "";

	/**
	 * This post variable will be used for page requests
	 * @var string
	 */
	private $ajax_page_post_variable = "cpage";

	/**
	 * Construct
	 *
	 * @param int $max_entries_per_page
	 *   the max entries per page
	 * @param int $entries
	 *   the complete number of entries,
	 *   if not set yet we must set it before we call the assign_smarty method
	 *   (optional, default = 0)
	 * @param int $current_page
	 *   the current page (optional, default = null)
	 * @param string $link_template
	 *   the link template, this will be used for the href value you need to privde %page% which will be replaced with the current page (optional, default = null)
	 * @param string $page_variable
	 *   the page variable which will be used to determine the current page (optional, default = 'cpage')
	 */
 	public function __construct($max_entries_per_page, $entries = 0, $current_page = null, $link_template = null, $page_variable = 'cpage') {
		parent::__construct();
		$this->max_entries_per_page = $max_entries_per_page;
		$this->entries = $entries;

		//If we provided a link template, set this template
		if (!is_null($link_template)) {
			$this->link_template = $link_template;
		}
		else {
			//We have no link template so use the current one and apply cpage _GET parameter to the url
			$tmp_arr = array();
			foreach ($_GET AS $k => &$v) {
				$tmp_arr[md5($k)] = $k."=".$v;
			}
			$tmp_arr[md5($page_variable)] = $page_variable . "=%page%";
			$url = $_SERVER["REQUEST_URI"];
			$this->link_template = substr($url, 0, strpos($url, "?"))."?".implode("&", $tmp_arr);
		}
		//If current page provided use this
		if (!is_null($current_page)) {
			$this->current_page = (int)$current_page;
		}
		else {
			//IF cpage was found within the get request use this, else set it to 0
			$this->current_page = (!empty($_GET[$page_variable])) ? $_GET[$page_variable] : 0;
		}
	}

	/**
	 * Build up our pager and return the html (just the div container), The pager self will be build up with javascript
	 *
	 * @return string The Pager HTML
	 */
	public function build_pager() {
		if ($this->entries() <= $this->max_entries_per_page()) { //Return empty pager couse we do not reached our max entries per page
			return "";
		}
		$unique_id = uniqid();
		$pager_html = array("<div class=\"pager\" id=\"pager_".$unique_id."\">");


		$this->core->js_config("system_pager_ajax", array(
			'container' => $this->ajax_replace_element(),
			'link_template' => $this->link_template(),
			'current_page' => $this->current_page(),
			'post_variable' => $this->ajax_page_post_variable(),
			'effect' => $this->ajax_effect(),
			'range' => $this->range(),
			'end_range' => $this->end_range(),
			'front_range' => $this->front_range(),
			'entries' => $this->entries(),
			'max_entries_per_page' => $this->max_entries_per_page(),
			'uuid' => $unique_id,
			'is_ajax' => $this->is_ajax()
			), true);
		$pager_html[] = "</div>";
		return implode("\n", $pager_html);
	}

	/**
	 * Get the offset of the current page
	 *
	 * @return int the current offset
	 */
	public function get_offset() {
		return $this->current_page() * $this->max_entries_per_page();
	}

	/**
	 * Build up the pager HTML and assign it to Smarty variable $variable
	 *
	 * @param string $variable
	 *   The smarty variable
	 */
	public function assign_smarty($variable) {
		$this->smarty->assign_by_ref($variable, $this->build_pager());
	}

	/**
	 * Enables or Disable to use ajax as page callbacks or if no param provied return current value
	 *
	 * @param boolean $is_ajax
	 *   wether to set the is_ajax to true or false (optional, default = NS)
	 *
	 * @return mixed return null if we are in set mode, else return boolean
	 */
	public function is_ajax($is_ajax = NS) {
		if ($is_ajax === NS) {
			return $this->is_ajax;
		}
		$this->is_ajax = $is_ajax;
		return null;
	}

	/**
	 * Set or Get the entries
	 *
	 * @param int $entries
	 *   wether to set the entries (optional, default = NS)
	 *
	 * @return mixed return null if we are in set mode, else return int
	 */
	public function entries($entries = NS) {
		if ($entries === NS) {
			return $this->entries;
		}
		$this->entries = $entries;
		return null;
	}

	/**
	 * Set or Get the current_page
	 *
	 * @param int $current_page
	 *   wether to set or get the current_page (optional, default = NS)
	 *
	 * @return mixed return null if we are in set mode, else return int
	 */
	public function current_page($current_page = NS) {
		if ($current_page === NS) {
			return $this->current_page;
		}
		$this->current_page = $current_page;
		return null;
	}

	/**
	 * Set or Get the max_entries_per_page
	 *
	 * @param int $max_entries_per_page
	 *   wether to set or get the max_entries_per_page (optional, default = NS)
	 *
	 * @return mixed return null if we are in set mode, else return int
	 */
	public function max_entries_per_page($max_entries_per_page = NS) {
		if ($max_entries_per_page === NS) {
			return $this->max_entries_per_page;
		}
		$this->max_entries_per_page = $max_entries_per_page;
		return null;
	}

	/**
	 * Set or Get the range
	 *
	 * @param int $range wether
	 *   to set or get the range  (optional, default = NS)
	 *
	 * @return mixed return null if we are in set mode, else return int
	 */
	public function range($range = NS) {
		if ($range === NS) {
			return $this->range;
		}
		$this->range = $range;
		return null;
	}

	/**
	 * Set or Get the front_range
	 *
	 * @param int $front_range
	 *   wether to set or get the front_range (optional, default = NS)
	 *
	 * @return mixed return null if we are in set mode, else return int
	 */
	public function front_range($front_range = NS) {
		if ($front_range === NS) {
			return $this->front_range;
		}
		$this->front_range = $front_range;
		return null;
	}

	/**
	 * Set or Get the end_range
	 *
	 * @param int $end_range wether
	 *   to set or get the end_range (optional, default = NS)
	 *
	 * @return mixed return null if we are in set mode, else return int
	 */
	public function end_range($end_range = NS) {
		if ($end_range === NS) {
			return $this->end_range;
		}
		$this->end_range = $end_range;
		return null;
	}

	/**
	 * Set or Get the link_template
	 *
	 * @param string $link_template
	 *   wether to set or get the link_template (optional, default = NS)
	 *
	 * @return mixed return null if we are in set mode, else return string
	 */
	public function link_template($link_template = NS) {
		if ($link_template === NS) {
			return $this->link_template;
		}
		$this->link_template = $link_template;
		return null;
	}

	/**
	 * Set or Get the ajax_effect
	 * the effect after the page content is recieved
	 *
	 * @param string $ajax_effect
	 *   wether to set or get  the ajax_effect (optional, default = NS)
	 *
	 * @return mixed return null if we are in set mode, else return string
	 */
	public function ajax_effect($ajax_effect = NS) {
		if ($ajax_effect === NS) {
			return $this->ajax_effect;
		}
		$this->ajax_effect = $ajax_effect;
		return null;
	}

	/**
	 * Set or Get the ajax_replace_element
	 * the html replace element
	 *
	 * @param string $ajax_replace_element
	 *   wether to set or get the ajax_replace_element (optional, default = NS)
	 *
	 * @return mixed return null if we are in set mode, else return string
	 */
	public function ajax_replace_element($ajax_replace_element = NS) {
		if ($ajax_replace_element === NS) {
			return $this->ajax_replace_element;
		}
		$this->ajax_replace_element = $ajax_replace_element;
		return null;
	}

	/**
	 * Set or Get the ajax_page_post_variable
	 * ajax_page_post_variable is the post variable which will be send to the ajax callback
	 * @param string $ajax_page_post_variable
	 *   wether to set or get the ajax_page_post_variable (optional, default = NS)
	 *
	 * @return mixed return null if we are in set mode, else return string
	 */
	public function ajax_page_post_variable($ajax_page_post_variable = NS) {
		if ($ajax_page_post_variable === NS) {
			return $this->ajax_page_post_variable;
		}
		$this->ajax_page_post_variable = $ajax_page_post_variable;
		return null;
	}

}

