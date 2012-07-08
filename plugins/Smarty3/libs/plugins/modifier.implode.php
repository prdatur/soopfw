<?php
/**
* Smarty plugin
* 
* @package Smarty
* @subpackage PluginsModifier
*/

/**
* Smarty implode modifier plugin
* 
* Type:     modifier<br>
* Name:     implode<br>
* Purpose:  transform the array into an string with given seperator
* 
* @author christian ackermann
* @param array $array
* @param string $seperator
 * 
* @return string 
*/
function smarty_modifier_implode($array, $seperator)
{
	if (!is_array($array)) {
		$array = array($array);
	}
    return implode($seperator, $array);
} 

?>
