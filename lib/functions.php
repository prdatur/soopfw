<?php

define("ENCODING", "UTF-8");

/**
 * Reads a string from the command line.
 *
 * @param string $question
 *	 the message to show in front of the waiting input line
 * @return string
 *	 The entered string
 */
function get_line($question) {
	echo $question;
	return trim(fgets(STDIN));
}

/**
 * Check a permission against the current user
 * 
 * @param string $perm 
 *   the permission to check
 * @return boolean true if current user has the permission, else false
 */
function has_perm($perm) {
	if (empty($GLOBALS['core'])) {
		return false;
	}
	$core = $GLOBALS['core'];
	/* @var $core Core */
	
	if (empty($core->right_manager)) {
		return false;
	}
	
	return $core->right_manager->has_perm($params['perm']);
}

/**
 * Reads the user input and validate it against a "yes" input.
 *
 * The following values will be return true:
 * 1,y,yes,z,zes,j,ja,true
 *
 * @param string $question
 *	 the message to show in front of the waiting input line
 * @param string $default
 *   the default value which will be used if the user just press enter
 * @return boolean
 *	 return true if the user entered a "true" (yes,y,j,ja,true,1)
 *   special behaviour on z,zes for keyboard layout english/german
 */
function get_boolean_input($question, $default = false) {
	$line = strtolower(get_line($question . ' [' . (($default) ? 'y' : 'n') . ']:'));
	if(empty($line)) {
		$line = $default;
	}
	switch($line) {
		case '1':
		case 'y':
		case 'yes':
		case 'z':
		case 'zes':
		case 'j':
		case 'ja':
		case 'true':
			return true;
	}
	return false;
}

/**
 * Reads the user input and returns the value
 *
 * @param string $question
 *	 the message to show in front of the waiting input line
 * @param string $default
 *   the default value which will be used if the user just press enter
 * @return string
 *	 returns the entered string, if left empty, default will returned
 */
function get_string_input($question, $default = '') {
	$line = get_line($question . ' [' . $default . ']:');
	if(empty($line)) {
		$line = $default;
	}
	return $line;
}

/**
 * Returns the entered string but the input will not be visible to the user.
 *
 * @param string $prompt
 *   The message to show
 * 
 * @return string
 *	 The entered string
 */
function prompt_silent($prompt = "Enter Password:") {
  if (preg_match('/^win/i', PHP_OS)) {
    $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
    file_put_contents(
      $vbscript, 'wscript.echo(InputBox("' . addslashes($prompt) . '", "", "password here"))');
    $command = "cscript //nologo " . escapeshellarg($vbscript);
    $password = rtrim(shell_exec($command));
    unlink($vbscript);
    return $password;
  }
  else {
    $command = "/usr/bin/env bash -c 'echo OK'";
    if (rtrim(shell_exec($command)) !== 'OK') {
      trigger_error("Can't invoke bash");
      return;
    }
    $command = "/usr/bin/env bash -c 'read -s -p \"" . addslashes($prompt) . "\" mypassword && echo \$mypassword'";
    $password = rtrim(shell_exec($command));
    echo "\n";
    return $password;
  }
}

/**
 * parse the given url into an array which we can use for action calling
 *
 * @param string $url the url
 * @return array the params for this url
 */
function parse_url_string($url) {
	$language = "";
	//This params will be provided within the action callback

	$override_params['module'] = "";
	$override_params['type'] = "";
	$override_params['action'] = "";
    $additional_function_params = array();
    if(!empty($url) && $url != '/') {
        $additional_function_params = explode('/', substr($url,1));

		if(count($additional_function_params) > 0) {
            if(preg_match('/^[a-z]{2}$/is',$additional_function_params[0], $matches)) {
                $language = $matches[0];
                array_shift($additional_function_params);
            }
        }
        if(preg_match('/.*\.([^\.]+)$/iUs', $url, $matches)) { //Handle custom file extensions
            switch($matches[1]) {
                case 'ajax'://We have an ajax extension so handle this as an ajax request and set the type
                    $override_params['type'] = 'ajax_request';
                    break;
                case 'ajax_html'://We have an ajax_html extension so handle this as an ajax_html (Normal PHP behaviour but do not display normal header and footer
                    $override_params['type'] = 'ajax_html';
                    break;
            }
        }
        $len = count($additional_function_params);
        for($i = 0; $i <= $len; $i++) {
            $shift = array_shift($additional_function_params);

            if(empty($override_params['module'])) {
                list($shift) = explode(".", $shift,2);
                $override_params['module'] = $shift;
                continue;
            }

            if(empty($override_params['action'])) {
                list($shift) = explode(".", $shift,2);
                $override_params['action'] = $shift;
                break;
            }

        }

    }

	$override_params['language'] = $language;
	$override_params['additional_function_params'] = $additional_function_params;
	return $override_params;
}

/**
 * Sends an email to the configured debug email
 * @param string $subject
 * @param string $message
 */
function debug_mail($subject, $message) {
	mail($GLOBALS['core']->config['core']['debug_email'], $GLOBALS['core']->config['core']['domain']." ( ".$subject." ) ", $message);
}

function fill_array($values, &$array) {
	foreach($values AS $val) {
		if(empty($array[$val])) {
			$array[$val] = "";
		}
	}
}

/**
 * Smarty truncate modifier plugin
 *
 * Type:     modifier<br>
 * Name:     truncate<br>
 * Purpose:  Truncate a string to a certain length if necessary,
 *             optionally splitting in the middle of a word, and
 *             appending the $etc string or inserting $etc into the middle.
 *
 * @link http://smarty.php.net/manual/en/language.modifier.truncate.php truncate (Smarty online manual)
 * @author Monte Ohrt <monte at ohrt dot com>
 * @param string $string input string
 * @param int $length lenght of truncated text
 * @param string $etc end string
 * @param boolean $break_words truncate at word boundary
 * @param boolean $middle truncate in the middle of text
 * @return string truncated string
 */
function truncate_soopfw($string, $length = 80, $etc = '...', $break_words = false, $middle = false) {
	if ($length == 0)
		return '';

	if (is_callable('mb_strlen')) {
		if (mb_strlen($string) > $length) {
			$length -= min($length, mb_strlen($etc));
			if (!$break_words && !$middle) {
				$string = preg_replace('/\s+?(\S+)?$/u', '', mb_substr($string, 0, $length + 1));
			}
			if (!$middle) {
				return mb_substr($string, 0, $length) . $etc;
			}
			else {
				return mb_substr($string, 0, $length / 2) . $etc . mb_substr($string, - $length / 2);
			}
		}
		else {
			return $string;
		}
	}
	else {
		if (strlen($string) > $length) {
			$length -= min($length, strlen($etc));
			if (!$break_words && !$middle) {
				$string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length + 1));
			}
			if (!$middle) {
				return substr($string, 0, $length) . $etc;
			}
			else {
				return substr($string, 0, $length / 2) . $etc . substr($string, - $length / 2);
			}
		}
		else {
			return $string;
		}
	}
}

/**
 * Check the given value against the email regexp, if checkdns is set to true (default) than the domain part will be checked if there is an mx record
 * @param string $value the email
 * @param boolean $checkDNS wether to check against the dns record for valid mx record or not
 * @return boolean true if valid, else false (optional, default = 'true')
 */
function checkMail($value, $checkDNS = true) {
	if (!preg_match("/^[a-zA-Z][a-zA-Z0-9\._-]+@[a-zA-Z][a-zA-Z0-9\._-]+\.+[a-z]{2,4}$/i", $value)) {
		return false;
	}
	else {
		$addr = $value;
		list($user, $host) = explode("@", $addr);
		if (!$checkDNS)
			return true;
		if (@checkdnsrr($host, "MX") || @checkdnsrr($host, "A")) {
			return true;
		}
		else {
			return false;
		}
	}
}

function floatcmp($left, $right, $precision = 10) { // are 2 floats equal
	$e = pow(10, $precision);
	$i1 = (int)($f1 * $e);
	$i2 = (int)($f2 * $e);
	return ($i1 == $i2);
}

/**
 * a > b
 */
function floatgtr($left, $right, $precision = 10) { // is one float bigger than another
	$e = pow(10, $precision);
	return ((int)($left * $e) > (int)($right * $e));
}

/**
 * a >= b
 */
function floatgtre($left, $right, $precision = 10) { // is one float bigger or equal than another
	$e = pow(10, $precision);
	return ((int)($left * $e) >= (int)($right * $e));
}

/**
 * a < b
 */
function floatltr($left, $right, $precision = 10) {
	$e = pow(10, $precision);
	return ((int)($left * $e) < (int)($right * $e));
}

/**
 * a <= b
 */
function floatltre($left, $right, $precision = 10) {
	$e = pow(10, $precision);
	return ((int)($left * $e) <= (int)($right * $e));
}

/**
 * Display the log message
 *
 * By default, only warnings and errors will be displayed, if 'verbose' is specified, it will also display notices.
 *
 * @param string $message
 * @param string $type
 * @return
 *   False in case of an error or failed type, True in all other cases.
 */
function consoleLog($message, $type='notice') {
	$red = "\033[31;40m\033[1m[%s]\033[0m";
	$yellow = "\033[1;33;40m\033[1m[%s]\033[0m";
	$green = "\033[1;32;40m\033[1m[%s]\033[0m";

	$verbose = true;

	$return = TRUE;
	switch ($type) {
		case 'plain':
			$type_msg = "";
			break;
		case 'warning' :
		case 'cancel' :
			$type_msg = sprintf($yellow, $type);
			break;
		case 'failed' :
		case 'error' :
			$type_msg = sprintf($red, $type);
			$return = FALSE;
			break;
		case 'ok' :
		case 'completed' :
		case 'success' :
		case 'status':
			$type_msg = sprintf($green, $type);
			break;
		case 'notice' :
		case 'message' :
		case 'info' :
			if (!$verbose) {
				// print nothing. exit cleanly.
				return TRUE;
			}
			$type_msg = sprintf($yellow, $type);
			break;
		default :
			return TRUE;
			break;
	}

	$columns = 80;

	$width[1] = 11;

	$width[0] = ($columns - 11);

	$format = sprintf("%%-%ds%%%ds", $width[0], $width[1]);

	// Place the status message right aligned with the top line of the error message.
	$message = wordwrap(rtrim($message), $width[0]);
	$lines = explode("\n", $message);
	$lines[0] = sprintf($format, $lines[0], $type_msg);
	$message = implode("\n", $lines) . "\n";

	// try to log to file
	if (defined('console_log_file')) {
		if (!is_file(console_log_file)) {
			$touch_status = @touch(console_log_file);
			if (!$touch_status) {
				trigger_error('Can not create file for loggin: ' . console_log_file, E_USER_ERROR);
			}
			elseif (!is_writable(console_log_file)) {
				trigger_error('Loggin file isn ot writeable: ' . console_log_file, E_USER_ERROR);
			}
			else {
				file_put_contents(console_log_file, $message, FILE_APPEND);
			}
		}
	}

	// if we run on cli, echo
	if (empty($_SERVER['HTTP_HOST'])) {
		$handle = STDERR;
		if (isset($handle)) {
			fwrite($handle, $message);
		}
		else {
			print $message;
		}
	}

	return $return;
}

function soopfw_shutdown() {
	global $translation_cache, $core;

	if ($core->module_enabled("translation")) {
		$query = " INSERT IGNORE INTO `" . TranslationKeysObj::TABLE . "` (`id`,`key`) VALUES ";
		$query_arr = array();
		foreach ($translation_cache AS $id => $trans) {
			$query_arr[] = "('" . safe($id) . "', '" . safe($trans) . "')";
		}

		$query .= implode(",", $query_arr);
		$core->db->query_master($query);
	}
}

/**
 * Translate the given key, which should be always the english part of the translation
 * @param string $key the language key
 * @param array $args an array with replacement array('search_key' => 'replace_value')
 * 			We can use this prefix in char in front of the search_key
 * 			i = intval, f = floatval, all other search_key's will be replaced with htmlspecialchars
 * @return string the translated string, or if the translations is not found return the given key
 */
function t($key, $args = array()) {
	static $key_cache = array();
	global $translation_cache, $core;
	$key = strtolower($key);
	$cache_key = $key."|".md5(json_encode($args));
	if(isset($key_cache[$cache_key])) {
		return $key_cache[$cache_key];
	}

	//$core = &$GLOBALS['core'];
	//Check if translation module is available
	$bbcode = new BBCodeParser();


	//Check if language is available
	if (!empty($core->lng)) {

		//Try to get the translation for the key and do replacements within language object
		$translated = $core->lng->get($key, "", $args);
		if (!empty($translated)) {
			$key = $translated;
		}
		else {
			$translation_cache[md5($key)] = $key;
		}
	}
	if(!empty($core)) {
		$cached_parsed = $core->mcache("core_translation_parsed:".md5($key));
		if(empty($cached_parsed)) {
			$m_key = md5($key);
			//Parse bbcode
			$key = $bbcode->parse($key);
			$core->mcache("core_translation_parsed:".$m_key, $key);
		}
		else {
			$key = $cached_parsed;
		}
	}
	else {
		$key = $bbcode->parse($key);
	}

	//We do not have found the translation so do the replacement on the english key string
	foreach ($args AS $k => $v) {
		switch (substr($k, 0, 1)) {
			case 'i': $v = (int)$v;
			case 'f': $v = (float)$v;
			default: $v = htmlspecialchars($v);
		}

		$key = str_replace($k, $v, $key);
	}
	$key_cache[$cache_key] = $key;
	//Return replaced untranslated english string
	return $key;
}

function objectsIntoArray($arrObjData, $arrSkipIndices = array()) {
	$arrData = array();

	// if input is object, convert into array
	if (is_object($arrObjData)) {
		$arrObjData = get_object_vars($arrObjData);
	}

	if (is_array($arrObjData)) {
		foreach ($arrObjData as $index => $value) {
			if (is_object($value) || is_array($value)) {
				$value = objectsIntoArray($value, $arrSkipIndices); // recursive call
			}
			if (in_array($index, $arrSkipIndices)) {
				continue;
			}
			$arrData[$index] = $value;
		}
	}
	return $arrData;
}

/**
 * Merge array $b into array $a, same keys in b will be overriden in a, the arrays can be multi dimensional
 * @param array $a the first array
 * @param array $b the second array
 * @return array
 */
function array_extend($a, $b) {
	foreach ($b as $k => $v) {
		if (is_array($v)) {
			if (!isset($a[$k])) {
				$a[$k] = $v;
			}
			else {
				$a[$k] = array_extend($a[$k], $v);
			}
		}
		else {
			$a[$k] = $v;
		}
	}
	return $a;
}

function quote($str, $delimiter = "/") {
	return preg_quote($str, $delimiter);
}

function mulsort(&$a) {
	asort($a);
	foreach ($a AS &$v) {
		if (is_array($v)) {
			mulsort($v);
		}
	}
}

/* * @function mksalt
 * Make a Salt
 *
 * @package functions
 * @param int Salt Length (optional)
 * @return string the salt as a string
 */

function mksalt($len = 9) {
	$pass = "";
	$abc = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 0);
	$a = 0;
	while ($a < $len) {
		shuffle($abc);
		$pass .= $abc[rand(0, count($abc) - 1)];
		$a++;
	}
	return $pass;
}

function generateClassList() {

	$argv = array(null,
		SITEPATH . '/', // root directory
		'true', // recursive?
		SITEPATH . '/config/classes.php', // filename or 'false' to display results
		'classes'	  // variable name of Array
	);

	$argc = count($argv);

	$_SERVER['argc'] = $argc;
	$_SERVER['argv'] = $argv;

	require_once(SITEPATH . '/lib/classlist_auto_generator.php');
}

function cmd($cmd) {
	$args = func_get_args();
	foreach ($args AS $k => &$arg) {
		$arg = escapeshellarg($arg);
	}
	unset($args[0]);
	$cmd = $cmd . " " . implode(" ", $args);
	debug_log("Proccess cmd: " . $cmd);
	return debug_log(shell_exec($cmd));
}

function debug_log($msg, $newline = true) {

	if (defined("DEBUG") && DEBUG === 1) {
		echo $msg;
		if ($newline === true) {
			echo "\n";
		}
	}
	return $msg;
}

function des_encode($text, $key, $output = "base64") {
	$td = mcrypt_module_open('tripledes', '', 'ecb', '');
	$key = substr($key, 0, mcrypt_enc_get_key_size($td));
	$iv_size = mcrypt_enc_get_iv_size($td);
	$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

	mcrypt_generic_init($td, $key, $iv);

	$c_t = mcrypt_generic($td, $text);

	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);

	if ($output == "base64") {
		return base64_encode($c_t);
	}
	return $c_t;
}

function des_decode($text, $key, $input = "base64") {
	if ($input == "base64") {
		$text = base64_decode($text);
	}
	$td = mcrypt_module_open('tripledes', '', 'ecb', '');
	$key = substr($key, 0, mcrypt_enc_get_key_size($td));
	$iv_size = mcrypt_enc_get_iv_size($td);
	$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

	mcrypt_generic_init($td, $key, $iv);

	$p_t = mdecrypt_generic($td, $text);

	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);
	$p_t = trim($p_t);
	$string = "";
	for ($i = 0; $i < strlen($p_t); $i++) {
		if (ord($p_t{$i}) == 4) {
			continue;
		}
		$string .= $p_t{$i};
	}
	return trim($string);
}

/**
 * Open an url on https using curl and return content
 *
 * @author hatem <info@phptunisie.net>
 * @param string url            The url to open
 * @param string refer        Referer (optional)
 * @param mixed usecookie    If true, cookie.txt    will be used as default, or the usecookie value.
 * @return string
 */
function open_url($url, $post = array(), $ssl = false, $refer = "", $usecookie = false) {
	if ($usecookie) {
		if (file_exists($usecookie)) {
			if (!is_writable($usecookie)) {
				return "Can't write to $usecookie cookie file, change file permission to 777 or remove read only for windows.";
			}
		}
		else {
			$usecookie = "cookie.txt";
			if (!is_writable($usecookie)) {
				return "Can't write to $usecookie cookie file, change file permission to 777 or remove read only for windows.";
			}
		}
	}
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);

	if (preg_match("/^https:/iUs", $url)) {
		$ssl = true;
	}

	if ($ssl == true) {
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	}
	if (!empty($post)) {
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");

	if ($usecookie) {
		curl_setopt($ch, CURLOPT_COOKIEJAR, $usecookie);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $usecookie);
	}
	if ($refer != "") {
		curl_setopt($ch, CURLOPT_REFERER, $refer);
	}
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

/** @function safe
 * Strip a text to be a "save" text for not allowing SQL-Injections<br>
 * and other bad things
 *
 * @package functions
 * @param string $text the code
 */
function safe($text, $type = PDT_STRING) {
	switch ($type) {
		case PDT_INT:
			return (int)$text;
			break;
	}

	return mysql_real_escape_string($text);
}

/** @function cc_error_handler
 * Replace the standard php error handler ( this is a callback functions)
 *
 * @package functions
 * @param int $number the error number
 * @param string $message The error message
 * @param string $file the file where there error appeared
 * @param string $line the line where the appeared
 * @param string $variables the variables
 * @return A String in format $number Fehler in $file in Zeile $line:$message
 */
function cc_error_handler($errno = E_NOTICE, $errstr = "", $errfile = "", $errline = "", $variables = "") {
	global $core;
	
	/* @var $core Core */
	// if error has been supressed with an @
	if (error_reporting() == 0 || (($errno & error_reporting()) == 0 )) {
		return;
	}

	static $firstErr = true;

	// check if function has been called by an exception
	if (func_num_args() == 1) {
		// caught exception
		$exc = func_get_arg(0);
		$errno = $exc->getCode();
		$errstr = $exc->getMessage();
		$errfile = $exc->getFile();
		$errline = $exc->getLine();

		$backtrace = $exc->getTrace();
	}
	else {
		// called by trigger_error()
		$exception = null;

		$backtrace = debug_backtrace();
		unset($backtrace[0]);
		$backtrace = array_reverse($backtrace);
	}

	$errorType = array(
		E_ERROR => 'ERROR',
		E_WARNING => 'WARNING',
		E_PARSE => 'PARSING ERROR',
		E_NOTICE => 'NOTICE',
		E_CORE_ERROR => 'CORE ERROR',
		E_CORE_WARNING => 'CORE WARNING',
		E_COMPILE_ERROR => 'COMPILE ERROR',
		E_COMPILE_WARNING => 'COMPILE WARNING',
		E_USER_ERROR => 'USER ERROR',
		E_USER_WARNING => 'USER WARNING',
		E_USER_NOTICE => 'USER NOTICE',
		E_STRICT => 'STRICT NOTICE',
		E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR'
	);

	// create error message
	if (array_key_exists($errno, $errorType)) {
		$err = $errorType[$errno];
	}
	else {
		$err = 'CAUGHT EXCEPTION (' . $errno . ')';
	}

	$errMsg = $errMsgPlain = "";

	if (empty($_SERVER['HTTP_HOST']))
		$_SERVER['HTTP_HOST'] = '';
	if (empty($_SERVER['REQUEST_URI']))
		$_SERVER['REQUEST_URI'] = '(unknown)';
	if (empty($_SERVER["HTTP_REFERER"]))
		$_SERVER['HTTP_REFERER'] = '(unknown)';
	if (empty($_SERVER["HTTP_USER_AGENT"]))
		$_SERVER['HTTP_USER_AGENT'] = '(unknown)';

	$current_user = "";
	if(!empty($core) && !empty($core->session)) {
		$current_user = $core->session->current_user();
	}
	if(empty($current_user)) {
		$user = '(not logged in)';
	}
	else {
		$user = $current_user->user_id;
	}

	if ($firstErr) {

		$errMsg = "
		<div style=\"width:70%;margin:0px auto;font-family:Arial;font-size:14px;border:1px solid black;\">
			<div style=\"background-color:#6699CC;font-weight:bold;color:white;padding:5px;text-align:left;\">User Infos</div>
			<div style=\"background-color:#D2DDF2;padding:5px;text-align:left;\">
<pre style=\"width:100%; overflow:auto;\">User................: " . htmlspecialchars($user) . "
User................: " . htmlspecialchars($user) . "
Request URI.........: " . htmlspecialchars($_SERVER['HTTP_HOST']) . htmlspecialchars($_SERVER['REQUEST_URI']) . "
Referer.............: " . htmlspecialchars($_SERVER['HTTP_REFERER']) . "
User's Browser......: " . htmlspecialchars($_SERVER['HTTP_USER_AGENT']) . "</pre></div>
		</div>
		<br />
		";


		$errMsgPlain = "
User................: " . htmlspecialchars($user) . "
Request URI.........: " . htmlspecialchars($_SERVER['HTTP_HOST']) . htmlspecialchars($_SERVER['REQUEST_URI']) . "
Referer.............: " . htmlspecialchars($_SERVER['HTTP_REFERER']) . "
User's Browser......: " . htmlspecialchars($_SERVER['HTTP_USER_AGENT']);
	}

	$bgcolor = "#FFCC33";
	$color = "black";
	switch ($errno) {
		case E_ERROR:
		case E_USER_ERROR: $bgcolor = '#C43E3E';
			$color = 'white';
			break;
	}

	$errMsg .= "<br>
		<div style=\"width:70%;margin:0px auto;font-family:Arial;font-size:14px;border:1px solid black;\">
			<div style=\"background-color:" . $bgcolor . ";font-weight:bold;color:" . $color . ";padding:5px;text-align:left;\">" . $err . ": " . $errstr . " in " . $errfile . " on line " . $errline . "</div>
			<div style=\"background-color:#D2DDF2;padding:5px;text-align:left;\">
			<pre style=\"width:100%; overflow:auto;\">";

	$lastfile = '';
	$errMsg .= "<b>\$_GET:</b><br>";
	$errMsg .= var_export($_GET, true);
	$errMsg .= "<br><br><b>\$_POST:</b><br>";
	$errMsg .= var_export($_POST, true);
	$errMsg .= "<br><br><b>\$_COOKIE:</b><br>";
	$errMsg .= var_export($_COOKIE, true);
	$errMsg .= "<br><br><b>Backtrace:</b><br>";
	$errMsgPlain.= "Error: " . $err . ": " . $errstr . " in " . $errfile . " on line " . $errline . "\n";

	$errMsgPlain .= "Backtrace:\n";
	foreach ($backtrace as $row) {
		if (empty($row['file']))
			$row['file'] = '(unknown)';
		if (empty($row['line']))
			$row['line'] = '(unknown)';
		if ($lastfile != $row['file']) {
			$errMsg .= "File: <b>" . $row['file'] . "</b><br>";
			$errMsgPlain .= "File: " . $row['file'] . "\n";
		}
		$lastfile = $row['file'];
		$errMsg .= "<b>  Line: " . $row['line'] . ": </b>";
		$errMsgPlain .= "  Line: " . $row['line'] . ": \n";
		if (!empty($row['class'])) {
			$errMsg .= $row['class'] . $row['type'] . $row['function'];
			$errMsgPlain .= $row['class'] . $row['type'] . $row['function'];
		}
		else {
			$errMsg .= $row['function'];
			$errMsgPlain .= $row['function'];
		}
		if (empty($row['args'])) {
			$errMsg .= ' (no args)';
			$errMsgPlain .= ' (no args)';
		}
		else {
			$errMsg .= ' (Args: ';
			$errMsgPlain .= ' (Args: ';
			$separator = '';
			foreach ($row['args'] as $arg) {
				$errMsg .= htmlspecialchars($separator . getArgument($arg));
				$errMsgPlain .= $separator . getArgument($arg);
				$separator = ', ';
			}
			$errMsg .= ')';
			$errMsgPlain .= ')';
		}
		$errMsg .= "\n\n";
		$errMsgPlain .= "\n";
	}

	$errMsg .= "</pre></div></div>";
	if (ini_get("display_errors")) {
		/* echo '<div><pre>';
		  echo htmlspecialchars($errMsgPlain);
		  echo '</pre></div>'; */
		if(defined('is_shell')) {
			echo $errMsgPlain;
		}
		else {
			echo $errMsg;
		}
	}

	if (ini_get('log_errors')) {
		error_log($errMsgPlain);
	}


	$firstErr = false;
	switch ($errno) {
		case E_ERROR:
		case E_USER_ERROR:

			exit(1);
			break;
	}
	return true;
}

function getArgument($arg) {
	switch (strtolower(gettype($arg))) {
		case 'string':
			return( '"' . str_replace(array("\n"), array(''), $arg) . '"' );

		case 'boolean':
			return (bool) $arg;

		case 'object':
			return 'object(' . get_class($arg) . ')';

		case 'array':
			$ret = 'array(';
			$separtor = '';

			foreach ($arg as $k => $v) {
				$ret .= $separtor . getArgument($k) . ' => ' . getArgument($v);
				$separtor = ', ';
			}
			$ret .= ')';

			return $ret;

		case 'resource':
			return 'resource(' . get_resource_type($arg) . ')';

		default:
			return var_export($arg, true);
	}
}

/** @function classLoader
 * Load a php file from a given classname<br>
 * First try is $classname.class.php on error<br>
 * Try $classname.php
 *
 * @package functions
 * @param string $classname the classname
 * @return null.
 */
function class_loader($classname) {
	global $classes;
	if (array_key_exists($classname, $classes["classes"])) {
		require(SITEPATH . $classes["classes"][$classname]['path']);
		//	echo "After ".$classname.", Memory usage: ".round((memory_get_usage(true)/1024/1000),2)." MB<br>";
	}
	elseif (array_key_exists($classname, $classes["interfaces"])) {
		require(SITEPATH . $classes["interfaces"][$classname]['path']);
	}
	else {
		if(file_exists($classname . ".php")) {
			require($classname . ".php");
		}
		//throw new Exception("Error - class $classname is not available!");
	}
	$already_loaded[$classname] = true;
}

/** @function generatePW
 * Generates a random Password with letters a-zA-Z0-9
 *
 * @package functions
 * @param unt $count the Password length
 * @return string the password
 */
function generatePW($count) {
	$charset = array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9");
	shuffle($charset);
	$newpw = "";
	for ($i = 0; $i < $count; $i++) {
		shuffle($charset);
		$newpw .= $charset[rand(0, count($charset) - 1)];
	}
	return $newpw;
}

/** @function sql_escape
 * Escape the give String<br>
 * with mysql_real_escape_string
 *
 * @package functions
 * @param string The String
 * @return string The escaped String
 */
function sql_escape($string, $hochkomma = 0) {
	if ($hochkomma)
		return "'" . mysql_real_escape_string($string) . "'";
	return mysql_real_escape_string($string);
}

/** @function getRealIP
 * Get the IP-Address from the client
 *
 * @package functions
 * @param boolean $ip2long use ip2long for output
 * @return mixed Returns the ip adress as a String, if $ip2long is set to True it will return the converted long value
 */
function getRealIP($ip2long = false) {

	if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$client_ip = (!empty($_SERVER['REMOTE_ADDR']) ) ? $_SERVER['REMOTE_ADDR'] : ( (!empty($_ENV['REMOTE_ADDR']) ) ? $_ENV['REMOTE_ADDR'] : "unknown" );

		$entries = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

		reset($entries);
		while (list(, $entry) = each($entries)) {
			$entry = trim($entry);
			if (preg_match("/^([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/", $entry, $ip_list)) {
				$private_ip = array(
					'/^0\./',
					'/^127\.0\.0\.1/',
					'/^192\.168\..*/',
					'/^172\.((1[6-9])|(2[0-9])|(3[0-1]))\..*/',
					'/^10\..*/');

				$found_ip = preg_replace($private_ip, $client_ip, $ip_list[1]);

				if ($client_ip != $found_ip) {
					$client_ip = $found_ip;
					break;
				}
			}
		}
	}
	else {
		$client_ip =
			(!empty($_SERVER['REMOTE_ADDR']) ) ?
			$_SERVER['REMOTE_ADDR'] :
			( (!empty($_ENV['REMOTE_ADDR']) ) ?
				$_ENV['REMOTE_ADDR'] :
				"unknown" );
	}
	if (strpos($client_ip, '::') === 0) {
		$client_ip = substr($client_ip, strrpos($client_ip, ':') + 1);
	}
	if ($ip2long) {
		$long = ip2long($client_ip);
		if (!$long)
			$long = 0;
		return $long;
	}
	return $client_ip;
}

/** @function htmlconverter
 * Convert the given text to html as a text
 *
 * @package functions
 * @param string The text
 * @return string The converted text
 */
function htmlconverter($text) {
	global $phpversion;

	$charsets = array('ISO-8859-1', 'ISO-8859-15', 'UTF-8', 'CP1252', 'WINDOWS-1252', 'KOI8-R', 'BIG5', 'GB2312', 'BIG5-HKSCS', 'SHIFT_JIS', 'EUC-JP');

	if (version_compare($phpversion, '4.3.0') >= 0 && in_array(strtoupper(ENCODING), $charsets))
		return @htmlentities($text, ENT_COMPAT, ENCODING);
	elseif (in_array(strtoupper(ENCODING), array('ISO-8859-1', 'WINDOWS-1252')))
		return htmlentities($text);
	else
		return htmlspecialchars($text);
}

?>
