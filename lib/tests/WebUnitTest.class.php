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

	public function __construct(&$core = null) {
		parent::__construct($core);
		$this->client = new HttpClient();
	}


	/**
	 * Do a GET and store the content within the current content variable.
	 *
	 * @param string $url
	 *   the url.
	 * @param array $args
	 *   The GET arguments (optional, default = array())
	 *
	 * @return string the body content.
	 */
	public function do_get($url, $args = array()) {
		return $this->content = $this->client->do_get($url, $args);
	}

	/**
	 * Do a POST and store the content within the current content variable.
	 *
	 * @param string $url
	 *   the url.
	 * @param array $args
	 *   The GET arguments (optional, default = array())
	 *
	 * @return string the body content.
	 */
	public function do_post($url, $args = array()) {
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
