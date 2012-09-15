<?php

/**
 * Provides a class to provide user helper methods
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.user.includes
 * @category User
 */
class UserTools
{

	/**
	 * Generates a random Password with letters a-zA-Z0-9
	 *
	 * @param int $count
	 *   the Password length
	 *
	 * @return string the password
	 */
	public static function generate_pw($count) {
		$charset = array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9");
		shuffle($charset);
		$newpw = "";
		for ($i = 0; $i < $count; $i++) {
			shuffle($charset);
			$newpw .= $charset[rand(0, count($charset) - 1)];
		}
		return $newpw;
	}

}

