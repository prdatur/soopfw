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
	/* @var $core Core */
	global $core;


	$id = $params['id'];
	if (!is_numeric($id)) {
		$filter = DatabaseFilter::create(PageRevisionObj::TABLE)
			->add_column('page_id')
			->add_where('title', $id)
			->select_first();
		$id = $filter['page_id'];
	}

	if (!empty($id)) {
		$page = new content();
		return $page->view($id, '', true);
	}
	return "";
}

?>
