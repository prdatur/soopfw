<?php

/**
 * Smarty plugin
 * 
 * @package Smarty
 * @subpackage PluginsModifier
 */

/**
 * Smarty ucfirst modifier plugin
 * 
 * Type:     modifier<br>
 * Name:     icfirst<br>
 * Purpose:  convert first char to uppercase
 * 
 * @link http://smarty.php.net/manual/en/language.modifier.upper.php upper (Smarty online manual)
 * @author Christian Ackermann <prdatur at gmail dot com> 
 * @param string $ 
 * @return string 
 */
function smarty_modifier_ucfirst($string) {
	return ucfirst($string);
}
