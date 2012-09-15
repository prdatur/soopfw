<?php
/**
 * The Websocket user which holds his unique id, socket and if the handshake was
 * done.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.net
 * @category Websocket
 */
class WebSocketUser {

	/**
	 * The unique id.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * The client socket.
	 *
	 * @var resource
	 */
	public $socket;

	/**
	 * If the user has done the Handshake or not.
	 *
	 * @var boolean
	 */
	public $handshake;

	/**
	 * Sends data to the user.
	 *
	 * @param string $payload
	 *	 The data to be send
	 * @param string $type
	 *	 The data type (optional, default = 'text')
	 * @param boolean $masked
	 *	 If the data should be masked or not (optional, default = false)
	 *
	 * @return boolean Wether the send succed or not
	 */
	public function send($payload, $type = 'text', $masked = false) {
		return WebSocket::write_buffer($this->socket, WebSocketHybi10::encode($payload, $type, $masked));
	}

	/**
	 * Closes the connection to the client.
	 *
	 * @param int $status_code
	 *	 The Websocket status code (optional, default = 1000)
	 *
	 * @return boolean Wether the client got correct closed or not.
	 */
	public function close($status_code = 1000) {
		$data = str_split(sprintf('%016b', $status_code), 8);
		$data[0] = chr(bindec($data[0]));
		$data[1] = chr(bindec($data[1]));
		$data = implode('', $data);

		switch ($status_code) {
			case 1000:
				$data .= 'normal closure';
				break;

			case 1001:
				$data .= 'going away';
				break;

			case 1002:
				$data .= 'protocol error';
				break;

			case 1003:
				$data .= 'unknown data (opcode)';
				break;

			case 1004:
				$data .= 'frame too large';
				break;

			case 1007:
				$data .= 'utf8 expected';
				break;

			case 1008:
				$data .= 'message violates server policy';
				break;
		}

		if ($this->send($data, 'close', false) === false) {
			return false;
		}

		return stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
	}
}


