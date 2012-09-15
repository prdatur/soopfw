<?php
/**
 * Provides the base class for a websocket service.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.net
 * @category Websocket
 */
abstract class WebSocketListener extends WebSocket {

	/**
	 * Configuration how much request per limit is the maximum for a client ip.
	 *
	 * @var int
	 */
	public $max_requests_per_minute = 250;

	/**
	 * Holds all connection request per user.
	 * This is used to handle max request limits.
	 *
	 * @var array
	 */
	protected $request_storage = array();

	/**
	 * Listen for changed sockets
	 */
	protected function listen() {
		while (true) {
			$changed_sockets = $this->sockets;
			@stream_select($changed_sockets, $write = null, $except = null, 0, 5000);
			foreach ($changed_sockets as $socket) {

				$data = $this->read_buffer($socket);
				$bytes = strlen($data);

				if ($bytes === 0 || $data === false) {
					$this->disconnect($socket);
					continue;
				}

				$user = $this->get_user_by_socket($socket);

				if(!$user->handshake) {
					continue;
				}

				if (!$this->check_request_limit($user)) {
					$this->disconnect($user->socket);
					continue;
				}

				$this->handle($data, $user);

			}
		}
	}

	/**
	 * Disconnects the user.
	 *
	 * @param resource $socket
	 *	 The client socket resource
	 */
	protected function disconnect($socket) {
		if(isset($this->users[(int) $socket])) {
			/* @var $client WebSocketUser */
			$client = $this->users[(int) $socket];
			$client->close();
			unset($this->sockets[$client->id]);
			unset($this->users[(int) $socket]);
		}
		WebSocket::log_console("Disconnect user");
	}

	/**
	 * Checks if a client has reached its max. requests per minute limit.
	 *
	 * @param WebSocketUser $user
	 *	 The user
	 *
	 * @return bool true if limit is not yet reached. false if request limit is reached.
	 */
	protected function check_request_limit(WebSocketUser $user) {
		// No data in storage - no danger:
		if (!isset($this->request_storage[$user->id])) {
			$this->request_storage[$user->id] = array(
				'last_request' => time(),
				'total_requests' => 1
			);
			return true;
		}

		// time since last request > 1min - no danger:
		if (time() - $this->request_storage[$user->id]['last_request'] > 60) {
			$this->request_storage[$user->id] = array(
				'last_request' => time(),
				'total_requests' => 1
			);
			return true;
		}

		// did requests in last minute - check limits:
		if ($this->request_storage[$user->id]['total_requests'] > $this->max_requests_per_minute) {
			return false;
		}

		$this->request_storage[$user->id]['total_requests']++;
		return true;
	}

	/**
	 * Needs to be implemented to handle the data which we recieved from the
	 * client.
	 *
	 * @param string $data
	 *	 The data which we got from the client.
	 * @param WebSocketUser $client
	 *   The client from which we recieved the data.
	 */
	abstract protected function on_data($data, WebSocketUser &$client);

	/**
	 * Will be called if the user will be disconnected.
	 *
	 * @param WebSocketUser $client
	 *   The client from which connected.
	 */
	abstract protected function on_close(WebSocketUser &$client);

	/**
	 * Will be called if an error occured.
	 *
	 * @param WebSocketUser $client
	 *   The client from which connected.
	 */
	abstract protected function on_error(WebSocketUser &$client);
}

?>