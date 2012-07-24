<?php
	ob_implicit_flush();
	error_reporting(E_ALL);
	header('Content-Type: text/html; charset=utf-8');
	if(!defined('SITEPATH')) {
		define('SITEPATH',  dirname(__FILE__));
	}

	define('CURRENT_TIME',time());
	define('BUILD_START',microtime(true));
    ini_set('display_errors', 'on');
    ini_set('html_errors', 'on');

	//Define the $translation_cache array to insert before unloading page the new translations
	$translation_cache = array();

	require('lib/Core.php');

	register_shutdown_function('soopfw_shutdown');
    if(!isset($is_shell)) {
        $is_shell = false;
    }

	$prof_enable = !empty($_GET['PROFILER']);

	if($prof_enable) {
		xhprof_enable();
	}

	$override_params = array(
        'type' => '',
        'module' => '',
        'action' => '',
    );

    //indicates that we want to request an admin page
    $loggedin = false;
	$_SERVER['REQUEST_URI'] = preg_replace('/^\/+/is', '/',$_SERVER['REQUEST_URI']);
	$language = "";
    list($url) = explode('?', $_SERVER['REQUEST_URI'],2);

	$override_params = parse_url_string($url);

	$language = $override_params['language'];
	unset($override_params['language']);
	$core = new Core($language, $is_shell);
	
	$core->cache('core', 'admin_theme', $override_params['admin_link']);

	//Only check for url_aliase if a specific url was provided not just _GET params
	if(!empty($override_params['module'])) {
		//If module starts with _ we do not want to override it through url_alias couse this form determines that we want to call direct the module action
		if(preg_match("/^_(.*)$/is", $override_params['module'], $matches)){
			$override_params['module'] = $matches[1];
		}
		else {

			$alias_params = UrlAliasObj::get_params_from_alias($url);
			if($alias_params !== false) {
				$override_params = $alias_params;
			}
		} // If we have not a prefix of _ within module
	} // If we should search for url aliase

	//Build the param array for the param struct (index.php or similar entry pages)

	$additional_function_params = $override_params['additional_function_params'];
	unset($override_params['additional_function_params']);

    $param_array = array_merge($_GET, $override_params);

	$core->boot();

	/**
	 * Checks wether the given alias string is more precise than the last "best" one.
	 *
	 * @global mixed $best_match 0 if not initalized or nothing found, else the best match alias array
	 * @param string $alias the alias string
	 * @return boolean true if it is more precise, else false
	 */
	function is_alias_preciser($alias) {
		/**
		 * Stores the last best match array
		 */
		global $best_match;

		//Get the alias as param array
		$alias_array = explode("/", $alias);

		//Get the param count of check alias
		$alias_count = count($alias_array);

		//Get the param count of previous best alias
		$best_match_count = count($best_match);

		//If we call this method first time or the current alias param count is higher than the last one, return true and set the best match array new
		if($best_match === 0 || $alias_count > $best_match_count) {
			$best_match = $alias_array;
			return true;
		}

		//If we have equals param count on both side check variables
		if($alias_count == $best_match_count) {
			//Loop through current alias
			foreach($alias_array AS $index => $param) {
				//If the last best match param on current index is a variable and the current one not, the current one is more precise.
				if($best_match[$index] == "%" && $param != "%") {
					$best_match = $alias_array;
					return true;
				}

				//If we found at the current index that the current alias has a param where the best match one has no one, this determines
				//that the hole current alias is not more precise as the last one.
				if($param == "%" && $best_match[$index] != "%") {
					return false;
				}
			}
		}

		//The current alias has less params than the last best match one, so return false
		return false;
	}
?>