<?php

/**
 * Provides a class to get some helper methods for net based actions.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.net
 * @category NetTools
 */
class NetTools
{

	/**
	 * Returns the request uri.
	 *
	 * @return string the prepared request uri.
	 */
	public static function get_request_uri() {
		$url = null;
		if ($url === null) {
			$_SERVER['REQUEST_URI'] = preg_replace('/^\/+/is', '/', $_SERVER['REQUEST_URI']);
			list($url) = explode('?', $_SERVER['REQUEST_URI'], 2);
		}
		return $url;
	}

	/**
	 * Returns the full request uri.
	 *
	 * @return string the prepared request uri.
	 */
	public static function get_full_request_uri() {
		$url = null;
		if ($url === null) {
			$_SERVER['REQUEST_URI'] = preg_replace('/^\/+/is', '/', $_SERVER['REQUEST_URI']);
			$url = $_SERVER['REQUEST_URI'];
		}
		return $url;
	}

	/**
	 * Check the given value against the email regexp, if checkdns is set to true (default) than the domain part will be checked if there is an mx record
	 *
	 * @param string $value
	 *   the email
	 * @param boolean $check_dns
	 *   wether to check against the dns record for valid mx record or not
	 * 	 (optional, default = true)
	 *
	 * @return boolean true if valid, else false
	 */
	public static function check_mail($value, $check_dns = true) {
		if (!preg_match("/^[a-zA-Z][a-zA-Z0-9\._-]+@([a-zA-Z][a-zA-Z0-9\._-]+\.+[a-z]{2,4})$/i", $value, $matches)) {
			return false;
		}

		if (!$check_dns) {
			return true;
		}

		if (@checkdnsrr($matches[1], "MX") || @checkdnsrr($matches[1], "A")) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Returns wether the current connection is a local ip or not.
	 *
	 * @return boolean true if current ip is a local ip or false if not
	 */
	public static function is_local_ip() {
		return preg_match("/^127\.0\.[0-9]+\.[0-9]+$/", NetTools::get_real_ip());
	}

	/**
	 * Get the IP-Address from the client
	 *
	 * @param boolean $ip2long
	 *   Use ip2long for output.
	 *
	 * @return mixed Returns the ip adress as a String, if $ip2long is set to True it will return the converted long value
	 */
	public static function get_real_ip($ip2long = false) {
		static $ip = null;

		if ($ip === null) {
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$client_ip = (!empty($_SERVER['REMOTE_ADDR']) ) ? $_SERVER['REMOTE_ADDR'] : ( (!empty($_ENV['REMOTE_ADDR']) ) ? $_ENV['REMOTE_ADDR'] : "unknown" );

				$entries = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

				reset($entries);
				while (list(, $entry) = each($entries)) {
					$entry = trim($entry);
					if (preg_match("/^([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/", $entry, $ip_list)) {
						$private_ip = array(
							'/^0\./',
							'/^127\.0\.0\.1/',
							'/^192\.168\..*/',
							'/^172\.((1[6-9])|(2[0-9])|(3[0-1]))\..*/',
							'/^10\..*/');

						$found_ip = preg_replace($private_ip, $client_ip, $ip_list[1]);

						if ($client_ip != $found_ip) {
							$client_ip = $found_ip;
							break;
						}
					}
				}
			}
			else {
				$client_ip = (!empty($_SERVER['REMOTE_ADDR']) ) ? $_SERVER['REMOTE_ADDR'] : ( (!empty($_ENV['REMOTE_ADDR']) ) ? $_ENV['REMOTE_ADDR'] : "unknown" );
			}
			if (strpos($client_ip, '::') === 0) {
				$client_ip = substr($client_ip, strrpos($client_ip, ':') + 1);
			}
			$long = ip2long($client_ip);
			if (!$long) {
				$long = 0;
			}

			$ip[false] = $client_ip;
			$ip[true] = $long;
		}

		return $ip[$ip2long];
	}

}

