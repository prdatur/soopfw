<?php
/**
 * Configuration for csv model exporter.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
class ModelExporterCSVConfiguration extends Configuration {

	/**
	 * The split char for the values.
	 *
	 * @use: set(), get()
	 * @return string
	 */
	const SPLIT_CHAR = 0;

	/**
	 * The split char for each row.
	 *
	 * @use: set(), get()
	 * @return string
	 */
	const ENTRY_CHAR = 1;

	/**
	 * Whether to include the header fields or not.
	 *
	 * @use: enable(), disable(), is_enabled(), is_disabled()
	 * @return boolean
	 */
	const INCLUDE_HEADER = 2;
}

