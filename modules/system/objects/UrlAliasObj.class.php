<?php

/**
 * This holds all url aliase
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category ModelObjects
 */
class UrlAliasObj extends AbstractDataManagment
{
	/**
	 * Define constances
	 */
	const TABLE = "system_url_alias";

	/**
	 * Constructor
	 *
	 * @param int $id
	 *   the alias id (optional, default = '')
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($id = "", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key("id");
		$this->db_struct->set_auto_increment('id');
		$this->db_struct->add_field("id", t("ID"), PDT_INT, 0, 'UNSIGNED');
		$this->db_struct->add_field("type", t("The type"), PDT_STRING, 'auto');
		$this->db_struct->add_field("alias", t("The URL-Alias"), PDT_STRING);
		$this->db_struct->add_field("module", t("The module"), PDT_STRING);
		$this->db_struct->add_field("action", t("The action"), PDT_STRING);
		$this->db_struct->add_field("params", t("params"), PDT_STRING);
		$this->db_struct->add_field("perm", t("The perm"), PDT_STRING, '');

		if (!empty($id)) {
			if (!$this->load($id, $force_db)) {
				return false;
			}
		}
	}

	/**
	 * Save the given Data
	 *
	 * @param boolean $save_if_unchanged
	 *   Save this object even if no changes to it's values were made (optional, default = false)
	 *
	 * @return boolean true on success, else false
	 */
	public function save($save_if_unchanged = false) {
		$old_alias = $this->get_original_value("alias");
		if (parent::save($save_if_unchanged)) {
			$this->core->mcache('url_alias_match_' . md5($this->values['alias']), "", 1);
			$this->core->mcache('url_alias_match_' . md5($old_alias), "", 1);
		}
	}

	/**
	 * Insert the current data
	 *
	 * @param boolean $ignore
	 *   Don't throw an error if data is already there (optional, default=false)
	 *
	 * @return boolean true on success, else false
	 */
	public function insert($ignore = false) {
		if (parent::insert($ignore)) {
			//Need to clear the cache key for the alias
			$this->core->mcache('url_alias_match_' . md5($this->values['alias']), "", 1);
			return true;
		}
		return false;
	}

	/**
	 * Delete the given menu, also deletes all menu entries for this menu
	 *
	 * @return boolean true on success, else false
	 */
	public function delete() {
		$old_alias = $this->get_value("alias");
		if (parent::delete()) {
			$this->core->mcache('url_alias_match_' . md5($old_alias), "", 1);
		}
	}

	/**
	 * Converts a given string in a string which an be used within url's
	 *
	 * @param string $string
	 *   the string to convert
	 *
	 * @return string the converted string
	 */
	public static function get_alias_string($string) {
		$string = mb_convert_encoding($string, 'UTF-8');
		$invalid_chars = "!\"§$%&/()=?`*_:;><,.#+´ß^°¬¹²³¼½¬{[]}\¸@ł€¶ŧ←↓→øþ¨~æſðđŋħł˝|»«¢„“”µ·…";
		$string = strtolower(preg_replace("/[" . preg_quote($invalid_chars, "/") . "\s]+/u", "-", $string));
		$string = preg_replace("/-+/u", "-", $string);
		$string = preg_replace("/-$/u", "", $string);
		$string = preg_replace("/^-/u", "", $string);
		return $string;
	}

	/**
	 * Tries to get action call parameter array for the given alias string
	 *
	 * @param string $alias
	 *   the alias to search for
	 *
	 * @return array the array with module, action, and params for the action which is needed to call an action or false if nothing is found
	 */
	public static function get_params_from_alias($alias) {
		$core = Core::get_instance();
		
		if ($core->config['db']['use'] !== true) {
			return false;
		}

		$return_array = array();

		if (preg_match("/^\/?([a-z][a-z]\/)?(.*)$/i", $alias, $matches)) {
			$alias = $matches[2];
		}

		//Build up from url alias check array the check url path
		$alias_url_check = $alias;

		//Remove ending file-endings if it is a regular file ending (just the most used one) and also removes the starting /
		if (preg_match('/^\/?(.*)\.(html|ajaxhtml|ajax|htm|direct)$/is', $alias_url_check, $matches)) {
			$alias_url_check = $matches[1];
		}

		$cached_match = $core->mcache('url_alias_match_' . md5($alias_url_check));
		if (false && !empty($cached_match)) {
			$additional_function_params = $cached_match['additional_function_params'];
			$return_array = $cached_match['override_params'];
		}
		else {
			//We can determine a direct module action call with the _ prefix in a module, also we can provide .direct as "file-extension" so check for it
			if (!empty($matches) && $matches[2] == "direct") {
				$param_array = explode('/', $alias_url_check);
				if (isset($param_array[0])) {
					$return_array['module'] = array_shift($param_array);
					if (isset($param_array[0])) {
						$return_array['action'] = array_shift($param_array);
					}
				}

				$additional_function_params = $param_array;

				//We do not want the psyodo file extension within the last parameter, so remove it
				$last_index = count($additional_function_params) - 1;
				$additional_function_params[$last_index] = str_replace('.direct', '', $additional_function_params[$last_index]);
			}
			//We need only an alias check if we provided some url path parts
			else if (!empty($alias)) {

				$alias_priority = ((!empty($matches) && ($matches[2] == "html" || $matches[2] == "htm")));

				$rows = $core->db->query_slave_first("SELECT * FROM `" . UrlAliasObj::TABLE . "` WHERE `alias` = @alias", array("@alias" => $alias_url_check));

				if (empty($rows)) {
					list($alias_start) = explode("/", $alias, 2);
					$rows = $core->db->query_slave_all("SELECT * FROM `" . UrlAliasObj::TABLE . "` WHERE `alias` LIKE 'alias_start%'", array("alias_start" => $alias_start));
				}
				else {
					$rows = array($rows);
				}

				//To have a much better performance we search only alias which starts with the first url path patter (first part of / array)
				foreach ($rows AS $row) {

					//try to match the alias, replace all % to (.*) to get the entry for an additional parameter
					$regexp = '/^' . str_replace("%", "([^\/]*)", preg_quote($row['alias'], '/')) . '(\/.+)?$/is';
					if (preg_match($regexp, $alias_url_check, $matches)) {

						if (UrlAliasObj::is_alias_preciser($row['alias'])) {
							$return_array['module'] = $row['module'];
							$return_array['action'] = $row['action'];
							$return_array['perm'] = $row['perm'];
							$additional_function_params = array();

							//Remove the first match index couse this is just the hole match which we do not want
							array_shift($matches);

							//Get how much "params" we have left
							$match_count = count($matches);

							//If a param is left build up
							if ($match_count > 0) {

								//If we have not just the last part as a param, (%-param)
								if ($match_count > 1) {
									$match = $matches;
									//Remove the last param couse this is build up below
									array_pop($match);
									$additional_function_params = $match;
								}
								reset($matches);
								//Get the additional params
								$add_param_array = explode('/', $matches[$match_count - 1]);
								//We must shift the first element of $add_param_array, this is always be empty because the starting / within preg_match above
								if (empty($add_param_array[0])) {
									array_shift($add_param_array);
								}

								$additional_function_params = array_merge($additional_function_params, $add_param_array);
							} //if we have additional params

							//If we got some params from database, provide it
							if (!empty($row['params'])) {
								$params = json_decode($row['params'], true);
								if (empty($params)) {
									$params = array($row['params']);
								}
								else if (!is_array($params)) {
									$params = array($params);
								}

								foreach ($params AS $param) {
									$additional_function_params[] = $param;
								}
							}
						} //if alias is a better / longer option
					} // if alias match
				} // foreach url alases

				//Cache the result if we have one
				if (!empty($return_array)) {
					if (!$alias_priority) {
						$original_url_params = explode("/", $alias_url_check);
						if ($original_url_params[0] == 'admin') {
							array_shift($original_url_params);
						}
						if (isset($original_url_params[0]) && $core->module_enabled($original_url_params[0])) {
							return false;
						}
					}
					$core->mcache('url_alias_match_' . md5($alias_url_check), array(
						'override_params' => $return_array,
						'additional_function_params' => $additional_function_params,
					), 1209600); // 1209600 = 14 Days.
				}
			} //if matches .direct
		} //Cached

		if (empty($return_array)) {
			return false;
		}

		$return_array['additional_function_params'] = $additional_function_params;
		return $return_array;
	}

	/**
	 * Returns an array like parse_url_string but only if an alias exist.
	 *
	 * @param array $original_override_params
	 *   The original override param array.
	 *
	 * @return array the overriding param array, if we did not override it return false
	 */
	public static function get_alias_from_uri($original_override_params) {
		//Only check for url_aliase if a specific url was provided not just _GET params
		if (!empty($original_override_params['module'])) {

			//If module starts with _ we do not want to override it through url_alias couse this form determines that we want to call direct the module action
			if (preg_match("/^_(.*)$/is", $original_override_params['module'], $matches)) {
				$original_override_params['module'] = $matches[1];
			}
			else {
				$alias_params = UrlAliasObj::get_params_from_alias(NetTools::get_request_uri());
				if ($alias_params !== false) {
					$original_override_params = $alias_params;
				}
			} // If we have not a prefix of _ within module
			return $original_override_params;
		} // If we should search for url alases

		return false;
	}

	/**
	 * parse the given url into an array which we can use for action calling.
	 *
	 * @param string $url
	 *   the url.
	 *   if not set it will use the current request uri (optional, default = NS)
	 *
	 * @return array the params for this url
	 */
	public static function parse_url_string($url = NS) {
		if ($url === NS) {
			$url = NetTools::get_request_uri();
		}
		$language = "";

		//This params will be provided within the action callback
		$override_params = array(
			'type' => '',
			'module' => '',
			'action' => '',
		);

		$additional_function_params = array();

		$admin_link = false;
		if (!empty($url) && $url != '/') {
			$additional_function_params = explode('/', substr($url, 1));

			if (count($additional_function_params) > 0) {
				if (preg_match('/^[a-z]{2}$/is', $additional_function_params[0], $matches)) {
					$language = $matches[0];
					array_shift($additional_function_params);
				}
			}
			if (count($additional_function_params) > 0) {
				if (preg_match('/^admin$/is', $additional_function_params[0])) {
					$admin_link = true;
					array_shift($additional_function_params);
				}
			}
			if (preg_match('/.*\.([^\.]+)$/iUs', $url, $matches)) { //Handle custom file extensions
				switch ($matches[1]) {
					case 'ajax'://We have an ajax extension so handle this as an ajax request and set the type
						$override_params['type'] = 'ajax_request';
						break;
					case 'ajax_html'://We have an ajax_html extension so handle this as an ajax_html (Normal PHP behaviour but do not display normal header and footer
						$override_params['type'] = 'ajax_html';
						break;
				}
			}
			$len = count($additional_function_params);
			for ($i = 0; $i <= $len; $i++) {
				$shift = array_shift($additional_function_params);

				if (empty($override_params['module'])) {
					list($shift) = explode(".", $shift, 2);
					$override_params['module'] = $shift;
					continue;
				}

				if (empty($override_params['action'])) {
					list($shift) = explode(".", $shift, 2);
					$override_params['action'] = $shift;
					break;
				}
			}
		}
		$override_params['admin_link'] = $admin_link;
		$override_params['language'] = $language;
		$override_params['additional_function_params'] = $additional_function_params;
		return $override_params;
	}
	/**
	 * Checks wether the given alias string is more precise than the last "best" one.
	 *
	 * @global mixed $best_match 0 if not initalized or nothing found, else the best match alias array
	 * @param string $alias the alias string
	 * @return boolean true if it is more precise, else false
	 */
	public static function is_alias_preciser($alias) {
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
		if ($best_match === 0 || $alias_count > $best_match_count) {
			$best_match = $alias_array;
			return true;
		}

		//If we have equals param count on both side check variables
		if ($alias_count == $best_match_count) {
			//Loop through current alias
			foreach ($alias_array AS $index => $param) {
				//If the last best match param on current index is a variable and the current one not, the current one is more precise.
				if ($best_match[$index] == "%" && $param != "%") {
					$best_match = $alias_array;
					return true;
				}

				//If we found at the current index that the current alias has a param where the best match one has no one, this determines
				//that the hole current alias is not more precise as the last one.
				if ($param == "%" && $best_match[$index] != "%") {
					return false;
				}
			}
		}

		//The current alias has less params than the last best match one, so return false
		return false;
	}

}
