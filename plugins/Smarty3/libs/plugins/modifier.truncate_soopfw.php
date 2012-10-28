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
 * @param string $string
 *   input string
 * @param int $length
 *   lenght of truncated text
 * @param string $truncate_policy
 *   truncate with the specific policy
 *   Use one of StringTools::TRUNCATE_POLICY_*
 *    (optional, default = StringTools::TRUNCATE_POLICY_WORD_SAVE)
 * @param string $etc
 *   end string
 *
 * @return string truncated string
 */
function smarty_modifier_truncate_soopfw($string, $length = 80, $truncate_policy = StringTools::TRUNCATE_POLICY_WORD_SAVE, $etc = '...') {
	return StringTools::truncate($string, $length, $truncate_policy, $etc);
}

?>