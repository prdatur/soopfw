<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage PluginsModifier
 */


/**
 * Smarty bbcode modifier plugin
 *
 * Type:     modifier<br>
 * Name:     bbcode<br>
 * Date:     Okt 14, 2011
 * Purpose:  replace bbcode tags to html
 * Input:    string to convert
 * Example:  {$var|bbcode}
 * @author   Christian Ackermann <prdatur@gmail.com>
 * @version 1.0
 * @param string
 * @return string
 */
function smarty_modifier_bbcode($string)
{
	$bbcode = new BBCodeParser();
    return $bbcode->parse($string);
}

?>
