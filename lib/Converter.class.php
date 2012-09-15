<?php

/**
 * Provides a class to convert values into other values or formated values.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Tools
 */
class Converter
{

	/**
	 * Formate a given number to the given currency.
	 *
	 * @param string $string
	 *   the number which will be formated.
	 * @param string $currency
	 *   the currency string.
	 *
	 * @return string the formated string
	 */
	public static function format_money($string, $currency) {
		switch ($currency) {
			case 'EUR':
				$decPoint = ",";
				$thousendPoint = ".";
				break;
			case 'CHF':
				$decPoint = ".";
				$thousendPoint = "'";
				break;
			default:
				$thousendPoint = ".";
				$decPoint = ",";
				break;
		}

		return number_format(self::round_money($string, $currency), 2, $decPoint, $thousendPoint);
	}

	/**
	 * Rounds a given $value to the given $currency.
	 *
	 * @param float $value
	 *   the value to be rounded.
	 * @param string $currency
	 *   which currency is used.
	 *
	 * @return float the rounded money number
	 */
	public static function round_money($value, $currency) {
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
	 * Returns a formated size string for the given bytes.
	 *
	 * @param int $bytes
	 *   the bytes
	 * @param string $force_type
	 *   if provided it will return the forced type and not the smallest one (optional, default = '')
	 *
	 * @return string the formated string with size description (kb, mb, gb ...)
	 */
	public static function format_bytes($bytes, $force_type = '') {
		$size_types = array('kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
		$i = -1;
		do {
			$bytes = $bytes / 1024;
			$i++;
			if (!empty($force_type) && $force_type == $size_types[$i]) {
				break;
			}
		}
		while ($bytes > 999);

		return round(max(array($bytes, 0.1)), 2) . ' ' . $size_types[$i];
	}

	/**
	 * Converts a string like 100k to the bytes which are here 100*1024.
	 * The highest byte string identifer can be y which represents yottabyte.
	 * list of endings:
	 * y = Yottabytes
	 * z = Zetabytes
	 * e = Exabytes
	 * p = Petabytes
	 * t = Terrabytes
	 * g = Gigabytes
	 * m = Megabytes
	 * k = Kilobytes
	 *
	 * @param string $str
	 *   the size string
	 *
	 * @return int the bytes
	 */
	public static function format_byte_string($str) {
		$val = trim($str);

		if (preg_match("/^([0-9]+)\s*([kmgtpezy])?b?\s*$/i", $val, $matches)) {
			$val = (int) $matches[1];

			if (!empty($matches[2])) {
				switch (strtolower($matches[2])) {
					case 'y': $val *= 1024;
					case 'z': $val *= 1024;
					case 'e': $val *= 1024;
					case 'p': $val *= 1024;
					case 't': $val *= 1024;
					case 'g': $val *= 1024;
					case 'm': $val *= 1024;
					case 'k': $val *= 1024;
				}
			}
		}

		return (int) $val;
	}

}

