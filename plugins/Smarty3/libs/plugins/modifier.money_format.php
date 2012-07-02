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
function smarty_modifier_money_format($string, $currency)
{
	switch($currency)
	{
		case 'EUR':
		{
			$decPoint = ",";
			$thousendPoint = ".";
			break;
		}
		case 'CHF':
		{
			$decPoint = ".";
			$thousendPoint = "'";
			break;
		}
	}

    return number_format(round_money($string, $currency),2, $decPoint, $thousendPoint);
}

/* vim: set expandtab: */

?>
