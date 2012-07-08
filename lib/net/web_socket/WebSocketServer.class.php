<?php
/**
 * Provides an abstract class which could be extended to provide a websocket server.
 * 
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 * @category Websocket
 */
abstract class WebSocketServer extends WebSocketListener {

	/**
	 * The master socket resource.
	 *
	 * @var resource
	 */
	protected $master;

	/**
	 * Holds all allowed origins.
	 * If this is empty, no checks will be performed.
	 *
	 * @var array
	 */
	protected $allowed_origins = array();

	/**
	 * Construct.
	 *
	 * Starts and listen as a WebSocket server.
	 *
	 * @param string $address
	 *	 The server address to listen.
	 * @param int $port
	 *	 The server port to listen.
	 */
 	public function __construct($address, $port) {
		parent::__construct();
		error_reporting(E_ALL);
		ob_implicit_flush(true);

		$this->master = stream_socket_server('tcp://'.$address . ':' . $port, $errno, $err, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, stream_context_create()) or die("socket_create() failed");
		$this->sockets[] = $this->master;
		WebSocket::log_console("Server Started : ".date('Y-m-d H:i:s'));
		WebSocket::log_console("Listening on   : ".$address." port ".$port);
		WebSocket::log_console("Master socket  : ".$this->master."\n");

		$this->listen();
	}

	/**
	 * Listen for changed sockets
	 */
	protected function listen() {
		while (true) {
			$changed_sockets = $this->sockets;
			@stream_select($changed_sockets, $write = null, $except = null, 0, 5000);
			foreach ($changed_sockets as $socket) {
				if ($socket == $this->master) {
					if (($client_socket = stream_socket_accept($this->master)) === false) {
						WebSocket::log_console('Socket error: ' . socket_strerror(socket_last_error($ressource)));
						continue;
					}
					$this->connect($client_socket);
				}
				else {
					$data = $this->read_buffer($socket);
					$bytes = strlen($data);

					if ($bytes === 0 || $data === false) {
						$this->disconnect($socket);
						continue;
					}

					$user = $this->get_user_by_socket($socket);

					if(!$user->handshake) {
						$hybi10 = new WebSocketHybi10($user);
						if(!$hybi10->handshake($data, $this->allowed_origins)) {
							$this->disconnect($user->socket);
							continue;
						}
						$this->on_connect($user);
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
	}

	/**
	 * Connects the user.
	 *
	 * @param resource &$socket
	 *	 The client socket resource
	 */
	protected function connect(&$socket) {
		$user = new WebSocketUser();
		$user->id = uniqid();
		$user->socket = $socket;

		$this->users[(int) $socket] = &$user;
		$this->sockets[$user->id] = $socket;
		WebSocket::log_console("User " . $user->id . " connected!");
	}

	/**
	 * Adds a domain to the allowed origin storage.
	 *
	 * @param sting $domain
	 *   A domain name from which connections to server are allowed.
	 */
	public function set_allowed_origin($domain) {
		if(preg_match("/(https?:\/\/)?(www\.)?([^\/]+)(\/.*|$)/is", $domain, $matches)) {
			$this->allowed_origins[$matches[2]] = true;
		}
	}

	/**
	 * Will be called if a new user connects and finished the handshake.
	 *
	 * @param WebSocketUser $client
	 *   The client from which connected.
	 */
	abstract protected function on_connect(WebSocketUser &$client);
}
?>