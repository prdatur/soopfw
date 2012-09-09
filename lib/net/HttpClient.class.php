<?php
/**
 * Provide an easy lightwight http client which uses curl.
 * It can do post's and get's with or without ssl and/or arguments.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Tools
 */
class HttpClient extends Object {

	/**
	 * Holds the current fetched content.
	 *
	 * @var string
	 */
	protected $current_content = "";

	/**
	 * Wether to accept cookies or not
	 * @var boolean
	 */
	protected $usecookie = true;

	/**
	 * The file path where we store the cookie.
	 *
	 * @var string
	 */
	protected $cookie_file_path = '';

	/**
	 * Current referer.
	 *
	 * @var string
	 */
	protected $current_referer = "";

	/**
	 * Setup defaults.
	 */
	public function __construct() {
		parent::__construct();
		$this->cookie_file_path = SITEPATH . '/uploads/httpclient_cookie_' . uniqid() . '.txt';
	}

	/**
	 * Resets the hole request.
	 * This will clear current referer, current body content and current cookie file content
	 * if it is present.
	 */
	public function reset() {
		$this->current_content = "";
		if (!empty($this->cookie_file_path) && is_writable($this->cookie_file_path)) {
			file_put_contents($this->cookie_file_path, "");
		}
		$this->reset_referer();
	}

	/**
	 * Resets the current referer.
	 */
	public function reset_referer() {
		$this->current_referer = "";
	}

	/**
	 * Set/Change the cookie path.
	 *
	 * If previous cookie path is not empty we first try to delete it.
	 * The new path will be only active if the directory for the file exist and is writeable.
	 *
	 * @param string $file_path
	 *   the file path where we should store the cookies
	 *
	 * @return boolean returns true if file exists and is writeable
	 *   else false
	 */
	public function set_cookie_file_path($file_path) {
		$dir = dirname($this->cookie_file_path);

		if (file_exists($this->cookie_file_path)) {
			if (!is_writable($this->cookie_file_path)) {
				return false;
			}
		}
		else {
			if (!file_exists($dir) || !is_writable($dir)) {
				return false;
			}
		}

		if (!(empty($this->cookie_file_path)) && file_exists($this->cookie_file_path)) {
			@unlink($this->cookie_file_path);
		}

		$this->cookie_file_path = $file_path;

		return true;
	}

	/**
	 * Returns the full path to the cookie file.
	 *
	 * @return string the path.
	 */
	public function get_cookie_file_path() {
		return $this->cookie_file_path;
	}

	/**
	 * Clean up system before destroying the class.
	 */
	public function __destruct() {
		if (file_exists($this->cookie_file_path)) {
			@unlink($this->cookie_file_path);
		}
	}

	/**
	 * Send a GET request to the specified url.
	 *
	 * @param string $url
	 *   the url
	 * @param array $args
	 *   The arguments which will be appended through ? http_build_query()
	 *
	 * @return string the body content
	 */
	public function do_get($url, $args = array()) {
		if (!empty($args)) {
			if (!preg_match("/\?/", $url)) {
				$url .= "?";
			}

			$url .= http_build_query($args);
		}
		return $this->execute($url);
	}

	/**
	 * Send a GET request to the specified url.
	 *
	 * @param string $url
	 *   the url
	 * @param array $args
	 *   The arguments which will be appended through ? http_build_query()
	 *
	 * @return string the body content
	 */
	public function do_post($url, $args = array()) {

		$ch = curl_init();

		if (!empty($args)) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		}

		return $this->execute($url, $ch);
	}

	/**
	 * Sends a HTTP-Request to the specified url.
	 *
	 * @param string $url
	 *   the url.
	 * @param resource $ch
	 *   the ressource returned by curl_init
	 *   if not provided it will create a new one.
	 *   (optional, default = null)
	 *
	 * @return string the body content
	 */
	protected function execute($url, $ch = null) {

		if (empty($ch)) {
			$ch = curl_init();
		}

		curl_setopt($ch, CURLOPT_URL, $url);

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");

		if ($this->usecookie) {
			curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file_path);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file_path);
		}
		if ($this->current_referer != "") {
			curl_setopt($ch, CURLOPT_REFERER, $this->current_referer);
		}

		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		// Prevent a redirect loop.
		curl_setopt($ch, CURLOPT_MAXREDIRS, 15);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		curl_close($ch);
		$this->current_content = $result;

		$this->current_referer = $url;
		return $result;
	}
}
?>