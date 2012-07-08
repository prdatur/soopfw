<?php
/**
 * Websocket Handshake version Hybi10
 * 
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 * @category Websocket
 */
class WebSocketHybi10 extends WebSocketHandshake {

	/**
	 * Do the handshake.
	 *
	 * @param string $data
	 * 	 incoming request data
	 * @return boolean
	 * 	 Wether the handshake succeed or not, false on error
	 */
	public function handshake($data) {

		if (($headers = parent::handshake($data)) === false) {
			return false;
		}

		// Do handyshake: (hybi-10).
		$sec_key = $headers['Sec-WebSocket-Key'];
		$sec_accept = base64_encode(pack('H*', sha1($sec_key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
		$response = "HTTP/1.1 101 Switching Protocols\r\n";
		$response.= "Upgrade: websocket\r\n";
		$response.= "Connection: Upgrade\r\n";
		$response.= "Sec-WebSocket-Accept: " . $sec_accept . "\r\n";
		$response.= "Sec-WebSocket-Protocol: " . substr($headers['path'], 1) . "\r\n\r\n";
		if (WebSocket::write_buffer($this->client->socket, $response) === false) {
			return false;
		}
		$this->client->handshake = true;
		WebSocket::log_console('Handshake sent');
		return true;
	}

	/**
	 * Encodes the incoming data to hybi10.
	 *
	 * @param string $data
	 *	 The string to be encoded
	 * @param string $type
	 *	 The data type
	 * @param boolean $masked
	 *	 Wether we want to mask it or not
	 *
	 * @return string
	 *	 If success it returns the encoded string, else FALSE
	 */
	public static function encode($data, $type = 'text', $masked = true) {
		$frame_head = array();
		$frame = '';
		$payload_length = strlen($data);

		switch ($type) {
			case 'text':
				// first byte indicates FIN, Text-Frame (10000001):
				$frame_head[0] = 129;
				break;

			case 'close':
				// first byte indicates FIN, Close Frame(10001000):
				$frame_head[0] = 136;
				break;

			case 'ping':
				// first byte indicates FIN, Ping frame (10001001):
				$frame_head[0] = 137;
				break;

			case 'pong':
				// first byte indicates FIN, Pong frame (10001010):
				$frame_head[0] = 138;
				break;
		}

		// set mask and payload length (using 1, 3 or 9 bytes)
		if ($payload_length > 65535) {
			$payload_length_bin = str_split(sprintf('%064b', $payload_length), 8);
			$frame_head[1] = ($masked === true) ? 255 : 127;
			for ($i = 0; $i < 8; $i++) {
				$frame_head[$i + 2] = bindec($payload_length_bin[$i]);
			}
			// most significant bit MUST be 0 (close connection if frame too big)
			if ($frame_head[2] > 127) {
				$this->close(1004);
				return false;
			}
		}
		elseif ($payload_length > 125) {
			$payload_length_bin = str_split(sprintf('%016b', $payload_length), 8);
			$frame_head[1] = ($masked === true) ? 254 : 126;
			$frame_head[2] = bindec($payload_length_bin[0]);
			$frame_head[3] = bindec($payload_length_bin[1]);
		}
		else {
			$frame_head[1] = ($masked === true) ? $payload_length + 128 : $payload_length;
		}

		// convert frame-head to string:
		foreach (array_keys($frame_head) as $i) {
			$frame_head[$i] = chr($frame_head[$i]);
		}
		if ($masked === true) {
			// generate a random mask:
			$mask = array();
			for ($i = 0; $i < 4; $i++) {
				$mask[$i] = chr(rand(0, 255));
			}

			$frame_head = array_merge($frame_head, $mask);
		}
		$frame = implode('', $frame_head);

		// append data to frame:
		for ($i = 0; $i < $payload_length; $i++) {
			$frame .= ($masked === true) ? $data[$i] ^ $mask[$i % 4] : $data[$i];
		}

		return $frame;
	}

	/**
	 * Decodes outgoing data to hybi10
	 *
	 * @param string $data
	 *	 The data
	 * @return mixed
	 *	 The data on success, else FALSE
	 */
	public static function decode($data) {
		$payload_length = '';
		$mask = '';
		$unmaskedPayload = '';
		$decoded_data = array();

		// estimate frame type:
		$firstByteBinary = sprintf('%08b', ord($data[0]));
		$secondByteBinary = sprintf('%08b', ord($data[1]));
		$opcode = bindec(substr($firstByteBinary, 4, 4));
		$is_masked = ($secondByteBinary[0] == '1') ? true : false;
		$payload_length = ord($data[1]) & 127;

		// close connection if unmasked frame is received:
		if ($is_masked === false) {
			$this->close(1002);
		}

		switch ($opcode) {
			// Text frame.
			case 1:
				$decoded_data['type'] = 'text';
				break;

			case 2:
				$decoded_data['type'] = 'binary';
				break;

			// Connection close frame.
			case 8:
				$decoded_data['type'] = 'close';
				break;

			// Ping frame.
			case 9:
				$decoded_data['type'] = 'ping';
				break;

			// Pong frame.
			case 10:
				$decoded_data['type'] = 'pong';
				break;

			default:
				// Close connection on unknown opcode.
				$this->close(1003);
				break;
		}

		if ($payload_length === 126) {
			$mask = substr($data, 4, 4);
			$payload_offset = 8;
			$data_length = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payload_offset;
		}
		elseif ($payload_length === 127) {
			$mask = substr($data, 10, 4);
			$payload_offset = 14;
			$tmp = '';
			for ($i = 0; $i < 8; $i++) {
				$tmp .= sprintf('%08b', ord($data[$i + 2]));
			}
			$data_length = bindec($tmp) + $payload_offset;
			unset($tmp);
		}
		else {
			$mask = substr($data, 2, 4);
			$payload_offset = 6;
			$data_length = $payload_length + $payload_offset;
		}

		/**
		 * We have to check for large frames here. socket_recv cuts at 1024 bytes
		 * so if websocket-frame is > 1024 bytes we have to wait until whole
		 * data is transferd.
		 */
		if (strlen($data) < $data_length) {
			return false;
		}

		if ($is_masked === true) {
			for ($i = $payload_offset; $i < $data_length; $i++) {
				$j = $i - $payload_offset;
				if (isset($data[$i])) {
					$unmaskedPayload .= $data[$i] ^ $mask[$j % 4];
				}
			}
			$decoded_data['data'] = $unmaskedPayload;
		}
		else {
			$payload_offset = $payload_offset - 4;
			$decoded_data['data'] = substr($data, $payload_offset);
		}

		return $decoded_data;
	}

}

?>
