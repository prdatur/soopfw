<?php

/**
 * Provides a class to generate a csv entry which represents a model database entry.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Model
 */
class ModelExporterCSV extends AbstractModelExporter
{
	/**
	 * The configuration
	 *
	 * @var ModelExporterCSVConfiguration
	 */
	private $configuration = null;

	/**
	 * The headers which will be stored within first parse_row call.
	 *
	 * @var array
	 */
	private $header = null;
	/**
	 * Setup the model class.
	 *
	 * @param string model_class
	 *   The model class.
	 *
	 * @throws SoopfwWrongParameterException
	 */
	public function __construct($model_class, ModelExporterCSVConfiguration $configuration = null) {
		parent::__construct($model_class);

		// Setup csv model exporter configuration.
		if ($configuration === null) {
			$configuration = new ModelExporterCSVConfiguration();
		}
		$this->configuration = $configuration;
	}

	/**
	 * Parses the row.
	 *
	 * @param array $row
	 *   The row which needs to be parsed.
	 *
	 * @param mixed The parsed row.
	 */
	protected function parse_row(&$row) {

		// Only check header data for the first call.
		if ($this->header === null) {

			// Check whether we want to include the header or not.
			if ($this->configuration->is_enabled(ModelExporterCSVConfiguration::INCLUDE_HEADER, true)) {

				// We want the header so surround all keys, which are the field names, with quotes.
				$this->header = array();
				foreach ($row AS $header_field => &$v) {
					$this->header[] = '"' . $header_field . '"';
				}

				// Get the header line.
				$this->header = implode($this->configuration->get(ModelExporterCSVConfiguration::SPLIT_CHAR, ';'), $this->header);
			}
			else {

				// We do not want the header.
				$this->header = false;
			}
		}

		// Convert the value to type specific like quotes for strings.
		foreach ($row AS $k => &$v) {
			switch ($this->model_class->get_dbstruct()->get_field_type($k)) {
				case PDT_STRING:
				case PDT_DATE:
				case PDT_TIME:
				case PDT_DATETIME:
				case PDT_JSON:
				case PDT_PASSWORD:
				case PDT_ENUM:
				case PDT_TEXT:
					$v = '"' . $v . '"';
			}
		}

		return implode($this->configuration->get(ModelExporterCSVConfiguration::SPLIT_CHAR, ';'), $row);
	}

	/**
	 * Parses the rows.
	 *
	 * @param array $rows
	 *   The rows which needs to be parsed.
	 *
	 * @return mixed The parsed data.
	 */
	protected function parse_rows(&$rows) {

		// If we have setup the header fields, prepend it to the beginning.
		if (!empty($this->header)) {
			array_unshift($rows, $this->header);
		}

		// Return the data.
		return implode($this->configuration->get(ModelExporterCSVConfiguration::ENTRY_CHAR, "\n"), $rows);
	}
}