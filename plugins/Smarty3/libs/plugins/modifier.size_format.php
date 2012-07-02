<?php
/**
* Smarty plugin
*
* @package Smarty
* @subpackage PluginsModifier
*/

/**
* Smarty size_format modifier plugin
*
* Type:     modifier<br>
* Name:     size_format<br>
* Purpose:  format bytes to kb, mb, gb, tb <br>
* Input:<br>
*          - string: input date string
*          - format: the forced type
*
* @author prdatur
* @param string $
* @param string $
* @return string |void
*/
function smarty_modifier_size_format($string, $format = '')
{
    $number = (int)$string;
	$formats['b'] = 1;
	$formats['kb'] = 1024;
	$formats['mb'] = 1048576;
	$formats['gb'] = 1073741824;
	$formats['tb'] = 1099511627776;
	if(!empty($format) && isset($formats[$format])) {
		return get_round($number, $formats[$format])." ".strtoupper($format);
	}

	if($number >= $formats['tb']) {
		return get_round($number, $formats['tb'])." TB";
	} else if($number >= $formats['gb']) {
		return get_round($number, $formats['gb'])." GB";
	} else if($number >= $formats['mb']) {
		return get_round($number, $formats['mb'])." MB";
	} else if($number >= $formats['kb']) {
		return get_round($number, $formats['kb'])." KB";
	}
	return get_round($number, $formats['b'])." B";
}

function get_round($number, $format) {
	return round(($number/$format)*100)/100;
}

?>
