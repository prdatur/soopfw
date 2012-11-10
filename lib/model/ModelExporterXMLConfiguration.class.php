<?php
/**
 * Configuration for xml model exporter.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
class ModelExporterXMLConfiguration extends Configuration {

	/**
	 * The split char for the values.
	 *
	 * @use: set(), get()
	 * @return string
	 */
	const CONTAINER_TAG = 0;

	/**
	 * The split char for each row.
	 *
	 * @use: set(), get()
	 * @return string
	 */
	const ROW_TAG = 1;
}

