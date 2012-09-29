<?php

/**
 * Provides a class to parse forms.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.tests
 * @category Testing
 */
class UnitTestFormParser
{
	/**
	 * Holds all found forms and there elements.
	 * @var array
	 */
	protected $forms = array();

	/**
	 * Checks whether the form exist or not.
	 *
	 * @param string $form_id
	 *   the form id.
	 *
	 * @return boolean returns true if given form exist, else false
	 */
	public function form_exist($form_id) {
		return isset($this->forms[$form_id]);
	}

	/**
	 * Returns the form if it exists.
	 *
	 * @param string $form_id
	 *   the form id.
	 *
	 * @return array Returns an array with all form elements, if form does not exist it returns boolean false
	 */
	public function get_form($form_id) {
		if (!isset($this->forms[$form_id])) {
			return false;
		}
		return $this->forms[$form_id];
	}

	/**
	 * Checks whether the form field exist or not.
	 *
	 * @param string $form_id
	 *   the form id.
	 * @param string $field_id
	 *   the form field id.
	 *
	 * @return boolean returns true if given form field exist, else false
	 */
	public function form_field_exist($form_id, $field_id) {
		return isset($this->forms[$form_id]) && isset($this->forms[$form_id][$field_id]);
	}

	/**
	 * Returns the form field if it exists.
	 *
	 * @param string $form_id
	 *   the form id.
	 * @param string $field_id
	 *   the form field id.
	 *
	 * @return array Returns an array with all form field tags, if form or form field does not exist it returns boolean false
	 */
	public function get_form_field($form_id, $field_id) {
		if (!isset($this->forms[$form_id]) || !isset($this->forms[$form_id][$field_id])) {
			return false;
		}
		return $this->forms[$form_id][$field_id];
	}

	/**
	 * Checks whether the form field tag exist or not.
	 *
	 * @param string $form_id
	 *   the form id.
	 * @param string $field_id
	 *   the form field id.
	 *   the form id.
	 * @param string $tag
	 *   the tag name.
	 *
	 * @return boolean returns true if given form field tag exist, else false
	 */
	public function form_field_tag_exist($form_id, $field_id, $tag) {
		return isset($this->forms[$form_id]) && isset($this->forms[$form_id][$field_id]) && isset($this->forms[$form_id][$field_id][$tag]);
	}

	/**
	 * Returns the form field tag value if it exists.
	 *
	 * @param string $form_id
	 *   the form id.
	 * @param string $field_id
	 *   the form field id.
	 * @param string $tag
	 *   the tag name.
	 *
	 * @return array Returns an array with all form field tags, if form or form field does not exist it returns boolean false
	 */
	public function get_form_field_tag($form_id, $field_id, $tag) {
		if (!isset($this->forms[$form_id]) || !isset($this->forms[$form_id][$field_id]) || !isset($this->forms[$form_id][$field_id][$tag])) {
			return false;
		}
		return $this->forms[$form_id][$field_id][$tag];
	}

	/**
	 * Parses all forms which are found within the given $content.
	 *
	 * @param string $content
	 *   the plain html content
	 */
	public function parse_forms($content) {
		if (preg_match_all('/<\s*form([^>]*)>(.*)<\s*\/\s*form\s*>/iUs', $content, $matches) || preg_match_all('/<\s*div ajax_form="1"([^>]*)>(.*)<\s*\/\s*div\s*><!-- AJAX CLOSE -->/iUs', $content, $matches)) {
			foreach ($matches[2] AS $k => $form) {

				$form_info = $this->get_input_tags($matches[1][$k]);
				$name = (isset($form_info['id'])) ? $form_info['id'] : '';
				$this->forms[$name] = array();

				if (preg_match_all('/<\s*input(.+)\/>/iUs', $form, $inputs)) {
					foreach ($inputs[1] AS $input_tokens) {
						$element = $this->get_input_tags($input_tokens);
						if (!empty($element)) {
							if (isset($element['id'])) {
								$id = preg_replace('/^form_id_(' . preg_quote($name, '/') . '_)?/', '', $element['id']);
								$this->forms[$name][$id] = $element;
							}
						}

					}
				}

				if (preg_match_all('/<\s*textarea(.+)>(.*)<\s*\/\s*textarea\s*>/iUs', $form, $textareas)) {
					foreach ($textareas[1] AS $k => $textarea) {
						$element = $this->get_input_tags($textarea);
						if (!empty($element)) {
							$id = preg_replace('/^form_id_(' . preg_quote($name, '/') . '_)?/', '', $element['id']);
							$element['value'] = $textareas[2][$k];
							$element['type'] = 'textarea';
							$this->forms[$name][$id] = $element;
						}
					}
				}
				if (preg_match_all('/<\s*select(.+)>(.*)<\s*\/\s*select\s*>/iUs', $form, $selectfields)) {

					foreach ($selectfields[1] AS $k => $selectfield) {
						$select_element = $this->get_input_tags($selectfield);
						if (!empty($select_element)) {
							$id = preg_replace('/^form_id_(' . preg_quote($name, '/') . '_)?/', '', $select_element['id']);
							$select_element['type'] = 'select';
							$select_element['options'] = array();
							$select_element['value'] = '';


							if (preg_match_all('/<\s*option(.+)>(.*)<\s*\/\s*option\s*>/iUs', $selectfields[2][$k], $options)) {
								foreach ($options[1] AS $k => $option) {
									$element = $this->get_input_tags($option);
									if (!empty($element)) {
										$element['type'] = 'option';
										$element['value'] = $options[2][$k];
										$select_element['options'][] = $element;
									}
									if (isset($element['selected'])) {
										$select_element['value'] = $element['value'];
									}
								}
							}

							$this->forms[$name][$id] = $select_element;
						}
					}
				}

			}
		}
	}

	/**
	 * Extracts all tags for given input string.
	 *
	 * @param string $text
	 *   the input inner html.
	 *
	 * @return array the tag array
	 */
	private function get_input_tags($text) {
		$tokens = array();
		if (preg_match_all('/(([a-z_]+)\s*=\s*("(.*(?<!\\\))"|\'(.*(?<!\\\))\'|([a-z0-9_.]+)(>|\/|\s|$)))/iUs', $text, $f_token)) {
			foreach ($f_token[2] AS $k=>$type) {
				if (!empty($f_token[6][$k])) {
					$tokens[$type] = $f_token[6][$k];
				}
				else if (!empty($f_token[5][$k])) {
					$tokens[$type] = $f_token[5][$k];
				}
				else {
					$tokens[$type] = $f_token[4][$k];
				}
			}
		}
		return $tokens;
	}
}

?>