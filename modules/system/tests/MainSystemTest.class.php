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
class MainSystemTest extends UnitTest implements UnitTestInterface {

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

		$original_query = "ON DUPLICATE KEY UPDATE `tablename`.field = 1 WHERE `tablename`.y = 1";
		$this->db->final_transform_query($original_query);
		$check_query = "ON DUPLICATE KEY UPDATE `" . $table_prefix . "tablename`.field = 1 WHERE `" . $table_prefix . "tablename`.y = 1";
		if (!$this->assert_equals($original_query, $check_query, t("Check plain ON DUPLICATE KEY UPDATE"))) {
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
			->add_where('subfield1', '34')
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

		$check_query = "SELECT `" . $table_prefix . "tablename`.* FROM `" . $table_prefix . "tablename` JOIN `" . $table_prefix . "aliastablename` AS aliasjoin ON (aliasjoin.`x` = `database1`.`" . $table_prefix . "tablename2`.`y`) LEFT JOIN `" . $table_prefix . "tablename3` ON (`" . $table_prefix . "tablename3`.x = `" . $table_prefix . "tablename2`.y) WHERE (`x` = '1' AND `" . $table_prefix . "tablename2`.`y` = '1' AND `tests_soopfw_tablename`.`x` >= (SELECT count(*) FROM `tests_soopfw_subtable`  WHERE (`subfield1` = '34' AND `subfield1` = aliasjoin.`field1`) ))  GROUP BY `tests_soopfw_tablename`.`field1`, `tests_soopfw_tablename2`.`field2` ORDER BY `" . $table_prefix . "tablename`.`x` asc, `" . $table_prefix . "tablename2`.`y` asc";
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
			->add_where('TABLE_NAME', $this->original_table_prefix . '%' , 'LIKE');

		$needed_test_tables = array();
		foreach ($tables->select_all(0, true) AS $table_name) {
			if (!preg_match("/^" . preg_quote($this->original_table_prefix, '/') . "/", $table_name)) {
				continue;
			}
			$needed_test_tables['test_' . $table_name] = true;
		}

		$tables = DatabaseFilter::create('information_schema`.`TABLES')
			->add_column('TABLE_NAME')
			->add_where('TABLE_SCHEMA', $database)
			->add_where('TABLE_NAME', 'test_' . $this->original_table_prefix . '%' , 'LIKE');

		foreach ($tables->select_all(0, true) AS $table_name) {
			if (!preg_match("/^" . preg_quote('test_' . $this->original_table_prefix, '/') . "/", $table_name)) {
				continue;
			}
			unset($needed_test_tables[$table_name]);
		}


		$this->db->table_prefix('tests_' . $this->original_table_prefix);

		if (!$this->assert_empty($needed_test_tables, t('All test databases could be created'))) {
			return false;
		}

		return true;
	}
}
?>
