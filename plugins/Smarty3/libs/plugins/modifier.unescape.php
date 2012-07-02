<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty unescape modifier plugin
 *
 * Type:     modifier<br>
 * Name:     escape<br>
 * Purpose:  UnEscape the string
 * @author   Christian Ackermann <webmaster at air-unlimited.de>
 * @param string
 * @return string
 */
function smarty_modifier_unescape($string)
{
	$string = htmlspecialchars_decode($string, ENT_QUOTES);
	return $string;
}

/* vim: set expandtab: */

?>
