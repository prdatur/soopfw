<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {format} function plugin
 *
 * Type:     function<br>
 * Name:     bormat<br>
 * Input:<br>
 *           - params        - array

 * @return string
 */
function smarty_function_format($params, &$smarty)
{
	if (empty($params['type']))
	{
		$smarty->trigger_error("format: missing parameter 'type'");
	}
	if (!isset($params['value']))
	{
		$smarty->trigger_error("format: missing parameter 'value'");
	}
	
	if (isset($params['ifEmpty']) && empty($params['value'])) return $params['ifEmpty'];

	switch($params['type'])
	{
		case 'currency':
			return	Core::get_instance()->lng->format_currency($params['value']);
			break;

		default:
			$smarty->trigger_error("format: unsupported 'type'". $params['type']);
	}

}

/* vim: set expandtab: */

?>
