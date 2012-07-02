<?php

/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage PluginsModifier
 */

/**
 * Smarty truncate soopfw modifier plugin
 *
 * Type:     modifier<br>
 * Name:     truncate_soopfw<br>
 * Purpose:  Truncate a string to a certain length if necessary,
 *             optionally splitting in the middle of a word, and
 *             appending the $etc string or inserting $etc into the middle.
 *
 * @param string $string input string
 * @param integer $length lenght of truncated text
 * @param string $etc end string
 * @param boolean $break_words truncate at word boundary
 * @param boolean $middle truncate in the middle of text
 * @return string truncated string
 */
function smarty_modifier_truncate_soopfw($string, $length = 80, $etc = '...', $break_words = false, $middle = false) {
	return truncate_soopfw($string, $length, $etc, $break_words, $middle);
}

?>
