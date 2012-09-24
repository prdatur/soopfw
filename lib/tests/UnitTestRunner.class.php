<?php
/**
 * This class is used to run unit tests.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Testing
 */
class UnitTestRunner extends Object {

	/**
	 * Holds the passed test count.
	 *
	 * @var int
	 */
	public $passed_tests = 0;

	/**
	 * Holds the failed test count.
	 *
	 * @var int
	 */
	public $failed_tests = 0;

	/**
	 * The old original table prefix.
	 *
	 * @var string
	 */
	protected $original_table_prefix = "";

	/**
	 * Holds all created test tables, those will be deleted on destructor.
	 * #
	 * @var array
	 */
	private $database_test_envoirements_tables = array();

	public function __destruct() {

		// Drop all created test tables.
		if (!empty($this->database_test_envoirements_tables)) {
			$this->core->message(t('Removing database test envoirement.'), Core::MESSAGE_TYPE_NOTICE);
			$this->db->table_prefix('');
			$i = 0;
			$args = array();
			$tables = array();
			foreach ($this->database_test_envoirements_tables AS $table_name) {
				$tables[] = '`:' . ++$i . 'table_name`';
				$args[':' . $i . 'table_name'] = $table_name;
			}

			$this->db->query_master('DROP TABLE ' . implode(',', $tables), $args);
			$this->core->message(t('Test envoirement removed.'), Core::MESSAGE_TYPE_SUCCESS);
		}

		// Reset original table prefix.
		$this->db->table_prefix($this->original_table_prefix);
	}


	/**
	 * Prepare the database for the unit test.
	 */
	public function prepare_tests() {

		if (!empty($this->db)) {
			$this->original_table_prefix = $this->db->table_prefix();
			$this->db->table_prefix('test_' . $this->original_table_prefix);
		}
		if (!empty($this->core->memcache_obj)) {
			$this->core->memcache_obj->flush();
			$this->core->mcache_set_prefix('test_' . $this->original_table_prefix);
		}

	}

	/**
	 * Call this if all tests are finished.
	 * This is important because the table prefis is changed during the test
	 * and this will restore the old one.
	 */
	public function end_tests() {
		$this->db->table_prefix($this->original_table_prefix);
	}

	public function run_tests(Array $tests) {

		// Prepare for unit tests.
		$this->prepare_tests();

		// Reset test result count
		$this->passed_tests = $this->failed_tests = 0;

		$logs = array();

		// Unset MainSystemTest within provided test array because we need this test
		// executed as the first one.
		$tests = array_flip($tests);
		if (isset($tests['MainSystemTest'])) {
			unset($tests['MainSystemTest']);
		}

		$tests = array_flip($tests);

		// Get the MainSystemTest as the first one.
		array_unshift($tests, 'MainSystemTest');

		foreach ($tests AS $test_classname) {

			// Skip if test class does not exist.
			if (!class_exists($test_classname)) {
				continue;
			}

			/* @var $test_class UnitTestInterface */
			$test_class = new $test_classname($this->original_table_prefix);

			// Get all available tests.
			$available_testes = $test_class->get_tests();

			// If we got an empty array from the interface method, we will use all public methods
			// within the class.
			if (empty($available_testes)) {
				$class = new ReflectionClass($test_classname);
				$public_methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
				foreach ($public_methods AS $method) {
					if ($method->class != $test_classname || $method->name == 'get_tests') {
						continue;
					}

					$available_testes[] = $method->name;
				}
			}
			$this->core->message(t('Running test: @test (@count test/s)', array('@test' => $test_classname, '@count' => count($available_testes))), Core::MESSAGE_TYPE_NOTICE);
			// Run all wanted tests.
			foreach ($available_testes AS $test_method) {
				$this->core->message(t('Checking : @test', array('@test' => $test_method)), Core::MESSAGE_TYPE_NOTICE);
				// Run the test.
				$return = $test_class->$test_method();

				// If we run the MainSystemTest and we got a boolean false as the result
				// we can not execute any further tests.
				if ($test_classname == 'MainSystemTest' && $return === false) {

					// Increment test count.
					$this->passed_tests += $test_class->get_passed_test_count();
					$this->failed_tests += $test_class->get_failed_test_count();

					// Merge logs.
					$logs = array_merge($logs, $test_class->get_results());

					$this->core->message(t('!!!WARNING!!! MainSystemTest failed, aborting all other tests.'), Core::MESSAGE_TYPE_ERROR);
					return $logs;
				}

				if ($test_classname === 'MainSystemTest' && $test_method === 'check_main_database') {
					$this->create_database_test_envoirement();
				}
			}

			// Increment test count.
			$this->passed_tests += $test_class->get_passed_test_count();
			$this->failed_tests += $test_class->get_failed_test_count();

			// Merge logs.
			$logs = array_merge($logs, $test_class->get_results());
		}

		// Return results.
		return $logs;
	}

	public function create_database_test_envoirement() {

		$this->core->message(t('Creating database test envoirement.'), Core::MESSAGE_TYPE_NOTICE);
		$this->db->table_prefix($this->original_table_prefix);

		$database = $this->db->query_field("SELECT DATABASE()");
		$this->db->table_prefix('');
		$tables = DatabaseFilter::create('information_schema`.`TABLES')
			->add_column('TABLE_NAME')
			->add_where('TABLE_SCHEMA', $database);


		foreach ($tables->select_all(0, true) AS $table_name) {
			if (!preg_match("/^" . preg_quote($this->original_table_prefix, '/') . "/", $table_name)) {
				continue;
			}

			$this->db->query_master('CREATE TABLE `test_' . $table_name . '`  LIKE `' . $table_name . '`');
			$this->database_test_envoirements_tables[] = 'test_' . $table_name;
		}


		$this->db->table_prefix('test_' . $this->original_table_prefix);
		$this->core->message(t('Test tables created.'), Core::MESSAGE_TYPE_SUCCESS);
	}
}