<?php

/**
 * Provides a class to handle floats.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Tools
 */
class FloatTools
{

	/**
	 * Compares $left and $right float value if they are equals.
	 *
	 * @param float $left
	 *   first float
	 * @param float $right
	 *   second float
	 * @param int $precision
	 *   the precision
	 *
	 * @return boolean true if both floats are equals, else false.
	 */
	public static function floatcmp($left, $right, $precision = 10) {
		// are 2 floats equal
		$e = pow(10, $precision);
		$i1 = (int) ($left * $e);
		$i2 = (int) ($right * $e);
		return ($i1 == $i2);
	}

	/**
	 * Compares $left and $right float value if $left is greater than $right.
	 *
	 * @param float $left
	 *   first float
	 * @param float $right
	 *   second float
	 * @param int $precision
	 *   the precision
	 *
	 * @return boolean true if $left is greater than $right, else false.
	 */
	public static function floatgtr($left, $right, $precision = 10) {
		// is one float bigger than another
		$e = pow(10, $precision);
		return ((int) ($left * $e) > (int) ($right * $e));
	}

	/**
	 * Compares $left and $right float value if $left is greater or equals than $right.
	 *
	 * @param float $left
	 *   first float
	 * @param float $right
	 *   second float
	 * @param int $precision
	 *   the precision
	 *
	 * @return boolean true if $left is greater than $right or both equals, else false.
	 */
	public static function floatgtre($left, $right, $precision = 10) {
		// is one float bigger or equal than another
		$e = pow(10, $precision);
		return ((int) ($left * $e) >= (int) ($right * $e));
	}

	/**
	 * Compares $left and $right float value if $left is less than $right.
	 *
	 * @param float $left
	 *   first float
	 * @param float $right
	 *   second float
	 * @param int $precision
	 *   the precision
	 *
	 * @return boolean true if $left is less than $right, else false.
	 */
	public static function floatltr($left, $right, $precision = 10) {
		// is one float smaller than another
		$e = pow(10, $precision);
		return ((int) ($left * $e) < (int) ($right * $e));
	}

	/**
	 * Compares $left and $right float value if $left is less or equals than $right.
	 *
	 * @param float $left
	 *   first float
	 * @param float $right
	 *   second float
	 * @param int $precision
	 *   the precision
	 *
	 * @return boolean true if $left is less than $right or both equals, else false.
	 */
	public static function floatltre($left, $right, $precision = 10) {
		// is one float smaller or equal than another
		$e = pow(10, $precision);
		return ((int) ($left * $e) <= (int) ($right * $e));
	}

}

