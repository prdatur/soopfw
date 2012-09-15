<?php

/**
 * Provides a class to handle arrays.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Tools
 */
class ArrayTools
{

	/**
	 * Fill the specific $array with the specific $values
	 * @param array $values
	 *   the values to fill.
	 * @param array &$array
	 *   the array to be filled.
	 */
	public static function fill_array($values, &$array) {
		foreach ($values AS $val) {
			if (empty($array[$val])) {
				$array[$val] = "";
			}
		}
	}

	/**
	 * Merge array $b into array $a, same keys in b will be overriden in a, the arrays can be multi dimensional.
	 *
	 * @param array $a
	 *   the first array
	 * @param array $b
	 *   the second array
	 *
	 * @return array the extended array
	 */
	public static function array_extend($a, $b) {
		foreach ($b as $k => $v) {
			if (is_array($v)) {
				if (!isset($a[$k])) {
					$a[$k] = $v;
				}
				else {
					$a[$k] = self::array_extend($a[$k], $v);
				}
			}
			else {
				$a[$k] = $v;
			}
		}
		return $a;
	}

	/**
	 * Sort an array recrusive by asort.
	 *
	 * @param array $a
	 *   the array to be sorted
	 */
	public static function mulsort(Array &$a) {
		asort($a);
		foreach ($a AS &$v) {
			if (is_array($v)) {
				self::mulsort($v);
			}
		}
	}

}

?>