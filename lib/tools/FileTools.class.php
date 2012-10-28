<?php

/**
 * Provides a class to handle file operations.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Tools
 */
class FileTools
{
	/**
	 * Returns a valid full file path to the specified $path.
	 *
	 * It will check the path if it is correctly chrooted to the SITEPATH.
	 *
	 * @param string $path
	 *   the path
	 * @param boolean $prepend_sitepath
	 *   If set to false SITEPATH will be NOT prepended (optional, default = true)
	 *
	 *  @return string|null the path or null if path is invalid.
	 */
	public static function get_path($path, $prepend_sitepath = true) {

		if ($prepend_sitepath === true) {
			$path = SITEPATH . '/' . $path;
		}

		// Replace double path seperators.
		$path = preg_replace("/\/\/+/", "/", $path);
		// Replace double SITEPATH entries both, path and realpath.
		$real_path = realpath(preg_replace('/' . preg_quote(SITEPATH, '/') .  '(' . preg_quote(SITEPATH, '/') . '){1,}/', SITEPATH, $path));
		$path = preg_replace('/' . preg_quote(SITEPATH, '/') .  '(' . preg_quote(SITEPATH, '/') . '){1,}/', SITEPATH, $path);

		// Check if the processed path is chrooted to SITEPATH.
		if (!preg_match('/^' . preg_quote(SITEPATH, '/') .  '\//', $real_path)) {
			return null;
		}
		// Return the path.
		return $real_path;
	}
}