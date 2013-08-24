<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage PluginsModifier
 */


/**
 * Smarty md5 modifier plugin
 *
 * Type:     modifier<br>
 * Name:     md5<br>
 * Date:     Aug 02, 2013
 * Purpose:  generate a md5 sum
 * Input:    string to md5 sum
 * Example:  {$var|md5}
 * @author   Christian Ackermann <prdatur at gmail dot com>
 * @version 1.0
 * @param string
 * @param string
 * @return string
 */
function smarty_modifier_md5($string)
{
    return md5($string);
}

?>
