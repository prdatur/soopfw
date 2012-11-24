<?php
/**
 * Provide a unit zest with additional web test features.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
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

	/**
	 * This holds the current user.
	 *
	 * @var UserObj
	 */
	protected $current_user = null;

	/**
	 * Holds the last csrf token.
	 *
	 * @var string
	 */
	protected $csrf_token = '';

	/**
	 * Holds the current report id which is used to display the opened pages.
	 *
	 * @var array
	 */
	protected $report_id = "";

	/**
	 * Holds the form parser.
	 * This variable is only available after parse_forms() is called.
	 *
	 * @var UnitTestFormParser
	 */
	protected $form_parser = null;

	/**
	 * Holds the report counter.
	 *
	 * @var int
	 */
	public static $report_counter = array();

	public function __construct(&$core = null) {
		parent::__construct($core);

		$this->base_domain = 'http://' . $this->core->core_config('core', 'domain') . '/en';
		$this->report_id = md5(uniqid());
		WebUnitTest::$report_counter[$this->report_id] = 0;
		$this->client = new HttpClient();
		$this->client->do_get($this->base_domain . '/');
		if (preg_match("/" . preg_quote($this->core->core_config('core', 'domain'), '/') . "\t+FALSE\t+\/\t+FALSE\t+0\t+PHPSESSID\t+(.+)\n$/", file_get_contents($this->client->get_cookie_file_path()), $matches)) {
			$this->client_session_id = $matches[1];
			$this->core->mcache_set_prefix($this->original_table_prefix);
			$this->core->mcache($this->report_id, true);
			file_put_contents(SITEPATH . '/uploads/session_is_test_' . $this->client_session_id, $this->report_id);
		}
		$this->core->mcache_set_prefix('test_' . $this->original_table_prefix);
	}

	/**
	 * Clean up
	 */
	public function __destruct() {
		// Remove test created file to identify this connection as a test envoirement.
		if (!empty($this->client_session_id) && file_exists(SITEPATH . '/uploads/session_is_test_' . $this->client_session_id)) {
			@unlink(SITEPATH . '/uploads/session_is_test_' . $this->client_session_id);
		}

		$this->core->mcache_set_prefix($this->original_table_prefix);
		$this->core->memcache_obj->delete($this->report_id);
		$this->core->mcache_set_prefix('test_' . $this->original_table_prefix);

		$this->core->mcache('webtest_report::' . $this->report_id . '::max_counter', WebUnitTest::$report_counter[$this->report_id]);
		if (WebUnitTest::$report_counter[$this->report_id] > 0) {
			$this->core->message(t('WebTest request reports viewable at @url', array(
				'@url' => 'http://' . $this->core->core_config('core', 'domain') . '/admin/system/view_webtest_report/' . $this->report_id. '/1',
			)));
		}
	}

	public function login($username) {
		if (!empty($this->current_user)) {
			$this->do_get('/user/logout');
		}

		$accounts = $this->core->cache('tests', 'user_accounts');
		if (!$this->assert_true(isset($accounts[$username]), t('Username: @username does not exist.', array('@username' => $username)))) {
			return;
		}

		$this->do_get('/user/login.html');
		if ($this->assert_true((preg_match("/type=\"submit\"\s+value=\"([^\"]+)\"\s+name=\"soopfw_login\"/", $this->content, $matches) == 1), t('Validate user login form'))) {
			$this->do_post('/user/login.html', array(
				'soopfw_login' => $matches[1],
				'user' => $username,
				'pass' => $accounts[$username]['password'],
			), true);

			$this->assert_web_regexp('/<a[^>]+href\s*=\s*"[^"]*' . $accounts[$username]['user_id'] . '"[^>]+>My account<\/a>/', t('Check valid login'));
			$this->current_user = new UserObj($accounts[$username]['user_id']);
		}
	}

	/**
	 * Parses all forms which are found within the current content.
	 */
	public function parse_forms() {
		$this->form_parser = new UnitTestFormParser();
		$this->form_parser->parse_forms($this->content);
	}

	/**
	 * Do a GET and store the content within the current content variable.
	 *
	 * @param string $url
	 *   the url.
	 * @param array $args
	 *   The GET arguments (optional, default = array())
	 * @param boolean $use_ssl
	 *   Set to true to force ssl
	 *   this will replace http to https if set to true
	 *   (optional, default = false)
	 * @param boolean $full_path
	 *   If set to true the provided url will be direct executed,
	 *   else it will prepend the base_domain
	 *   which is http://{domain} (optional, default = false)
	 *
	 * @return string the body content.
	 */
	public function do_get($url, $args = array(), $use_ssl = false, $full_path = false) {
		$this->form_parser = null;
		if ($full_path === false && !empty($url) && $url{0} !== '/') {
			$url = '/' . $url;
		}

		if ($use_ssl == false) {
			$use_ssl = preg_match('/^\/admin\//', $url);
		}

		if ($full_path === false) {
			$url = $this->base_domain . $url;
		}

		$this->content = $this->client->do_get($url, $args, $use_ssl);
		if (preg_match('/<\s*input\s*type\s*=\s*"\s*hidden\s*"\s*class\s*=\s*"[^"]*inputs[^"]*"\s*name\s*=\s*"[^"]*_submit"\s*value\s*=\s*"(.+)"\s*id\s*=\s*"[^"]+_submit"\s*\/\s*>/', $this->content, $matches)) {
			$this->csrf_token = $matches[1];
		}

		$this->core->mcache('webtest_report::' . $this->report_id . '::' . ++WebUnitTest::$report_counter[$this->report_id], array(
			'type' => 'get',
			'url' => $url,
			'args' => $args,
			'data' => $this->content,
		), 1800);

		$this->assert_default_web_request($url, $args);
		return $this->content;
	}

	/**
	 * Do a Ajax-GET and store the content within the current content variable.
	 * The returning content will be checked for valid json.
	 *
	 * @param string $url
	 *   the url.
	 * @param array $args
	 *   The GET arguments (optional, default = array())
	 * @param boolean $use_ssl
	 *   Set to true to force ssl
	 *   this will replace http to https if set to true
	 *   (optional, default = false)
	 * @param boolean $full_path
	 *   If set to true the provided url will be direct executed,
	 *   else it will prepend the base_domain
	 *   which is http://{domain} (optional, default = false)
	 *
	 * @return string the body content.
	 */
	public function do_ajax_get($url, $args = array(), $use_ssl = false, $full_path = false) {
		$this->do_get($url, $args, $use_ssl, $full_path);
		$json = json_decode($this->content, true);
		if ($this->assert_true(!empty($json), t('Ajax get "@url" did not returned valid json, return was\n @return', array(
			'@url' => $url,
			'@return' => $this->content,
		)))) {
			$this->content = $json;
			return true;
		}
		return false;
	}

	/**
	 * Do a POST and store the content within the current content variable.
	 *
	 * @param string $url
	 *   the url.
	 * @param array $args
	 *   The POST arguments (optional, default = array())
	 * @param boolean $use_ssl
	 *   Set to true to force ssl
	 *   this will replace http to https if set to true
	 *   (optional, default = false)
	 * @param boolean $full_path
	 *   If set to true the provided url will be direct executed,
	 *   else it will prepend the base_domain
	 *   which is http://{domain} (optional, default = false)
	 *
	 * @return string the body content.
	 */
	public function do_post($url, $args = array(), $use_ssl = false, $full_path = false) {
		$this->form_parser = null;
		if ($full_path === false && !empty($url) && $url{0} !== '/') {
			$url = '/' . $url;
		}

		if ($use_ssl == false) {
			$use_ssl = preg_match('/^\/admin\//', $url);
		}

		if ($full_path === false) {
			$url = $this->base_domain . $url;
		}

		$this->content = $this->client->do_post($url, $args, $use_ssl);
		if (preg_match('/<\s*input\s*type\s*=\s*"\s*hidden\s*"\s*class\s*=\s*"[^"]*inputs[^"]*"\s*name\s*=\s*"[^"]*_submit"\s*value\s*=\s*"(.+)"\s*id\s*=\s*"[^"]+_submit"\s*\/\s*>/', $this->content, $matches)) {
			$this->csrf_token = $matches[1];
		}

		$this->core->mcache('webtest_report::' . $this->report_id . '::' . ++WebUnitTest::$report_counter[$this->report_id], array(
			'type' => 'post',
			'url' => $url,
			'args' => $args,
			'data' => $this->content,
		), 1800);

		$this->assert_default_web_request($url, $args);
		return $this->content;
	}

	/**
	 * Do a Ajax-POST and store the content within the current content variable.
	 * The returning content will be checked for valid json.
	 *
	 * @param string $url
	 *   the url.
	 * @param array $args
	 *   The POST arguments (optional, default = array())
	 * @param boolean $use_ssl
	 *   Set to true to force ssl
	 *   this will replace http to https if set to true
	 *   (optional, default = false)
	 * @param boolean $full_path
	 *   If set to true the provided url will be direct executed,
	 *   else it will prepend the base_domain
	 *   which is http://{domain} (optional, default = false)
	 *
	 * @return string the body content.
	 */
	public function do_ajax_post($url, $args = array(), $use_ssl = false, $full_path = false) {
		$this->do_post($url, $args, $use_ssl, $full_path);
		$json = json_decode($this->content, true);
		if ($this->assert_true(!empty($json), t('Ajax post "@url" did not returned valid json, return was\n@return', array(
			'@url' => $url,
			'@return' => $this->content,
		)))) {
			$this->content = $json;
			return true;
		}
		return false;
	}

	/**
	 * Check if the given content is a valid ajax return and the code equals the given one.
	 *
	 * @param int $code
	 *   the code to check. (Use one of AjaxModul::ERROR_* or AjaxModul::SUCCESS*)
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 */
	public function assert_ajax_code($code, $description, $message = "") {
		if ($this->assert_true(is_array($this->content), $description, t('Current result is not an array'))) {
			if ($this->assert_true(isset($this->content['code']), $description, t('Current result array has not a code entry'))) {
				$this->assert_equals($code, $this->content['code'], $description, $message);
			}
		}
	}

	/**
	 * Check if the given content is a valid ajax return and the description equals the given one.
	 *
	 * @param string $desc
	 *   the description to check.
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 */
	public function assert_ajax_description($desc, $description, $message = "") {
		if ($this->assert_true(is_array($this->content), $description, t('Current result is not an array, instead it is: @content'))) {
			if ($this->assert_true(isset($this->content['code']), $description, t('Current result array has not a code entry'))) {
				$this->assert_equals($desc, $this->content['desc'], $description, $message);
			}
		}
	}

	/**
	 * Checks the returning result from a query for default errors like
	 * php notice / error messages.
	 *
	 * @param string $url
	 *   the request url
	 * @param array &$args
	 *   the arguments for the query (optional, default = array())
	 */
	protected function assert_default_web_request($url, Array &$args = array()) {
		$errorType = 'ERROR|WARNING|PARSING ERROR|NOTICE|CORE ERROR|CORE WARNING|COMPILE ERROR|COMPILE WARNING|USER ERROR|USER WARNING|USER NOTICE|STRICT NOTICE|RECOVERABLE ERROR|CAUGHT EXCEPTION \([0-9]+\)';
		$this->assert_web_not_regexp('/(' . $errorType . '): .* in .* on line [0-9]+/s', t("Check php errors: !url\nArguments: !args", array('!url' => $url, '!args' => print_r($args, true))), "Found one or more php error within the requested page.");
	}

	/**
	 * Check if the given form, identified by $form_id, exist.
	 *
	 * @param string $form_id
	 *   the form id to check.
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 */
	public function assert_form_exist($form_id, $description, $message = "") {
		if ($this->form_parser === null) {
			$this->parse_forms();
		}
		if (empty($message)) {
			$message = t('Form "@form_id" not found', array(
				'@form_id' => $form_id,
			));
		}
		$this->assert_true($this->form_parser->form_exist($form_id), $description, $message);
	}

	/**
	 * Check if the given form field, identified by $form_id and $field_id, exist.
	 *
	 * @param string $form_id
	 *   the form id.
	 * @param string $field_id
	 *   the form field id.
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 */
	public function assert_form_field_exist($form_id, $field_id, $description, $message = "") {
		if ($this->form_parser === null) {
			$this->parse_forms();
		}
		if (empty($message)) {
			$message = t('Form field "@form_id/@field_id" not found', array(
				'@form_id' => $form_id,
				'@field_id' => $field_id,
			));
		}
		$this->assert_true($this->form_parser->form_field_exist($form_id, $field_id), $description, $message);
	}

	/**
	 * Check if the given form field tag, identified by $form_id, $field_id and $tag, exist.
	 *
	 * @param string $form_id
	 *   the form id.
	 * @param string $field_id
	 *   the form field id.
	 * @param string $tag
	 *   the tag name.
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 */
	public function assert_form_field_tag_exist($form_id, $field_id, $tag, $description, $message = "") {
		if ($this->form_parser === null) {
			$this->parse_forms();
		}
		if (empty($message)) {
			$message = t('Form field tag "@form_id/@field_id/@tag" not found', array(
				'@form_id' => $form_id,
				'@field_id' => $field_id,
				'@tag' => $tag,
			));
		}
		$this->assert_true($this->form_parser->form_field_tag_exist($form_id, $field_id, $tag), $description, $message);
	}

	/**
	 * Check if the given form field tag, identified by $form_id, $field_id and $tag, does not exist.
	 *
	 * @param string $form_id
	 *   the form id.
	 * @param string $field_id
	 *   the form field id.
	 * @param string $tag
	 *   the tag name.
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 */
	public function assert_form_field_tag_exist_not($form_id, $field_id, $tag, $description, $message = "") {
		if ($this->form_parser === null) {
			$this->parse_forms();
		}
		if (empty($message)) {
			$message = t('Form field tag "@form_id/@field_id/@tag" found', array(
				'@form_id' => $form_id,
				'@field_id' => $field_id,
				'@tag' => $tag,
			));
		}
		$this->assert_false($this->form_parser->form_field_tag_exist($form_id, $field_id, $tag), $description, $message);
	}

	/**
	 * Check if the given form field tag, identified by $form_id, $field_id and $tag, exist and equals given $value.
	 *
	 * @param string $form_id
	 *   the form id.
	 * @param string $field_id
	 *   the form field id.
	 * @param string $tag
	 *   the tag name.
	 * @param string $value
	 *   the value to be checked.
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 */
	public function assert_form_field_tag_equals($form_id, $field_id, $tag, $value, $description, $message = "") {
		if ($this->form_parser === null) {
			$this->parse_forms();
		}
		$this->assert_equals($this->form_parser->get_form_field_tag($form_id, $field_id, $tag), $value, $description, $message);
	}

	/**
	 * Check if the given form field tag, identified by $form_id, $field_id and $tag, exist and equals NOT the given $value.
	 *
	 * @param string $form_id
	 *   the form id.
	 * @param string $field_id
	 *   the form field id.
	 * @param string $tag
	 *   the tag name.
	 * @param string $value
	 *   the value to be checked.
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 */
	public function assert_form_field_tag_equals_not($form_id, $field_id, $tag, $value, $description, $message = "") {
		if ($this->form_parser === null) {
			$this->parse_forms();
		}
		$this->assert_not_equals($this->form_parser->get_form_field_tag($form_id, $field_id, $tag), $value, $description, $message);
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

	/**
	 * Check if ajax returned a valid success return code.
	 *
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 */
	public function assert_ajax_success($description, $message = "") {
		if (empty($message)) {
			if (isset($this->content['code'])) {
				$message = t('Returning ajax code is not valid, code was: icode (message: @desc)', array(
					'icode' => $this->content['code'],
					'@desc' => (!empty($this->content['desc'])) ? $this->content['desc'] : t('none'),
				));
			}
			else {
				$message = t('Return code not found, maybe invalid json.');
			}
		}

		$test = (isset($this->content['code']) && $this->content['code'] >= 200 && $this->content['code'] <= 299);
		$this->add_log('assert_ajax_valid_return', $description, $message, $test);
		return $test;
	}

}