<?php

/**
 * Provide a checkbox container where the configured column count will be used to place the checkbox's side by side.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 * @category Form.Inputs
 */
class ColumnCheckboxes extends Checkboxes
{

	/**
	 * The max. column count.
	 *
	 * @var int
	 */
	private $column_count = 0;

	/**
	 * Construct
	 *
	 * @param string $name
	 *   The main name for this checkbox container (the checkboxes will inhire the name)
	 * @param array $values
	 *   The values as an array in format ('value' => 'label') for every checkbox
	 * @param array $default_value
	 *   The default values to preselect checkboxes if needed, keys are not used,
	 *   provide just values with the key names from $values to be preselected
	 *   like array('yes') to preselect yes element (optional, default = array())
	 * @param int $column_count
	 *   the max column count (optional, default = 4)
	 * @param string $label
	 *   the input label (optional, default='')
	 * @param string $description
	 *   the input description (optional, default = '')
	 * @param boolean $include_empty_values
	 *   Set to true if you want also empty 'not posted' keys within the returning output (optional, default = false)
	 * @param string $class
	 *   the input css class (optional, default = '')
	 * @param string $id
	 *   the input id (optional, default = '')
	 */
	public function __construct($name, Array $values, Array $default_value = array(), $column_count = 4, $label = '', $description = '', $include_empty_values = false, $class = '', $id = '') {
		parent::__construct($name, $values, $default_value, $label, $description, $include_empty_values, $class, $id);
		$this->column_count = $column_count;
	}

	/**
	 * Returns the HTML-Code string for the element
	 * It will get all checkbox elements and concate the elements
	 *
	 * @return string the HTML code for the element
	 */
	public function fetch() {
		//first the the label string if not empty
		$output = $this->get_label();

		//Provide a suffix which we can configurate
		$suffix = $this->config("suffix");
		if(!empty($suffix)) {
			$output .= $suffix;
		}

		$clearfix = "";
		$i = 0;

		if (count($this->fields) > 0) {
			$output .= '<table><tr>';
			//Loop through all inputs and append the fetched element html string to our returning string
			foreach ($this->fields as &$field) {
				$this->init();

				$output .= ($i != 0 && $i%$this->column_count == 0) ? "\n</tr><tr>\n" : '';
				$output .= '<td><div class="' . $this->config('type') . '">' . $field->fetch() . '</div></td>';
				$i++;
			}

			$colspan = ($this->column_count-($i%$this->column_count));
			if ($colspan > 0) {
				$output .= '<td colSpan=' . $colspan . '></td>';
			}
			$output .= '</tr></table>';
		}
		//Append the main input template string and the followed description
		$output .= $this->get_description();
		return $output;
	}
}

