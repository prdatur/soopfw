<?php

/**
 * Provides a class to get some helper methods for net based actions.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category NetTools
 */
class NetTools
{

	/**
	 * Returns the request uri, without GET-Parameters.
	 *
	 * @return string The prepared request uri.
	 */
	public static function get_request_uri() {
		static $url = null;
		if ($url === null) {
			list($url) = explode('?', NetTools::get_full_request_uri(), 2);
		}
		return $url;
	}

	/**
	 * Returns the full request uri.
	 *
	 * @return string The prepared request uri.
	 */
	public static function get_full_request_uri() {
		static $url = null;
		if (!isset($_SERVER['REQUEST_URI'])) {
			return '';
		}
		if ($url === null) {
			$url = preg_replace('/^\/+/is', '/', $_SERVER['REQUEST_URI']);
		}
		return $url;
	}

	/**
	 * Check the given value against the email regexp, if checkdns is set to true (default) than the domain part will be checked if there is an mx record
	 *
	 * @param string $value
	 *   the email
	 * @param boolean $check_dns
	 *   whether to check against the dns record for valid mx record or not
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
	 * Returns whether the current connection is a local ip or not.
	 *
	 * @return boolean true if current ip is a local ip or false if not
	 */
	public static function is_local_ip() {
		return preg_match("/^127\.0\.[0-9]+\.[0-9]+$/", NetTools::get_real_ip());
	}

	/**
	 * Returns the user identification which will try to get the best match to re-identify the current user.
	 * If the user is logged in it will return "user_id:" followed by the current user id
	 * else it will return "ip:" followed by the current ip address.
	 *
	 * @return string the user identification.
	 */
	public static function get_user_identification() {
		$core = Core::get_instance();
		if ($core->session->is_logged_in()) {
			return 'user_id:' . $core->session->current_user()->user_id;
		}
		else {
			return 'ip:' . NetTools::get_real_ip();
		}
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

		// Only read the ip one time.
		if ($ip === null) {

			// Check if we have a proxy.
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {

				// Get the direct remote address which is the proxy ip.
				$client_ip = (!empty($_SERVER['REMOTE_ADDR']) ) ? $_SERVER['REMOTE_ADDR'] : ( (!empty($_ENV['REMOTE_ADDR']) ) ? $_ENV['REMOTE_ADDR'] : "unknown" );

				// Get all forwarded entries.
				$entries = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

				reset($entries);
				while (list(, $entry) = each($entries)) {
					$entry = trim($entry);
					// Check the forwarded entry if they are valid ips.
					if (preg_match("/^([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/", $entry, $ip_list)) {
						$private_ip = array(
							'/^0\./',
							'/^127\.0\.0\.1/',
							'/^192\.168\..*/',
							'/^172\.((1[6-9])|(2[0-9])|(3[0-1]))\..*/',
							'/^10\..*/');

						// Remove all private ips.
						$found_ip = preg_replace($private_ip, $client_ip, $ip_list[1]);

						// If the current forwarded ip is not a private one and not the proxy ip it is the real remote address.
						if ($client_ip != $found_ip) {
							$client_ip = $found_ip;
							break;
						}
					}
				}
			}
			else {
				// We have no proxy so get the direct remote address.
				$client_ip = (!empty($_SERVER['REMOTE_ADDR']) ) ? $_SERVER['REMOTE_ADDR'] : ( (!empty($_ENV['REMOTE_ADDR']) ) ? $_ENV['REMOTE_ADDR'] : "unknown" );
			}
			// If we have a ipv6 convertion get only the ip4 string.
			if (strpos($client_ip, '::') === 0) {
				$client_ip = substr($client_ip, strrpos($client_ip, ':') + 1);
			}

			$long = ip2long($client_ip);
			if (!$long) {
				$long = 0;
			}

			// Cache the data.
			$ip[false] = $client_ip;
			$ip[true] = $long;
		}

		// Return the wanted ip.
		return $ip[$ip2long];
	}

	/**
	 * Download the given content as the given extension.
	 *
	 * @param string $content
	 *   The complete content.
	 * @param string $filename
	 *   The complete filename INCLUDING the extension.
	 * @param string $extension
	 *   The extension, please provide NOT a mimetype.
	 *   For example: 'xml', 'tar.gz'...
	 * @param int $expires
	 *   The expire time which will be set within the headers.
	 *   The value are seconds and are NOT the date instead the value will be added to the current date.
	 *    (optional, default = DateTools::TIME_DAY)
	 * @param int $last_modofied
	 *   The last modification time.
	 *   The value is the direct date in seconds. (optional, default = TIME_NOW)
	 */
	public static function download($content, $filename, $extension, $expires = DateTools::TIME_DAY, $last_modified = TIME_NOW) {

		// Holds all extensions which should be downloaded as "text/plain"
		$plain_text = array(
			'txt' => '',
		);

		//Set http headers
		header("Expires: " . gmdate("D, d M Y H:i:s", TIME_NOW + $expires) . " GMT+1");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s", $last_modified) . " GMT+1");
		header("Content-Length: " . strlen($content));

		// If we have not a text/plain extension.
		if (!isset($plain_text[$extension])) {
			header("Content-Transfer-Encoding: binary");

			// Default mime type if we have not found a valid mime type for the given extension.
			$content_type = "application/" . $extension;

			// Try get the mime type for the extension.
			$obj = new MimeTypeObj($extension);
			if ($obj->load_success()) {
				$content_type = $obj->mime_type;
			}

			header("Content-type: " . $content_type . "\n");
			header("Content-Disposition: attachment; filename=\"" . $filename . "\";\n\n");
		}
		else {
			header("Content-type: text/plain\n");
		}
		echo $content;
		die();
	}

}

