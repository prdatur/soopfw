<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {round} modifier plugin
 *
 * Type:     modifier<br>
 * Name:     round<br>

 * @return string
 */
function smarty_modifier_round($string, $precision = null)
{
	return round(floatval($string), $precision);
}

/* vim: set expandtab: */

?>
