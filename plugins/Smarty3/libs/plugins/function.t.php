<?php
/**
* Smarty plugin
*
* @package Smarty
* @subpackage PluginsFunction
*/

/**
* Smarty {t} function plugin
*
* Type:     function<br>
* Name:     t<br>
* Date:     September 25, 2011
* Purpose:  Provide translation function<br>
*
* @version 1.0
* @author Christian Ackermann <prdatur at gmail dot com>
* @param array $params parameters
* Input:<br>
*          - key = the key
*          - args = (optional) the arguments which we want to replace
* @return string
*/
function smarty_function_t($params)
{
	if(empty($params['key'])) {
		if(!empty($params['default'])) {
			return $params['default'];
		}
		return "";
	}
	if(!isset($params['args'])) {
		$params['args'] = array();
	}
	return htmlspecialchars_decode(t($params['key'], $params['args']), ENT_QUOTES);
}

?>
