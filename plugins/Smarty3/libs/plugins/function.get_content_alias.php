<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage PluginsFunction
 */


/**
 * Smarty {get_content_alias} plugin
 *
 * Type:     function<br>
 * Name:     get_content_alias<br>
 * Purpose:  show the alias of a content page
 * @param array $params parameters
 * @param object $smarty Smarty object
 * @param object $template template object
 * @return string the text
 */
function smarty_function_get_content_alias($params, $smarty, $template)
{
	$id = $params['id'];
	if (!empty($id)) {
		$page = new Content();
		try {
			if (!empty($params['link'])) {
				return '/' . $page->get_alias_for_page_id($id);
			}
			return $page->get_alias_for_page_id($id);
		}
		catch(Exception $e) {
		}
		if (!empty($params['link'])) {
			return '/content/view/' . $id;
		}
	}
	
	return "";
}

?>
