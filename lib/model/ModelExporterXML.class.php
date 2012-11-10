<?php

/**
 * Provides a class to generate a xml entry which represents a model database entry.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Model
 */
class ModelExporterXML extends AbstractModelExporter
{
	/**
	 * The configuration
	 *
	 * @var ModelExporterXMLConfiguration
	 */
	private $configuration = null;

	/**
	 * Setup the model class.
	 *
	 * @param string model_class
	 *   The model class.
	 *
	 * @throws SoopfwWrongParameterException
	 */
	public function __construct($model_class, ModelExporterXMLConfiguration $configuration = null) {
		parent::__construct($model_class);

		// Setup csv model exporter configuration.
		if ($configuration === null) {
			$configuration = new ModelExporterXMLConfiguration();
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
		$row_string = '	<' . $this->configuration->get(ModelExporterXMLConfiguration::ROW_TAG, 'row'). '>';
		// Convert the value to type specific like quotes for strings.
		foreach ($row AS $k => &$v) {
			$v = '		<' . $k . '>' . $v . '</' . $k . '>';
		}
		$row_string .= "\n" . implode("\n", $row);
		return $row_string."\n	</" . $this->configuration->get(ModelExporterXMLConfiguration::ROW_TAG, 'row'). '>';
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
		return '<' . $this->configuration->get(ModelExporterXMLConfiguration::CONTAINER_TAG, 'data'). ">\n" . implode("\n", $rows) . "\n</" . $this->configuration->get(ModelExporterXMLConfiguration::CONTAINER_TAG, 'data'). '>';
	}
}