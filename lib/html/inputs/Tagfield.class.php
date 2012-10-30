<?php

/**
 * Provides a HTML-Tagfield
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form.Inputs
 */
class Tagfield extends Textfield
{

	/**
	 * The autocomplete source.
	 *
	 * @var mixed
	 */
	private $source = null;

	/**
	 * The minimum length a value must be before autocomplete starts
	 * @var int
	 */
	private $min_length = 0;
	/**
	 * constructor
	 *
	 * @param string $name
	 *   the input name
	 * @param string $value
	 *   the value for this input (optional, default='')
	 * @param string $label
	 *   the input label (optional, default='')
	 * @param string $description
	 *   the input description (optional, default = '')
	 * @param mixed $source
	 *   can be an array (value => lable or just value) which are static elements or an ajax callback url (GET variable = term) (optional, default = '')
	 * @param int $min_length
	 *   The minimum length a value must be before autocomplete starts (optional, default = 2)
	 * @param string $class
	 *   the input css class (optional, default = '')
	 * @param string $id
	 *   the input id (optional, default = '')
	 */
	public function __construct($name, $value = '', $label = '', $description = '', $source = '', $min_length = 2, $class = "", $id = '') {
		$this->min_length = $min_length;
		$this->source = $source;

		parent::__construct($name, $value, $label, $description, $class, $id);
	}

	/**
	 * init the input
	 */
	public function init() {
		$this->core->add_js('/js/jquery_plugins/jquery.ui.tag-it.js');
		$this->core->add_css('/css/jquery_soopfw/jquery.tagit.css');
		$this->config_array("css_class", "Tagfield");
		$other = "";
		if ($this->source != null) {
			if (is_array($this->source)) {
				$this->core->js_config('taginput_source_' . $this->config('id'), $this->source);
				$other .= ' source_id="' . $this->config('id') . '"';
			}
			else {
				$other .= 'autocomplete_source="' . $this->source . '" ';
			}

			$other .= ' autocomplete_min_length="' . $this->min_length . '"';

			$this->config('other', $other);
		}
		parent::init();
	}

}

