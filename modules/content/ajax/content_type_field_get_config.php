<?php
/**
 * Provides an ajax request to handle the "Add more button" for content creation
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @module Content
 * @category Ajax
 */
class AjaxContentContentTypeFieldGetConfig extends AjaxModul {

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {
		//Define needed params
		$params = new ParamStruct();
		$params->add_param("content_field", PDT_STRING, 'FieldGroupEmbeddedVideo');

		//Fill the params
		$params->fill();

		//Display error if params are not valid
		if (!$params->is_valid()) {
			die('');
		}

		//Check perms
		if (!$this->core->get_right_manager()->has_perm("admin.content.create")) {
			die('');
		}
		// Get the content field
		$field = $params->content_field;

		// Create a field instance of the provided field.
		$class = new $field();

		// Generate dummy form.
		$form = new Form('form_' . ContentTypeFieldGroupObj::TABLE, '', '');

		// Call the config method from the content field to get config parameters.
		$class->config($form);

		// Get the html element string.
		$html = "";

		foreach ($form->elements as &$fields) {
			foreach ($fields as &$element) {
				// Wrap our config array with an "config" array key, so we can easy get the additional config params.
				$element->config('name', 'config[' . $element->config('name') . ']');

				// Append the input html.
				$html .= $element->fetch();
			}
		}

		// Return.
		echo $html;
		die();
	}
}
