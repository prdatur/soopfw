<?php

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */
function round_money($value, $currency) {
	switch ($currency) {
		case 'CHF':
			$divider = 20;
			break;
		default:
			$divider = 100;
			break;
	}
	$value = (float) $value;
	return round($value * $divider) / $divider;
}

/**
 * Smarty {money_format} modifier plugin
 *
 * Type:     modifier<br>
 * Name:     money_format<br>

 * @return string
 */
function smarty_modifier_money_format($string, $currency) {
	switch ($currency) {
		case 'EUR': {
				$decPoint = ",";
				$thousendPoint = ".";
				break;
			}
		case 'CHF': {
				$decPoint = ".";
				$thousendPoint = "'";
				break;
			}
	}

	return number_format(round_money($string, $currency), 2, $decPoint, $thousendPoint);
}
?>
