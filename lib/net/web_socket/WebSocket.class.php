<?php
/**
 * Provides the base class for a websocket.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Websocket
 */
abstract class WebSocket extends Object {

	// Define log constances.
	const LOG_INFO = 'info';
	const LOG_ERROR = 'error';
	const LOG_SUCCESS = 'success';

	// Define response header.
	const HEADER_RESPONSE_BAD_REQUEST = 400;
	const HEADER_RESPONSE_UNAUTHORIZED = 401;
	const HEADER_RESPONSE_FORBIDDEN = 403;
	const HEADER_RESPONSE_NOT_FOUND = 404;
	const HEADER_RESPONSE_NOT_IMPLEMENTED = 501;

	/**
	 * Whether we are within debug mode or not.
	 *
	 * @var boolean
	 */
	public static $debug = true;

	/**
	 * An array which holds all connected socket resource's including master
	 * socket.
	 *
	 * @var array
	 */
	protected $sockets = array();

	/**
	 * Holds all connected user.
	 *
	 * @var array
	 */
	protected $users = array();

	/**
	 * Used while handling incoming data.
	 *
	 * @var boolean
	 */
	protected $waiting_for_data = false;

	/**
	 * Stores temporary the incoming data.
	 *
	 * @var string
	 */
	protected $data_buffer = "";

	/**
	 * Handle an incoming request.
	 *
	 * @param string $data
	 *   The data to be handled
	 * @param WebSocketUser &$client
	 *   The client from which we got the data.
	 *
	 * @return boolean on error return false, else true
	 */
	protected function handle($data, WebSocketUser &$client) {
		if ($this->waiting_for_data === true) {
			$data = $this->data_buffer . $data;
			$this->data_buffer = '';
			$this->waiting_for_data = false;
		}

		$decoded_data = WebSocketHybi10::decode($data);
		if ($decoded_data === false) {
			$this->waiting_for_data = true;
			$this->data_buffer .= $data;
			return false;
		}
		else {
			$this->data_buffer = '';
			$this->waiting_for_data = false;
		}

		if (!isset($data['type'])) {
			WebSocketListener::send_http_response($client, WebSocket::HEADER_RESPONSE_UNAUTHORIZED);
			$this->disconnect($client->socket);
			return false;
		}

		switch ($decoded_data['type']) {
			case 'text':
				$data = json_decode($decoded_data['data'], true);
				if(!empty($data)) {
					$decoded_data['data'] = $data;
				}
				$this->on_data($decoded_data['data'], $client);
				break;

			case 'binary':
				$client->close(1003);
				break;

			case 'ping':
				$this->send($decoded_data['data'], 'pong', false);
				WebSocket::log_console('Ping? Pong!');
				break;

			case 'pong':
				// server currently not sending pings, so no pong should be received.
				break;

			case 'close':
				$client->close();
				WebSocket::log_console('Disconnected');
				break;
		}

		return true;
	}

	/**
	 * Method originally found in phpws project.
	 *
	 * @param resource $resource
	 *	 the resource
	 *
	 * @return string the result or FALSE on error
	 */
	public static function read_buffer($resource) {
		$buffer = '';
		$buffsize = 8192;
		$metadata['unread_bytes'] = 0;
		do {
			if (feof($resource)) {
				return false;
			}
			$result = fread($resource, $buffsize);
			if ($result === false || feof($resource)) {
				return false;
			}
			$buffer .= $result;
			$metadata = stream_get_meta_data($resource);
			$buffsize = ($metadata['unread_bytes'] > $buffsize) ? $buffsize : $metadata['unread_bytes'];
		}
		while ($metadata['unread_bytes'] > 0);
		return $buffer;
	}

	/**
	 * Send a direct http header response.
	 *
	 * @param WebSocketUser $user
	 *	 The user to which we want send the response.
	 * @param int $http_status_code
	 *	 The status code
	 *   (optional, default = WebSocket::HEADER_RESPONSE_BAD_REQUEST)
	 */
	public static function send_http_response(WebSocketUser $user, $http_status_code = self::HEADER_RESPONSE_BAD_REQUEST) {
		$http_header = 'HTTP/1.1 ' . $http_status_code;
		switch ($http_status_code) {
			case self::HEADER_RESPONSE_UNAUTHORIZED:
				$http_header .= ' Unauthorized';
				break;
			case self::HEADER_RESPONSE_FORBIDDEN:
				$http_header .= ' Forbidden';
				break;
			case self::HEADER_RESPONSE_NOT_FOUND:
				$http_header .= ' Not Found';
				break;
			case self::HEADER_RESPONSE_NOT_IMPLEMENTED:
				$http_header .= ' Not Implemented';
				break;
			case self::HEADER_RESPONSE_BAD_REQUEST:
			default:
				$http_header .= ' Bad Request';
				break;
		}
		$http_header .= "\r\n";
		WebSocket::write_buffer($user->socket, $http_header);
		stream_socket_shutdown($user->socket, STREAM_SHUT_RDWR);
	}

	/**
	 * Method originally found in phpws project.
	 *
	 * @param resource $resource
	 *	 The resource
	 * @param string $string
	 *	 The string to send
	 *
	 * @return int returns the length of written bytes or false on error
	 */
	public static function write_buffer($resource, $string) {
		$string_length = strlen($string);
		for ($written = 0; $written < $string_length; $written += $fwrite) {
			$fwrite = @fwrite($resource, substr($string, $written));
			if ($fwrite === false) {
				return false;
			}
			elseif ($fwrite === 0) {
				return false;
			}
		}
		return $written;
	}

	/**
	 * Searches within all user for the given socket.
	 *
	 * @param resource $socket
	 *	 The socket resource
	 *
	 * @return WebSocketUser The user by reference
	 */
	protected function &get_user_by_socket($socket) {
		$found = null;
		foreach ($this->users as &$user) {
			if ($user->socket == $socket) {
				$found = $user;
				break;
			}
		}
		return $found;
	}

	/**
	 * Outputs the given message if debug mode is one.
	 *
	 * @param string $message
	 *	 The message to log
	 * @param string $type
	 *	 The message type (use one of WebSocket::LOG_*)
	 *   (optional, default = WebSocket::LOG_INFO)
	 */
	public static function log_console($message = "", $type = self::LOG_INFO) {
		if (WebSocket::$debug) {
			echo date('Y-m-d H:i:s') . ' [' . $type . '] ' . $message . PHP_EOL;
		}
	}
}

