<?php

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty {money_format} modifier plugin
 *
 * Type:     modifier<br>
 * Name:     money_format<br>

 * @return string
 */
function smarty_modifier_money_format($string, $currency) {
	return formate_money($string, $currency);
}
?>
