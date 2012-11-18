<?php

/**
 * This holds all available mime types with the file extensions
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @module System
 * @category Objects
 */
class MimeTypeObj extends AbstractDataManagement
{
	/**
	 * Define constances
	 */
	const TABLE = "system_mime_types";

	/**
	 * Constructor
	 *
	 * @param string $extension
	 *   the file extension (optional, default = '')
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($extension = "", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key("extension");
		$this->db_struct->add_field("extension", t("the file extension"), PDT_STRING);
		$this->db_struct->add_field("mime_type", t("The mime type"), PDT_STRING);

		if (!empty($extension)) {
			if (!$this->load($extension, $force_db)) {
				return false;
			}
		}
	}

	/**
	 * This will return the found file extension for a given mime type.
	 *
	 * @param string $mime_type
	 *   the mime type
	 * @param boolean $get_all_as_array
	 *   Set to true if you want all available extensions as an array.
	 *
	 * @return mixed the first found file extension as string if $get_all_as_array is set to false, else get an array with all extensions
	 */
	public static function get_extension($mime_type, $get_all_as_array = false) {
		$filter = DatabaseFilter::create(self::TABLE)
			->add_column('extension')
			->add_where('mime_type', $mime_type);

		if ($get_all_as_array === false) {
			return $filter->select_first();
		}
		else  {
			return $filter->select_all('extension');
		}
	}
}
