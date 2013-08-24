<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Class to handle the handshake.
 * decrypt/encrypt methods must be implemented within the "version" of websocket.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Websocket
 */
abstract class WebSocketHandshake {

	/**
	 * The Websocket user.
	 *
	 * @var WebSocketUser
	 */
	protected $client = null;

	/**
	 * Construct.
	 *
	 * @param WebSocketUser &$client
	 *   The client.
	 */
	public function __construct(WebSocketUser &$client) {
		$this->client = $client;
	}

	/**
	 * Do the handshake.
	 *
	 * @param string $data
	 * 	 incoming request data
	 * @param mixed $check_origin
	 *   If left empty, no origin check will be performed, else it will check it (optional, default = false)
	 *
	 * @return boolean Whether the handshake succeed or not, false on error
	 */
	public function handshake($data, $check_origin = false) {
		WebSocket::log_console('Performing handshake');
		$lines = preg_split("/\r\n/", $data);

		// Check if we must send a flash server policy.
		if (count($lines) && preg_match('/<policy-file-request.*>/', $lines[0])) {
			WebSocket::log_console('Flash policy file request');
			$this->serve_flash_policy();
			return false;
		}

		// Check for valid http-header.
		if (!preg_match('/\AGET (\S+) HTTP\/1.1\z/', $lines[0], $matches)) {
			WebSocket::log_console('Invalid request: ' . $lines[0]);
			WebSocket::send_http_response($this->client, WebSocket::HEADER_RESPONSE_BAD_REQUEST);
			return false;
		}

		// Generate headers array.
		$headers = array();
		$headers['path'] = $matches[1];
		foreach ($lines as $line) {
			$line = chop($line);
			if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
				$headers[strtolower($matches[1])] = $matches[2];
			}
		}

		// Check for supported websocket version.
		if (!isset($headers['sec-websocket-version']) || (int)$headers['sec-websocket-version'] < 6) {
			WebSocket::log_console('Unsupported websocket version.');
			WebSocket::send_http_response($this->client, WebSocket::HEADER_RESPONSE_NOT_IMPLEMENTED, true);
			return false;
		}

		// check origin:
		if (!empty($check_origin)) {
			$origin = (isset($headers['sec-websocket-origin'])) ? $headers['sec-websocket-origin'] : false;
			$origin = (isset($headers['origin'])) ? $headers['origin'] : $origin;
			if (!$this->check_origin($origin, $check_origin)) {
				WebSocket::log_console('No, empty or invalid origin provided.');
				$this->sendHttpResponse($this->client, WebSocket::HEADER_RESPONSE_UNAUTHORIZED);
				return false;
			}
		}

		return $headers;
	}

	/**
	 * Checks if the submitted origin (part of websocket handshake) is allowed
	 * to connect.
	 *
	 * Allowed origins can be set at server startup.
	 *
	 * @param string $domain
	 *   The origin-domain from websocket handshake.
	 * @param array $check_origin
	 *   the allowed origin's.
	 *
	 * @return bool If domain is allowed to connect method returns true.
	 */
	private function check_origin($domain, array $check_origin) {
		if (empty($check_origin)) {
			return true;
		}
		return isset($check_origin[str_replace(array(
			'http://',
			'https://',
			'www.',
			'/',
		), array('', '', '', ''), $domain)]);
	}

	/**
	 * Sends the flash policy request.
	 */
	protected function serve_flash_policy() {
		$policy = '<?xml version="1.0"?>' . "\n";
		$policy .= '<!DOCTYPE cross-domain-policy SYSTEM "http://www.macromedia.com/xml/dtds/cross-domain-policy.dtd">' . "\n";
		$policy .= '<cross-domain-policy>' . "\n";
		$policy .= '<allow-access-from domain="*" to-ports="*"/>' . "\n";
		$policy .= '</cross-domain-policy>' . "\n";
		socket_write($this->socket, $policy, strlen($policy));
		socket_close($this->socket);
	}

	/**
	 * Decodes outgoing data for the given version.
	 *
	 * @param string $data
	 * 	 The data
	 *
	 * @return mixed The data on success, else false
	 */
	public static function decode($data) {
		return $data;
	}

	/**
	 * Encodes the incoming data for the given version.
	 *
	 * @param string $data
	 * 	 The string to be encoded
	 * @param string $type
	 * 	 The data type (optional, default = 'text')
	 * @param boolean $masked
	 * 	 Whether we want to mask it or not (optional, default = true)
	 *
	 * @return string If success it returns the encoded string, else false
	 */
	public static function encode($data, $type = 'text', $masked = true) {
		return $data;
	}

}


