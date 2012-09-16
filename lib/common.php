<?php
define("TIME_NOW", time());
define("DB_DATE", "Y-m-d");
define("DB_DATETIME", "Y-m-d H:i:s");
define("DB_TIME", "H:i:s");

mb_internal_encoding('UTF-8');

/**
 * This is not a primitive datatype but it can be used as a real not set variable, so if we realy want to check if a
 * parameter was provided to a function/method we can default assign NS so if we pass "", null or something similar to empty
 * it is also a allowed "provided" value. The value behind NS is choosen with a string which should never be a value provided by a user
 */
define("NS", "-||notset||-");

global $memcached_use, $memcached_obj, $translation_cache;
$translation_cache = array();

/**
 * Determines if we use the original memcached class
 * @var boolean
 */
$memcached_use = true;

/**
 * If we have not a memcached class we must define it, and define needed constances
 * This is used within DBMemcached, MemcachedWrapper and where we need to check if a returning memcached result succeed
 */
if (!class_exists("Memcached")) {
	/**
	 * We had no memcached class so we do not use the original memcached class, this is need by memcache_init so it can decide if the found
	 * Memcached class is the original correct one or just the constances wrapper
	 */
	$memcached_use = false;

	class Memcached
	{
		const OPT_PREFIX_KEY = '';
		const OPT_COMPRESSION = TRUE;
		const OPT_LIBKETAMA_COMPATIBLE = FALSE;
		const OPT_BUFFER_WRITES = FALSE;
		const OPT_BINARY_PROTOCOL = FALSE;
		const OPT_NO_BLOCK = FALSE;
		const OPT_TCP_NODELAY = FALSE;
		const OPT_SOCKET_RECV_SIZE = 1000;
		const OPT_RETRY_TIMEOUT = 0;
		const OPT_SEND_TIMEOUT = 0;
		const OPT_RECV_TIMEOUT = 0;
		const OPT_POLL_TIMEOUT = 1000;
		const OPT_CACHE_LOOKUPS = FALSE;
		const OPT_SERVER_FAILURE_LIMIT = 0;
		const RES_SUCCESS = 0;
		const RES_FAILURE = 1;

	}

}

/**
 * Define our primitive datatypes, these are used in several ways.
 * Most use is within parameter type checks.
 */
$i = 1;
define("PDT_INT", $i++, true);
define("PDT_FLOAT", $i++, true);
define("PDT_STRING", $i++, true);
define("PDT_DECIMAL", $i++, true);
define("PDT_DATE", $i++, true);
define("PDT_OBJ", $i++, true);
define("PDT_ARR", $i++, true);
define("PDT_BOOL", $i++, true);
define("PDT_INET", $i++, true);
define("PDT_SQLSTRING", $i++, true);
define("PDT_JSON", $i++, true);
define("PDT_PASSWORD", $i++, true);
define("PDT_ENUM", $i++, true);
define("PDT_TEXT", $i++, true);
define("PDT_TINYINT", $i++, true);
define("PDT_MEDIUMINT", $i++, true);
define("PDT_BIGINT", $i++, true);
define("PDT_SMALLINT", $i++, true);
define("PDT_DATETIME", $i++, true);
define("PDT_TIME", $i++, true);
define("PDT_FILE", $i++, true);
define("PDT_LANGUAGE", $i++, true);
define("PDT_LANGUAGE_ENABLED", $i++, true);
define("PDT_SERIALIZED", $i++, true);

/**
 * Translate the given key, which should be always the english part of the translation.
 *
 * @param string $key
 *   the language key
 * @param array $args
 *   an array with replacement array('search_key' => 'replace_value')
 * 	 We can use this prefix in char in front of the search_key
 * 	 i = intval, f = floatval, all other search_key's will be replaced with htmlspecialchars
 *
 * @return string the translated string, or if the translations is not found return the given key
 */
function t($key, $args = array()) {
	static $key_cache = array();
	global $translation_cache;

	$core = Core::get_instance();
	$save_key = strtolower($key);

	// Sort the arg array descending by strlen.
	if (!empty($args)) {
		uksort($args, function($a, $b) {
					$str_len_a = strlen($a) - 1;
					if (!isset($b{$str_len_a})) {
						return 1;
					}

					if (!isset($b{$str_len_a + 1})) {
						return -1;
					}

					return 0;
				});
	}
	$cache_key = $save_key . "|" . md5(json_encode($args));
	if (isset($key_cache[$cache_key])) {
		return $key_cache[$cache_key];
	}

	//$core = &$GLOBALS['core'];
	$bbcode = new BBCodeParser();


	//Check if language is available
	if (!empty($core->lng)) {

		//Try to get the translation for the key and do replacements within language object
		$translated = $core->lng->get($save_key, "", $args);
		if (!empty($translated)) {
			$key = $translated;
		}
		else {
			$translation_cache[md5($save_key)] = $save_key;
		}
	}
	if (!empty($core)) {
		$cached_parsed = $core->mcache("core_translation_parsed:" . md5($key));
		if (empty($cached_parsed)) {
			$m_key = md5($key);
			//Parse bbcode
			$key = $bbcode->parse($key);
			$core->mcache("core_translation_parsed:" . $m_key, $key);
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
			case 'i': $v = (int) $v;
				break;
			case 'f': $v = (float) $v;
				break;
			case '!': break;
			default: $v = htmlspecialchars($v);
				break;
		}

		$key = str_replace($k, $v, $key);
	}
	$key_cache[$cache_key] = $key;
	//Return replaced untranslated english string
	return $key;
}

