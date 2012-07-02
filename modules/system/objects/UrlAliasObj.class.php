<?php

/**
 * This holds all url aliase
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.system.objects
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
	 * @param int $id the alias id (optional, default = false)
	 * @param boolean $force_db if we want to force to load the data from the database (optional, default = false)
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
	 * @param boolean $save_if_unchanged Save this object even if no changes to it's values were made
	 * @return boolean true on success, else false
	 */
	public function save($save_if_unchanged = false) {
		$old_alias = $this->get_original_value("alias");
		if(parent::save($save_if_unchanged)) {
			$this->core->mcache('url_alias_match_'.md5($this->values['alias']), "",1);
			$this->core->mcache('url_alias_match_'.md5($old_alias), "",1);
		}
	}

	/**
	 * Insert the current data
	 *
	 * @param boolean $ignore Don't throw an error if data is already there (optional, default=false)
	 * @return boolean true on success, else false
	 */
	public function insert($ignore = false) {
		if(parent::insert($ignore)) {
			//Need to clear the cache key for the alias
			$this->core->mcache('url_alias_match_'.md5($this->values['alias']), "",1);
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
		if(parent::delete()) {
			$this->core->mcache('url_alias_match_'.md5($old_alias), "",1);
		}
	}



	/**
	 * Converts a given string in a string which an be used within url's
	 *
	 * @param string $string the string to convert
	 * @return string the converted string
	 */
	public static function get_alias_string($string) {
		$string =  mb_convert_encoding($string, 'UTF-8');
		$invalid_chars = "!\"§$%&/()=?`*_:;><,.#+´ß^°¬¹²³¼½¬{[]}\¸@ł€¶ŧ←↓→øþ¨~æſðđŋħł˝|»«¢„“”µ·…";
		$string = strtolower(preg_replace("/[".preg_quote($invalid_chars,"/")."\s]+/u", "-", $string));
		$string =  preg_replace("/-+/u","-", $string);
		$string =  preg_replace("/-$/u","", $string);
		$string =  preg_replace("/^-/u","", $string);
		return $string;
	}

	/**
	 * Tries to get action call parameter array for the given alias string
	 *
	 * @param string $alias the alias to search for
	 * @return array the array with module, action, and params for the action which is needed to call an action or false if nothing is found
	 */
	public static function get_params_from_alias($alias) {
		global $core;
		$return_array = array();

		if(preg_match("/^\/?([a-z][a-z]\/)?(.*)$/i", $alias, $matches)) {
			$alias = $matches[2];
		}

		//Build up from url alias check array the check url path
		$alias_url_check = $alias;

		//Remove ending file-endings if it is a regular file ending (just the most used one) and also removes the starting /
		if(preg_match('/^\/?(.*)\.(html|ajaxhtml|ajax|htm|direct)$/is', $alias_url_check, $matches)) {
			$alias_url_check = $matches[1];
		}

		$cached_match = $core->mcache('url_alias_match_'.md5($alias_url_check));
		if(false && !empty($cached_match)) {
			$additional_function_params = $cached_match['additional_function_params'];
			$return_array = $cached_match['override_params'];
		}
		else {
			//We can determine a direct module action call with the _ prefix in a module, also we can provide .direct as "file-extension" so check for it
			if(!empty($matches) && $matches[2] == "direct") {
				//We do not want the psyodo file extension within the last parameter, so remove it
				$last_index = count($additional_function_params)-1;
				$additional_function_params[$last_index] = str_replace('.direct', '', $additional_function_params[$last_index]);
			}
			//We need only an alias check if we provided some url path parts
			else if(!empty($alias)){
				//Get the most precise alias from db
				$best_match = 0;
				$rows = array();
				$rows = $core->db->query_slave_first("SELECT * FROM `".UrlAliasObj::TABLE."` WHERE `alias` = @alias", array("@alias" => $alias_url_check));

				if(empty($rows)) {
					list($alias_start) = explode("/",$alias, 2);
					$rows = $core->db->query_slave_all("SELECT * FROM `".UrlAliasObj::TABLE."` WHERE `alias` LIKE 'alias_start%'", array("alias_start" => $alias_start));
				}
				else {
					$rows = array($rows);
				}

				//To have a much better performance we search only alias which starts with the first url path patter (first part of / array)
				foreach($rows AS $row) {

					//try to match the alias, replace all % to (.*) to get the entry for an additional parameter
					$regexp = '/^'.str_replace("%","([^\/]*)", preg_quote($row['alias'], '/')).'(\/.+)?$/is';
					if(preg_match($regexp, $alias_url_check, $matches)) {

						if(is_alias_preciser($row['alias'])) {
							$return_array['module'] = $row['module'];
							$return_array['action'] = $row['action'];
							$return_array['perm'] = $row['perm'];
							$additional_function_params = array();

							//Remove the first match index couse this is just the hole match which we do not want
							array_shift($matches);

							//Get how much "params" we have left
							$match_count = count($matches);
							//If a param is left build up
							if($match_count > 0) {

								//If we have not just the last part as a param, (%-param)
								if($match_count > 1) {
									$match = $matches;
									//Remove the last param couse this is build up below
									array_pop($match);
									$additional_function_params = $match;
								}
								reset($matches);
								//Get the additional params
								$add_param_array = explode('/', $matches[$match_count-1]);
								//We must shift the first element of $add_param_array, this is always be empty because the starting / within preg_match above
								if(empty($add_param_array[0])) {
									array_shift($add_param_array);
								}

								$additional_function_params = array_merge($additional_function_params, $add_param_array);
							} //if we have additional params

							//If we got some params from database, provide it
							if(!empty($row['params'])) {
								$params = json_decode($row['params'], true);
								if(empty($params)) {
									$params = array($row['params']);
								} else if(!is_array($params)) {
									$params = array($params);
								}

								foreach($params AS $param) {
									$additional_function_params[] = $param;
								}
							}

						} //if alias is a better / longer option
					} // if alias match
				} // foreach url aliase

				//Cache the result if we have one
				if($best_match > 0) {
					$core->mcache('url_alias_match_'.md5($alias_url_check), array(
						'override_params' => $return_array,
						'additional_function_params' => $additional_function_params,
					),1209600);
				}
			} //if matches .direct
		} //Cached

		if(empty($return_array)) {
			return false;
		}

		$return_array['additional_function_params'] = $additional_function_params;
		return $return_array;
	}

}

?>