<?php

/**
 * Test main functionality of the Framework.
 * If any of this tests fail no further Tests will be executed.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Testing
 */
class MainSystemTest extends UnitTest implements UnitTestInterface
{

	/**
	 * Returns an array with all test methods.
	 * If an empty array is returned all class method will be used.
	 *
	 * @return array the array with all methods which should be used for unit tests.
	 */
	public function get_tests() {
		return array();
	}

	/**
	 * Checks main database functionality.
	 *
	 * WARNING THIS TEST NEEDS TO BE THE FIRST ONE.
	 * This is needed because within this test we check if the tablename prefix
	 * rewrites works correctly. Also we test if we have the permission to create
	 * and delete tables.
	 *
	 * This test is special because the UnitRunner will create after this test
	 * the database test envoirement.
	 *
	 * @return boolean if this returns false no further Tests will be executed.
	 */
	public function check_main_database() {

		$this->assert_not_empty($this->db, t("Check if database is not empty"));
		if ($this->has_failed_tests()) {
			return false;
		}

		$table_prefix = $this->db->table_prefix();

		$original_query = "SELECT 1 FROM `tablename` WHERE `tablename`.x = `tablename2`.y";
		$this->db->final_transform_query($original_query);
		$check_query = "SELECT 1 FROM `" . $table_prefix . "tablename` WHERE `" . $table_prefix . "tablename`.x = `" . $table_prefix . "tablename2`.y";
		if (!$this->assert_equals($original_query, $check_query, t("Check plain SELECT"))) {
			return false;
		}

		$original_query = "UPDATE `tablename` SET x WHERE x";
		$this->db->final_transform_query($original_query);
		$check_query = "UPDATE `" . $table_prefix . "tablename` SET x WHERE x";
		if (!$this->assert_equals($original_query, $check_query, t("Check plain UPDATE"))) {
			return false;
		}

		$original_query = "ON DUPLICATE KEY UPDATE `field` = 1 WHERE `tablename`.y = 1";
		$this->db->final_transform_query($original_query);
		$check_query = "ON DUPLICATE KEY UPDATE `field` = 1 WHERE `" . $table_prefix . "tablename`.y = 1";
		if (!$this->assert_equals($original_query, $check_query, t("Check plain ON DUPLICATE KEY UPDATE"))) {
			return false;
		}

		$original_query = "ON DUPLICATE KEY UPDATE `tablename`.field = 1 WHERE `tablename`.y = 1";
		$this->db->final_transform_query($original_query);
		$check_query = "ON DUPLICATE KEY UPDATE `" . $table_prefix . "tablename`.field = 1 WHERE `" . $table_prefix . "tablename`.y = 1";
		if (!$this->assert_equals($original_query, $check_query, t("Check plain ON DUPLICATE KEY UPDATE with tablename prefix"))) {
			return false;
		}

		$original_query = "ON DUPLICATE KEY UPDATE `database1`.`tablename`.`field` = 1 WHERE `tablename`.y = 1";
		$this->db->final_transform_query($original_query);
		$check_query = "ON DUPLICATE KEY UPDATE `database1`.`" . $table_prefix . "tablename`.`field` = 1 WHERE `" . $table_prefix . "tablename`.y = 1";
		if (!$this->assert_equals($original_query, $check_query, t("Check plain ON DUPLICATE KEY UPDATE with database prefix"))) {
			return false;
		}

		$original_query = "INSERT INTO `tablename` () VALUES ()";
		$this->db->final_transform_query($original_query);
		$check_query = "INSERT INTO `" . $table_prefix . "tablename` () VALUES ()";
		if (!$this->assert_equals($original_query, $check_query, t("Check plain INSERT"))) {
			return false;
		}

		$original_query = "INSERT IGNORE INTO `tablename` () VALUES ()";
		$this->db->final_transform_query($original_query);
		$check_query = "INSERT IGNORE INTO `" . $table_prefix . "tablename` () VALUES ()";
		if (!$this->assert_equals($original_query, $check_query, t("Check plain INSERT IGNORE"))) {
			return false;
		}

		$original_query = "REPLACE INTO `tablename` () VALUES ()";
		$this->db->final_transform_query($original_query);
		$check_query = "REPLACE INTO `" . $table_prefix . "tablename` () VALUES ()";
		if (!$this->assert_equals($original_query, $check_query, t("Check plain REPLACE"))) {
			return false;
		}

		$original_query = "REPLACE IGNORE INTO `tablename` () VALUES ()";
		$this->db->final_transform_query($original_query);
		$check_query = "REPLACE IGNORE INTO `" . $table_prefix . "tablename` () VALUES ()";
		if (!$this->assert_equals($original_query, $check_query, t("Check plain REPLACE IGNORE"))) {
			return false;
		}

		$original_query = "CREATE TABLE `tablename` ()";
		$this->db->final_transform_query($original_query);
		$check_query = "CREATE TABLE `" . $table_prefix . "tablename` ()";
		if (!$this->assert_equals($original_query, $check_query, t("Check plain CREATE TABLE"))) {
			return false;
		}

		$original_query = "CREATE TABLE `database1`.`tablename` ()";
		$this->db->final_transform_query($original_query);
		$check_query = "CREATE TABLE `database1`.`" . $table_prefix . "tablename` ()";
		if (!$this->assert_equals($original_query, $check_query, t("Check plain CREATE TABLE with database prefix"))) {
			return false;
		}

		$original_query = "ALTER TABLE `tablename` () VALUES ()";
		$this->db->final_transform_query($original_query);
		$check_query = "ALTER TABLE `" . $table_prefix . "tablename` () VALUES ()";
		if (!$this->assert_equals($original_query, $check_query, t("Check plain ALTER TABLE"))) {
			return false;
		}

		$original_query = "SHOW COLUMNS FROM `tablename` () VALUES ()";
		$this->db->final_transform_query($original_query);
		$check_query = "SHOW COLUMNS FROM `" . $table_prefix . "tablename` () VALUES ()";
		if (!$this->assert_equals($original_query, $check_query, t("Check plain SHOW COLUMNS FROM"))) {
			return false;
		}

		$original_query = "SELECT * FROM `tablename` WHERE x =
		(SELECT 1 FROM `tablename2` WHERE y = 1 LIMIT 1)";
		$this->db->final_transform_query($original_query);
		$check_query = "SELECT * FROM `" . $table_prefix . "tablename` WHERE x =
		(SELECT 1 FROM `" . $table_prefix . "tablename2` WHERE y = 1 LIMIT 1)";
		if (!$this->assert_equals($original_query, $check_query, t("Check plain sub query"))) {
			return false;
		}

		$filter = DatabaseFilter::create('tablename');
		$original_query = $filter->get_select_sql();
		$this->db->final_transform_query($original_query);
		$check_query = "SELECT `" . $table_prefix . "tablename`.* FROM `" . $table_prefix . "tablename`";
		if (!$this->assert_equals($original_query, $check_query, t("Check Databasefilter simple select"))) {
			return false;
		}

		$filter = DatabaseFilter::create('tablename')
				->add_where('x', '1');
		$original_query = $filter->get_select_sql();
		$this->db->final_transform_query($original_query);
		$check_query = "SELECT `" . $table_prefix . "tablename`.* FROM `" . $table_prefix . "tablename`  WHERE (`x` = '1')";
		if (!$this->assert_equals($original_query, $check_query, t("Check Databasefilter select with where"))) {
			return false;
		}

		$sub_query = DatabaseFilter::create('subtable')
				->add_where('subfield1', "34' OR 1=1;#")
				->add_where('subfield1', 'aliasjoin.`field1`', '=', false)
				->add_column('count(*)');


		$filter = DatabaseFilter::create('tablename')
				->add_where('x', '1')
				->add_where('tablename2.y', '1')
				->add_where('tablename.x', $sub_query, '>=')
				->join('aliastablename', 'aliasjoin.`x` = `database1`.`tablename2`.`y`', 'aliasjoin')
				->left_join('tablename3', '`tablename3`.x = `tablename2`.y')
				->order_by('x')
				->group_by('field1')
				->group_by('field2', 'tablename2')
				->order_by('y', DatabaseFilter::ASC, 'tablename2');

		$original_query = $filter->get_select_sql();
		$this->db->final_transform_query($original_query);

		$check_query  = "SELECT `" . $table_prefix . "tablename`.* FROM `" . $table_prefix . "tablename` ";
		$check_query .= "JOIN `" . $table_prefix . "aliastablename` AS aliasjoin ON (aliasjoin.`x` = `database1`.`" . $table_prefix . "tablename2`.`y`) ";
		$check_query .= "LEFT JOIN `" . $table_prefix . "tablename3` ON (`" . $table_prefix . "tablename3`.x = `" . $table_prefix . "tablename2`.y) ";
		$check_query .= "WHERE (`x` = '1' AND `" . $table_prefix . "tablename2`.`y` = '1' AND `" . $table_prefix . "tablename`.`x` >= ";
		$check_query .= "(SELECT count(*) FROM `" . $table_prefix . "subtable`  WHERE (`subfield1` = '34\' OR 1=1;#' AND `subfield1` = aliasjoin.`field1`) ))  ";
		$check_query .= "GROUP BY `" . $table_prefix . "tablename`.`field1`, `" . $table_prefix . "tablename2`.`field2` ";
		$check_query .= "ORDER BY `" . $table_prefix . "tablename`.`x` asc, `" . $table_prefix . "tablename2`.`y` asc";

		if (!$this->assert_equals($original_query, $check_query, t("Check Databasefilter select, order_by, join, left_join, where, group by, where subquery"))) {
			return false;
		}

		$filter = DatabaseFilter::create('tablename')
				->change_fields('x', '1');
		$original_query = $filter->get_update_sql();
		$this->db->final_transform_query($original_query);
		$check_query = "UPDATE `" . $table_prefix . "tablename` SET `" . $table_prefix . "tablename`.`x` = '1'";
		if (!$this->assert_equals($original_query, $check_query, t("Check Databasefilter simple update"))) {
			return false;
		}

		$filter = DatabaseFilter::create('tablename')
				->change_fields('x', '1');
		$original_query = $filter->get_insert_sql();
		$this->db->final_transform_query($original_query);
		$check_query = "INSERT INTO `" . $table_prefix . "tablename` (`" . $table_prefix . "tablename`.`x`) VALUES ('1')";
		if (!$this->assert_equals($original_query, $check_query, t("Check Databasefilter simple insert"))) {
			return false;
		}

		if (!$this->assert_true($this->db->query_master('CREATE TABLE `create_table_test` (`test` INT UNSIGNED NOT NULL) ENGINE = InnoDB  DEFAULT CHARSET=utf8;'), t('CREATE TABLE permission check'))) {
			return false;
		}

		if (!$this->assert_true($this->db->query_master('INSERT INTO `create_table_test` (`test`) VALUES (1)'), t('INSERT INTO check'))) {
			return false;
		}

		if (!$this->assert_true($this->db->query_master('DROP TABLE `create_table_test`'), t('DROP TABLE permission check'))) {
			return false;
		}

		return true;
	}

	public function check_database_test_envoirement() {

		$this->db->table_prefix($this->original_table_prefix);

		$database = $this->db->query_field("SELECT DATABASE()");
		$this->db->table_prefix('');
		$tables = DatabaseFilter::create('information_schema`.`TABLES')
				->add_column('TABLE_NAME')
				->add_where('TABLE_SCHEMA', $database)
				->add_where('TABLE_NAME', $this->original_table_prefix . '%', 'LIKE');

		$needed_test_tables = array();
		foreach ($tables->select_all(0, true) AS $table_name) {
			if (!preg_match("/^" . preg_quote($this->original_table_prefix, '/') . "/", $table_name)) {
				continue;
			}
			if (preg_match("/^test_" . preg_quote($this->original_table_prefix, '/') . "/", $table_name)) {
				continue;
			}
			$needed_test_tables['test_' . $table_name] = true;
		}

		$tables = DatabaseFilter::create('information_schema`.`TABLES')
				->add_column('TABLE_NAME')
				->add_where('TABLE_SCHEMA', $database)
				->add_where('TABLE_NAME', 'test_' . $this->original_table_prefix . '%', 'LIKE');

		foreach ($tables->select_all(0, true) AS $table_name) {
			if (!preg_match("/^" . preg_quote('test_' . $this->original_table_prefix, '/') . "/", $table_name)) {
				continue;
			}
			unset($needed_test_tables[$table_name]);
		}

		$this->db->query_master("INSERT INTO `test_" . $this->original_table_prefix . CoreModulConfigObj::TABLE . "` (modul, key, value) ('system', 'installed', '1')");

		$this->db->query_master("INSERT INTO `test_" . $this->original_table_prefix . LanguagesObj::TABLE . "` SELECT * FROM `" . $this->original_table_prefix . LanguagesObj::TABLE . "`");

		$this->db->query_master("INSERT INTO `test_" . $this->original_table_prefix . CoreRightObj::TABLE . "` SELECT * FROM `" . $this->original_table_prefix . CoreRightObj::TABLE . "`");
		$this->db->query_master("INSERT INTO `test_" . $this->original_table_prefix . ModulConfigObj::TABLE . "` SELECT * FROM `" . $this->original_table_prefix . ModulConfigObj::TABLE . "`");
		$this->db->query_master("INSERT INTO `test_" . $this->original_table_prefix . MimeTypeObj::TABLE . "` SELECT * FROM `" . $this->original_table_prefix . MimeTypeObj::TABLE . "`");
		$this->db->query_master("INSERT INTO `test_" . $this->original_table_prefix . UserObj::TABLE . "` SELECT * FROM `" . $this->original_table_prefix . UserObj::TABLE . "` WHERE `user_id` = 1");
		$this->db->query_master("INSERT INTO `test_" . $this->original_table_prefix . UserRightObj::TABLE . "` SELECT * FROM `" . $this->original_table_prefix . UserRightObj::TABLE . "` WHERE `user_id` = 1");

		$this->db->table_prefix('test_' . $this->original_table_prefix);

		if (!$this->assert_empty($needed_test_tables, t('Not all test databases could be created: @dbases', array('@dbases' => var_export($needed_test_tables, true))))) {
			return false;
		}

		return true;
	}

	public function check_core() {
		global $memcached_use;

		######## Check static core cache #######################################
		$this->core->cache('test', 'key1', 'check');
		if (!$this->assert_equals($this->core->cache('test', 'key1'), 'check', t('Check static core cache'))) {
			return false;
		}

		$this->core->cache('test', 'key2', array(1));
		if (!$this->assert_equals($this->core->cache('test', 'key2'), array(1), t('Check static core cache with array value'))) {
			return false;
		}

		######## Check if we have a memcached object ###########################
		if ($memcached_use) {
			if (!$this->assert_true(($this->core->memcache_obj instanceof memcached), t('Check memcached object instance: memcached'))) {
				return false;
			}
		}
		//Memcached not exist, try to get memcache with the wrapper for memcached
		else if (class_exists("memcache")) {
			if (!$this->assert_true(($this->core->memcache_obj instanceof MemcachedWrapper), t('Check memcached object instance: MemcachedWrapper'))) {
				return false;
			}
		}
		//Memcache also not exist, try to use database memcached wrapper
		else if (!empty($this->db)) {
			if (!$this->assert_true(($this->core->memcache_obj instanceof DBMemcached), t('Check memcached object instance: DBMemcached'))) {
				return false;
			}
		}
		//We use no database connection so we have no realy cache, we use a static memcached wrapper
		else {
			if (!$this->assert_true(($this->core->memcache_obj instanceof StaticMemcached), t('Check memcached object instance: StaticMemcached'))) {
				return false;
			}
		}

		######## Check memcache functions ######################################
		$this->core->memcache_obj->get('notfound');
		if (!$this->assert_true(($this->core->memcache_obj->getResultCode() != Memcached::RES_SUCCESS), t('Memcache check res not found'))) {
			return false;
		}

		$check_array = array('1', '2', 'df' => '1');
		if (!$this->assert_true($this->core->memcache_obj->set('key1', $check_array), t('Memcache check: set value'))) {
			return false;
		}
		if (!$this->assert_equals($this->core->memcache_obj->get('key1'), $check_array, t('Memcache check value equals'))) {
			return false;
		}

		if (!$this->assert_false($this->core->mcache('notfound'), t('Core mcache: res not found'))) {
			return false;
		}
		if (!$this->assert_equals($this->core->mcache('key1'), $check_array, t('Core mcache: res not found'))) {
			return false;
		}

		if (!$this->assert_true($this->core->memcache_obj->delete('key1'), t('Memcache: res delete'))) {
			return false;
		}
		if (!$this->assert_false($this->core->mcache('key1'), t('Core mcache: check if res is really deleted.'))) {
			return false;
		}

		if (!$this->assert_true($this->core->mcache('key1', $check_array, time() + 2), t('Core mcache: check set with expire.'))) {
			return false;
		}
		if (!$this->assert_equals($this->core->mcache('key1'), $check_array, t('Core mcache: res still valid and found'))) {
			return false;
		}

		// Wait here because we need to check if the expires work
		sleep(3);
		if (!$this->assert_false($this->core->mcache('key1'), t('Core mcache: check if res is expired and therefore deleted.'))) {
			return false;
		}


		######## Check core_config #############################################
		$core_config = array(
			'depth1' => array(
				'depth2' => array(
					'depth3' => array(
						'value 1.2.3-1',
						'value 1.2.3-2',
						'value 1.2.3-3',
						'value 1.2.3-4',
						'value 1.2.3-5',
					),
				),
				'depth3' => array(
					'depth3' => array(
						'value 1.3.3-1',
						'value 1.3.3-2',
						'value 1.3.3-3',
						'value 1.3.3-4',
						'value 1.3.3-5',
					),
				),
			),
		);

		$this->core->core_config('test', 'depth0', $core_config);

		if (!$this->assert_equals($this->core->core_config('test', 'depth0'), $core_config, t('core_config hole key check'))) {
			return false;
		}
		if (!$this->assert_equals($this->core->core_config('test', array('depth0', 'depth1', 'depth2')), array('depth3' => array(
						'value 1.2.3-1',
						'value 1.2.3-2',
						'value 1.2.3-3',
						'value 1.2.3-4',
						'value 1.2.3-5',
						)), t('core_config check getting sub depth values'))) {
			return false;
		}


		######## Check dbconfig ################################################
		if (!$this->assert_null($this->core->dbconfig('somemodul', 'keynotexist'), t('Check dbconfig: key not exist'))) {
			return false;
		}
		if (!$this->assert_equals($this->core->get_dbconfig('somemodul', 'keynotexist', 'default_value'), 'default_value', t('Check get_dbconfig: key not exist (default value check)'))) {
			return false;
		}
		if (!$this->assert_true($this->core->dbconfig('somemodul', 'set', 'value', true), t('Check dbconfig: set key: simple string (with cache enabled)'))) {
			return false;
		}
		if (!$this->assert_equals($this->core->dbconfig('somemodul', 'set'), 'value', t('Check dbconfig: get key: simple string'))) {
			return false;
		}
		if (!$this->assert_equals($this->core->get_dbconfig('somemodul', 'set', 'default'), 'value', t('Check get_dbconfig: get key: simple string'))) {
			return false;
		}

		$filter = DatabaseFilter::create(CoreModulConfigObj::TABLE)
				->change_fields('value', 'changed_direct_db')
				->add_where('key', 'set')
				->add_where('modul', 'somemodul');

		if (!$this->assert_true($filter->update(), t('Check: DatabaseFilter: update'))) {
			return false;
		}

		if (!$this->assert_equals($this->core->get_dbconfig('somemodul', 'set', 'default', true), 'value', t('Check get_dbconfig: get key: cached simple string'))) {
			return false;
		}
		if (!$this->assert_true($this->core->dbconfig('somemodul', 'set', 'value_uncached_overridden'), t('Check dbconfig: override key: simple string: without cache'))) {
			return false;
		}
		if (!$this->assert_equals($this->core->get_dbconfig('somemodul', 'set', 'default', true), 'value', t('Check get_dbconfig: get key: cached simple string after override key without cache'))) {
			return false;
		}
		if (!$this->assert_true($this->core->dbconfig('somemodul', 'set', 'value_cached_overridden', true), t('Check dbconfig: override key: simple string: with cache'))) {
			return false;
		}
		if (!$this->assert_equals($this->core->get_dbconfig('somemodul', 'set', 'default', true), 'value_cached_overridden', t('Check get_dbconfig: get key: cached simple string after override key'))) {
			return false;
		}

		$not_scalar = array(
			'key' => 'value'
		);

		if (!$this->assert_true($this->core->dbconfig('somemodul', 'set_not_scalar', $not_scalar), t('Check dbconfig: set value which is not a scaler'))) {
			return false;
		}
		if (!$this->assert_equals($this->core->get_dbconfig('somemodul', 'set_not_scalar', 'default', true), $not_scalar, t('Check get_dbconfig: get key which is not a scalar, cache enabled but should not be found'))) {
			return false;
		}
		if (!$this->assert_equals($this->core->get_dbconfig('somemodul', 'set_not_scalar', true), $not_scalar, t('Check dbconfig: get key which is not a scalar, cache enabled but should not be found'))) {
			return false;
		}

		######## Check load_mime_types #########################################
		$this->core->load_mime_types();
		if (empty($this->core->mime_types)) {
			$this->core->message(t('Maybe we have not fetched it yet, try to get fresh mime type array. This may take a while... time to get a coffee.'), Core::MESSAGE_TYPE_NOTICE);
			$mime_type_cli = new cli_generate_mimetype_list();
			$mime_type_cli->start();
			$this->core->load_mime_types();

		}
		if (!$this->assert_not_empty($this->core->mime_types, t('Check load_mime_types'))) {
			return false;
		}

		######## Check module_enabled ##########################################
		if (!$this->assert_true($this->core->module_enabled('user'), t('Check module_enabled on user'))) {
			return false;
		}
		if (!$this->assert_null($this->core->module_enabled('user', false), t('Check module_enabled on user set to disabled'))) {
			return false;
		}
		if (!$this->assert_false($this->core->module_enabled('user'), t('Check module_enabled on user, should be disabled now.'))) {
			return false;
		}
		$this->core->module_enabled('user', true);

		######## Check js_config ###############################################
		$this->core->js_config('key1', 'value1');
		$current_config = $this->core->get_js_config();
		if (!$this->assert_true(isset($current_config['key1']), t('Check js_config: check if key was found'))) {
			return false;
		}
		if (!$this->assert_equals($current_config['key1'], 'value1', t('Check js_config: check get value'))) {
			return false;
		}
		$this->core->js_config('key1', 'value2');
		$current_config = $this->core->get_js_config();
		if (!$this->assert_equals($current_config['key1'], 'value2', t('Check js_config: check get overridden value'))) {
			return false;
		}
		$this->core->js_config('key1', 'value3', true);
		$current_config = $this->core->get_js_config();
		if (!$this->assert_equals($current_config['key1'], array('value2', 'value3'), t('Check js_config: check get direct appended array value (old one needs to exist too)'))) {
			return false;
		}
		$this->core->js_config('key1', 'value4', true, 'arraykey');
		$current_config = $this->core->get_js_config();
		if (!$this->assert_equals($current_config['key1'], array('value2', 'value3', 'arraykey' => 'value4'), t('Check js_config: check get appended value with specific arraykey'))) {
			return false;
		}
	}

	/**
	 * Checks functionallity of Configuration class
	 */
	public function check_configuration() {
		$config = new Configuration();

		if (!$this->assert_null($config->get('not_exist'), t('Check Configuration: getting not existing key'))) {
			return false;
		}
		if (!$this->assert_equals($config->get('not_exist', 'default'), 'default', t('Check Configuration: getting default value from not existing key'))) {
			return false;
		}
		if (!$this->assert_false($config->is_set('not_exist'), t('Check Configuration: is_set not existing key'))) {
			return false;
		}

		$config->set('exist_key', 'exist_value');
		if (!$this->assert_equals($config->get('exist_key'), 'exist_value', t('Check Configuration: get existing key'))) {
			return false;
		}

		if (!$this->assert_true($config->is_set('exist_key'), t('Check Configuration: is_set existing key'))) {
			return false;
		}

		$config->enable('exist_key');
		if (!$this->assert_true($config->get('exist_key'), t('Check Configuration: overwritten key value with enable()'))) {
			return false;
		}

		$config->disable('exist_key_false');
		if (!$this->assert_false($config->get('exist_key_false'), t('Check Configuration: disable()'))) {
			return false;
		}
	}

	/**
	 * Checks functionallity of Cron class
	 */
	public function check_cron() {
		$cron = new Cron();

		######## Check match time ##############################################
		$cron->set_match_time(Cron::CRON_TIME_DAY_OF_MONTH, 3);
		if (!$this->assert_false($cron->match(), t('Check unconfigured match time'))) {
			return false;
		}

		if (!$this->assert_true($cron->match("*", function() {}, TIME_NOW), t('Check always match *'))) {
			return false;
		}

		$five_minute_intervall_check = "*/5 * * * *";
		$five_minute_manual_check = "0,5,10,15,20,25,30,35,40,45,50,55 * * * *";
		for ($i = 0; $i < 60; $i++) {
			$m = $i;
			if ($m < 10) {
				$m = "0" . $m;
			}
			$check_time = strtotime(date("Y-m-d H:" . $m . ":00"));

			if ($i%5 === 0) {
				if (!$this->assert_true($cron->match($five_minute_intervall_check, function() {}, $check_time), t('Check match: */5 * * * *: @val', array('@val' => $m)))) {
					return false;
				}
				if (!$this->assert_true($cron->match($five_minute_manual_check, function() {}, $check_time), t('Check match: 0,5,10,15,20,25,30,35,40,45,50,55 * * * * with val: @val', array('@val' => $m)))) {
					return false;
				}
			}
			else {
				if (!$this->assert_false($cron->match($five_minute_intervall_check, function() {}, $check_time), t('Check NO match: */5 * * * *: @val', array('@val' => $m)))) {
					return false;
				}
				if (!$this->assert_false($cron->match($five_minute_manual_check, function() {}, $check_time), t('Check NO match: 0,5,10,15,20,25,30,35,40,45,50,55 * * * * with val: @val', array('@val' => $m)))) {
					return false;
				}
			}
		}

		$five_minute_with_hour_range = "*/5 5-8 * * *";
		for ($ih = 0; $ih < 24; $ih++) {
			$h = $ih;
			if ($h < 10) {
				$h = "0" . $h;
			}

			for ($i = 0; $i < 60; $i++) {
				$m = $i;
				if ($m < 10) {
					$m = "0" . $m;
				}
				$check_time = strtotime(date("Y-m-d " . $h . ":" . $m . ":00"));

				if ($ih >= 5 && $ih <= 8 && $i%5 === 0) {
					if (!$this->assert_true($cron->match($five_minute_with_hour_range, function() {}, $check_time), t('Check range match: */5 5-8 * * *: h: @valh m: @val', array(
						'@valh' => $h,
						'@val' => $m,
					)))) {
						return false;
					}
				}
				else {
					if (!$this->assert_false($cron->match($five_minute_with_hour_range, function() {}, $check_time), t('Check range NO match: */5 5-8 * * *: h: @valh m: @val', array(
						'@valh' => $h,
						'@val' => $m,
					)))) {
						return false;
					}
				}
			}
		}

		$multiple_minute_check = "*/5,2-4,8 * * * *";
		for ($i = 0; $i < 60; $i++) {
			$m = $i;
			if ($m < 10) {
				$m = "0" . $m;
			}
			$check_time = strtotime(date("Y-m-d H:" . $m . ":00"));

			if ($i === 2 || $i === 3 || $i === 4 || $i === 8 || $i%5 === 0) {
				if (!$this->assert_true($cron->match($multiple_minute_check, function() {}, $check_time), t('Check range match: */5,2-4,8 * * * *: m: @val', array(
					'@val' => $m,
				)))) {
					return false;
				}
			}
			else {
				if (!$this->assert_false($cron->match($multiple_minute_check, function() {}, $check_time), t('Check range NO match: */5,2-4,8 * * * *: m: @val', array(
					'@val' => $m,
				)))) {
					return false;
				}
			}
		}
		######## Check callable ################################################
		global $cron_called;
		$cron_called = false;

		$cron->match("*", function() {
			global $cron_called;
			$cron_called = true;
		}, TIME_NOW);

		if (!$this->assert_true($cron_called, t('Check anonymous callable function'))) {
			return false;
		}

		$cron_called = false;
		$cron->match("*", "callable_function", TIME_NOW);
		if (!$this->assert_true($cron_called, t('Check callable function name'))) {
			return false;
		}

		$cron_called = false;
		$cron->match("*", array('callable_class', 'callable_method'), TIME_NOW);
		if (!$this->assert_true($cron_called, t('Check callable classmethod'))) {
			return false;
		}

	}

	/**
	 * Checks basic user creation.
	 *
	 * @return boolean true if all works, else false
	 */
	public function check_user_creation() {
		$this->db->transaction_begin();
		$user_obj = new UserObj();
		$user_obj->username = 'admin_create';
		$user_obj->password = 'admin';
		$user_obj->active = 'yes';
		if (!$this->assert_true($user_obj->insert(), t('Create user object'))) {
			$this->db->transaction_rollback();
			return false;
		}

		$user_right_obj = new UserRightObj();
		$user_right_obj->user_id = $user_obj->user_id;
		$user_right_obj->permissions = "*";
		if (!$this->assert_true($user_right_obj->insert(), t('Create user right object'))) {
			$this->db->transaction_rollback();
			return false;
		}

		$user_address_obj = new UserAddressObj();
		$user_address_obj->email = 'testadmin@localhost';
		$user_address_obj->user_id = $user_obj->user_id;
		if (!$this->assert_true($user_address_obj->insert(), t('Create user address object'))) {
			$this->db->transaction_rollback();
			return false;
		}

		$load = new UserObj($user_obj->user_id);
		if (!$this->assert_true($load->load_success(), t('Load user object'))) {
			$this->db->transaction_rollback();
			return false;
		}

		if (!$this->assert_equals($load->username, 'admin_create', t('Verify correct user name'))) {
			$this->db->transaction_rollback();
			return false;
		}

		if (!$this->assert_not_equals($load->password, 'admin', t('Verify correct user password'))) {
			$this->db->transaction_rollback();
			return false;
		}

		$load = new UserRightObj($user_obj->user_id);
		if (!$this->assert_true($load->load_success(), t('Load user right object'))) {
			$this->db->transaction_rollback();
			return false;
		}
		$load = new UserAddressObj($user_address_obj->id);
		if (!$this->assert_true($load->load_success(), t('Load user address object'))) {
			$this->db->transaction_rollback();
			return false;
		}


		$this->db->transaction_commit();
		return true;
	}

}

function callable_function() {
	global $cron_called;
	$cron_called = true;
}

class callable_class {
	public function callable_method() {
		global $cron_called;
		$cron_called = true;
	}
}

