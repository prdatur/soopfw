<?php

/**
 * Provides a field group which contains upload fields
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.content.field_groups
 */
class FieldGroupUpload extends AbstractFieldGroup
{

	/**
	 * Constructor
	 *
	 * @param array $values the prefilled values should be same as _POST after submitting (optional, default = array())
	 */
	function __construct(Array $values = array()) {
		parent::__construct($values);
		$this->add_field(new Filefield('file', '', t("file")));
	}

	public function get_template(Array &$elements = null) {

		if($elements == null) {
			$file_input = $this->get_field('file');
		}
		else {
			$file_input = &$elements['file'];
		}

		$file_input->set_ajax(true);
		if($this->max_value == 1) {
			$file_input->config('label', $this->get_label());
		}


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
		return $file_input->fetch().$js;
	}

}

?>