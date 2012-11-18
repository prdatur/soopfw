<?php

/**
 * Provides a class to rotate audit entries stored in the log database.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @module System
 * @category Module
 */
class AuditLogRotator extends Object
{
	/**
	 * Rotates the system log.
	 *
	 * Will generate a new archive entry with current entries and truncate the current system log table.
	 */
	public static function rotate() {
		// Get the current entries as csv based output
		$csv = new ModelExporterXML('SystemLogObj');

		// Lock database tables.
		$csv->get_db()->query_master('LOCK TABLES `' . SystemLogObj::TABLE . '` WRITE, `' . SystemLogArchiveObj::TABLE . '` WRITE');

		// Build filter to query data.
		$filter = DatabaseFilter::create(SystemLogObj::TABLE)->order_by('date');
		$csv->add_database_filter($filter);

		// Get the archive csv data.
		$data = $csv->get_data();

		// Only archive it if we have some data.
		if (!empty($data)) {

			// Get the archive object.
			$archive_obj = new SystemLogArchiveObj();
			$archive_obj->data = gzencode($csv->get_data(), 9);

			// Get the first date for the log archive.
			$first = $filter->clear_columns()->add_column('date')->select_first();
			$archive_obj->date_from = $first['date'];

			// Get the last date for the log archive.
			$last = $filter->order_by('date', DatabaseFilter::DESC)->select_first();
			$archive_obj->date_to = $last['date'];

			// If we could insert our archive truncate current system log.
			if ($archive_obj->insert()) {
				$csv->get_db()->query_master('TRUNCATE `' . SystemLogObj::TABLE . '`');
			}
		}

		// Unlock tables.
		$csv->get_db()->query_master('UNLOCK TABLES');
	}


}