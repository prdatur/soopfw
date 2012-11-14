<?php

/**
 * Provides a field group which contains upload fields
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @module Content
 * @category Field groups
 */
class FieldGroupUpload extends AbstractFieldGroup
{

	/**
	 * Constructor
	 *
	 * @param array $values
	 *   The prefilled values should be same as _POST after submitting (optional, default = array())
	 */
	function __construct(Array $values = array()) {
		parent::__construct($values);
		$this->add_field(new Filefield('file', '', t("file")));
	}

	public function get_template(Array &$elements = null) {

		// Get the elements.
		if($elements == null) {
			$file_input = $this->get_field('file');
		}
		else {
			$file_input = &$elements['file'];
		}

		// Those upload fields need to be ajax.
		$file_input->set_ajax(true);
		if($this->max_value == 1) {
			$file_input->config('label', $this->get_label());
		}

		// Setup javascript that we can handle the upload field.
		$behavior_id = 'system_add_js_config_from_ajax_'.$file_input->config('id');

		$js = '
		<script type="text/javascript" language="javascript">
			if(Soopfw.prio_behaviors == undefined) {
				Soopfw.prio_behaviors = {};
			}
			Soopfw.prio_behaviors[\''.$behavior_id.'\'] = function() {
				Soopfw.config = $.extend(Soopfw.config, '.json_encode($this->core->get_js_config()).');
				Soopfw.prio_behaviors[\''.$behavior_id.'\'] = function() {}
				Soopfw.behaviors.AjaxFileUploadSetup();

			};
			Soopfw.reload_behaviors();
		</script>';

		// Return the html.
		return $file_input->fetch().$js;
	}

	/**
	 * Parses all provided values for this field group.
	 *
	 * @param array $values
	 *   The values for this field group id
	 */
	public static function parse_value(&$values) {

		// Loop through all field groups.
		foreach($values AS &$field_group_values) {

			// Loop through all inputs within the field groups.
			foreach($field_group_values AS $field_group_field_name => $field_group_field_value) {

				// If the field name is 'file' this is one we need to parse.l
				if ($field_group_field_name === 'file') {

					// Try to load the file, based up on the field value.
					$file_obj = new MainFileObj($field_group_field_value);

					// Provide the file object class if it could be loaded.
					if ($file_obj->load_success()) {
						$field_group_values['file_obj'] = &$file_obj;
					}
				}
			}
		}
	}

}

