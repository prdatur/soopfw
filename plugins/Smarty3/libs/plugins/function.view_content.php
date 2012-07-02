<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage PluginsFunction
 */


/**
 * Smarty {view_content} plugin
 *
 * Type:     function<br>
 * Name:     view_content<br>
 * Purpose:  show a content page rendered text
 * @param array $params parameters
 * @param object $smarty Smarty object
 * @param object $template template object
 * @return string the text
 */
function smarty_function_view_content($params, $smarty, $template)
{
	$page = new PageObj($params['id']);
	return $page->get_content();
}

?>
