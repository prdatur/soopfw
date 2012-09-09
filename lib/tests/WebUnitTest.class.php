<?php
/**
 * Provide a unit zest with additional web test features.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Testing
 */
class WebUnitTest extends UnitTest {

	/**
	 * The page content.
	 *
	 * @var string
	 */
	protected $content = '';

	/**
	 * Snoopy web client.
	 *
	 * @var HttpClient
	 */
	protected $client = null;

	/**
	 * The base url to do queries.
	 * @var string
	 */
	private $base_domain = '';

	/**
	 * The session id for the client.
	 *
	 * @var string
	 */
	private $client_session_id = "";

	public function __construct(&$core = null) {
		parent::__construct($core);

		$this->base_domain = 'http://' . $this->core->core_config('core', 'domain');

		$this->client = new HttpClient();
		$this->client->do_get($this->base_domain . '/');
		if (preg_match("/" . preg_quote($this->core->core_config('core', 'domain'), '/') . "\t+FALSE\t+\/\t+FALSE\t+0\t+PHPSESSID\t+(.+)\n$/", file_get_contents($this->client->get_cookie_file_path()), $matches)) {
			$this->client_session_id = $matches[1];
			file_put_contents(SITEPATH . '/uploads/session_is_test_' . $this->client_session_id, '1');
		}
	}

	/**
	 * Clean up
	 */
	public function __destruct() {
		// Remove test created file to identify this connection as a test envoirement.
		if (!empty($this->client_session_id) && file_exists(SITEPATH . '/uploads/session_is_test_' . $this->client_session_id)) {
			@unlink(SITEPATH . '/uploads/session_is_test_' . $this->client_session_id);
		}
	}

	/**
	 * Do a GET and store the content within the current content variable.
	 *
	 * @param string $url
	 *   the url.
	 * @param array $args
	 *   The GET arguments (optional, default = array())
	 * @param boolean $full_path
	 *   If set to true the provided url will be direct executed,
	 *   else it will prepend the base_domain
	 *   which is http://{domain} (optional, default = false)
	 *
	 * @return string the body content.
	 */
	public function do_get($url, $args = array(), $full_path = false) {
		if ($full_path === false && !empty($url) && $url{0} !== '/') {
			$url = '/' . $url;
		}

		if ($full_path === false) {
			$url = $this->base_domain . $url;
		}

		return $this->content = $this->client->do_get($url, $args);
	}

	/**
	 * Do a POST and store the content within the current content variable.
	 *
	 * @param string $url
	 *   the url.
	 * @param array $args
	 *   The GET arguments (optional, default = array())
	 * @param boolean $full_path
	 *   If set to true the provided url will be direct executed,
	 *   else it will prepend the base_domain
	 *   which is http://{domain} (optional, default = false)
	 *
	 * @return string the body content.
	 */
	public function do_post($url, $args = array(), $full_path = false) {
		if ($full_path === false && !empty($url) && $url{0} !== '/') {
			$url = '/' . $url;
		}

		if ($full_path === false) {
			$url = $this->base_domain . $url;
		}

		return $this->content = $this->client->do_post($url, $args);
	}

	/**
	 * Check if the current fetched content is empty.
	 *
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 */
	public function assert_web_empty($description, $message = "") {
		return $this->assert_empty($this->content, $description, $message);
	}

	/**
	 * Check if the current fetched content is not empty.
	 *
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 */
	public function assert_web_not_empty($description, $message = "") {
		return $this->assert_not_empty($this->content, $description, $message);
	}

	/**
	 * Check if $pattern is found within the current fetched content using regular expression.
	 *
	 * @param string $pattern
	 *   the pattern for preg_match().
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 */
	public function assert_web_regexp($pattern, $description, $message = "") {
		return $this->assert_regexp($this->content, $pattern, $description, $message);
	}

	/**
	 * Check if $pattern is NOT found within the current fetched content using regular expression.
	 *
	 * @param string $pattern
	 *   the pattern for preg_match().
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 */
	public function assert_web_not_regexp($pattern, $description, $message = "") {
		return $this->assert_not_regexp($this->content, $pattern, $description, $message);
	}

}
?>
