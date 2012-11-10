<?php

/**
 * Provides an abstract class to export models.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Model
 */
abstract class AbstractModelExporter extends Object
{
	/**
	 * Holds the model class.
	 *
	 * @var AbstractDataManagement
	 */
	protected $model_class = '';

	/**
	 * Holds all primary keys which we want to load and include within the output.
	 *
	 * @var array
	 */
	private $primary_keys = array();

	/**
	 * The database filter which is used to load the data.
	 *
	 * @var DatabaseFilter
	 */
	private $model_database_filter = null;

	/**
	 * Setup the model class.
	 *
	 * @param string model_class
	 *   The model class.
	 *
	 * @throws SoopfwWrongParameterException
	 */
	public function __construct($model_class) {
		if (!class_exists($model_class)) {
			throw new SoopfwWrongParameterException(t('Unknown model class'));
		}
		$this->model_class = new $model_class();

		if (!($this->model_class instanceof AbstractDataManagement)) {
			throw new SoopfwWrongParameterException(t('Provided class is not a AbstractDataManagement childs'));
		}
	}

	/**
	 * Adds an object identifier (primary key) to our list of generated entries.
	 *
	 * @param string|array $primary_key
	 *   The primary key based up on the provided $model_class within intializing.
	 */
	public function add_primary_key($primary_key) {
		if (!is_array($primary_key)) {
			$primary_key = array($primary_key);
		}
		$this->primary_keys[] = $primary_key;
	}

	/**
	 * Instead of adding primary keys by self we can use this method to direct set a database filter to load
	 * within the "load_multiple" method at the $model_class object.
	 *
	 * If this is used previous added primary keys with "add_primary_key" has no effect.
	 *
	 * @param DatabaseFilter &$filter
	 *   The filter to use.
	 */
	public function add_database_filter(DatabaseFilter &$filter) {

		// Clear previous added fields
		$filter->clear_columns();

		// Get all reference keys for the model.
		$ref_key = $this->model_class->get_dbstruct()->get_reference_key();

		// Add all primary keys to our field list.
		foreach ($ref_key AS $key) {
			$filter->add_column($key);
		}

		// Setup the filter.
		$this->model_database_filter = $filter;
	}

	/**
	 * Returns the data.
	 *
	 * Make sure you have setup a primary key with "add_primary_key" or a database filter with "add_database_filter"
	 *
	 * @return mixed The encoded data.
	 */
	public function get_data() {

		// Prio the database filter.
		if (!empty($this->model_database_filter)) {
			$keys = $this->model_database_filter->select_all();
		}
		else {
			$keys = $this->primary_keys;
		}

		// Return empty string if we provided no primary keys and no filter.
		if (empty($keys)) {
			return '';
		}

		// Initialize our rows
		$rows = array();

		// Get all entries.
		$entries = $this->model_class->load_multiple($keys, PDT_ARR);

		// Return empty string if we have no data.
		if (empty($entries)) {
			return '';
		}

		// Loop through all found entries.
		foreach ($entries AS $row) {
			// Add the value row.
			$rows[] = $this->parse_row($row);;
		}

		// Return the data.
		return $this->parse_rows($rows);
	}

	/**
	 * Parses the row.
	 *
	 * @param array $row
	 *   The row which needs to be parsed.
	 *
	 * @return mixed The parsed row.
	 */
	abstract protected function parse_row(&$row);

	/**
	 * Parses the rows.
	 *
	 * @param array $rows
	 *   The rows which needs to be parsed.
	 *
	 * @return mixed The parsed data.
	 */
	abstract protected function parse_rows(&$rows);
}