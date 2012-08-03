<?php

/**
 * This main file object is the base of all file uploads / downloads
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.system.objects
 */
class MainFileObj extends AbstractDataManagment
{
	/**
	 * Define constances
	 */
	const STATUS_TEMP = 0;
	const STATUS_PERM = 1;
	const MOVE_FILE = 2;
	const TABLE = "main_files";
	const TYPE = "mainfile";


	/**
	 * the copy function
	 * @var string
	 */
	protected $copy_function = "move_uploaded_file";

	protected $extension = "";

	/**
	 * The old filepath, this is used to delete the old file if it is changed
	 * @var string
	 */
	protected $old_file_path = "";

	/**
	 * All plain text mime types
	 *
	 * @var array
	 */
	private $plain_text_extensions = array();

	/**
	 * Constructor
	 *
	 * @param int $id the file id (optional, default = "")
	 * @param boolean $force_db if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($id = "", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key(array("fid"));
		$this->db_struct->set_auto_increment("fid");
		$this->db_struct->add_field("fid", t("FileID"), PDT_INT, 0, 'UNSIGNED');
		$this->db_struct->add_required_field("type", t("Type"), PDT_STRING);
		$this->db_struct->add_required_field("owner", t("Owner"), PDT_STRING, 120, '');
		$this->db_struct->add_required_field("size", t("Size"), PDT_INT);
		$this->db_struct->add_required_field("mimetype", t("Mime-Type"), PDT_STRING);
		$this->db_struct->add_required_field("filename", t("Filename"), PDT_STRING);
		$this->db_struct->add_required_field("created", t("Created"), PDT_DATETIME, date(DB_DATETIME, TIME_NOW));
		$this->db_struct->add_required_field("status", t("File Status"), PDT_INT, self::STATUS_TEMP, 'UNSIGNED');

		$this->init_mime_types();
		if (!empty($id)) {
			if (!$this->load(array($id), $force_db)) {
				return false;
			}
		}
	}

	/**
	 * Set or get the status, if you provide a value we are in set mode, else in get mode
	 *
	 * @param int $status
	 *   the status, use one of MainFileObj::STATUS_* (optional, default = NS)
	 *
	 * @return int on get mode the status, on set mode always false
	 */
	public function status($status = NS) {
		if ($status === NS) {
			return $this->status;
		}
		$this->status = (int)$status;
		return false;
	}

	/**
	 * load the given data
	 *
	 * @param mixed $val
	 *   The reference key value to be selected
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function load($val = "", $force_db = false) {
		//Load within normal way
		parent::load($val, $force_db);

		//Check if load success
		if ($this->load_success()) {
			//If we loaded successfully we need to get the current path of the file
			$this->old_file_path = $this->get_path();

			//Also we setup an extension if we have one.
			if (preg_match("/\.([^\.]+)$/is", $this->filename, $matches)) {
				$this->extension = $matches[1];
			}
		}
	}

	/**
	 * Save or insert the given data and if $tmp_file is not empty move the new uploaded file to the wanted location
	 * If you provided MainFileObj::MOVE_FILE as $tmp_file it will try to move the current old file to the new location
	 *
	 * @param string $tmp_file
	 *   the path to the temp file or MainFileObj::MOVE_FILE (optional, default = "")
	 * @param boolean $save_if_unchanged
	 *   Save this object even if no changes to it's values were made
	 *
	 * @return true on success, else false
	 */
	public function save_or_insert($tmp_file = "") {
		if ($this->load_success()) {
			return $this->save($tmp_file);
		}
		else {
			return $this->insert($tmp_file);
		}
	}

	/**
	 * Save the given data and if $tmp_file is not empty move the new uploaded file to the wanted location
	 * If you provided MainFileObj::MOVE_FILE as $tmp_file it will try to move the current old file to the new location
	 *
	 * @param string $tmp_file
	 *   the path to the temp file or MainFileObj::MOVE_FILE (optional, default = "")
	 * @param boolean $save_if_unchanged
	 *   Save this object even if no changes to it's values were made
	 *
	 * @return true on success, else false
	 */
	public function save($tmp_file = "", $save_if_unchanged = false) {
		$this->transaction_auto_begin();

		//If the temp file was provided, generate a free path for the current file
		if (!empty($tmp_file)) {
			$this->get_free_path();
		}

		//Save the database values
		if (parent::save($save_if_unchanged)) {

			//If we do not provided a tmp file we can stop here.
			if (empty($tmp_file)) {
				$this->transaction_auto_commit();
				return true;
			}

			//Check if we should move the current old path to the new location
			if ($tmp_file === self::MOVE_FILE) {
				if ($this->mkdir()) {
					if ($this->old_file_path != $this->get_path() && copy($this->old_file_path, $this->get_path())) {
						@unlink($this->old_file_path);
					}
					$this->transaction_auto_commit();
					return true;
				}
			}

			//We must move the current new uploaded file to the new location
			if ($this->mkdir()) {
				if (move_uploaded_file($tmp_file, $this->get_path())) {
					if (!empty($this->old_file_path)) {
						@unlink($this->old_file_path);
					}
					$this->transaction_auto_commit();
					return true;
				}
			}
			$this->transaction_auto_rollback();
		}
		return false;
	}

	/**
	 * This is a wrapper that we can call the parent insert function
	 *
	 * @param boolean $ignore
	 *   Don't throw an error if data is already there (optional, default=false)
	 *
	 * @return boolean true on success, else false
	 */
	public function insert_parent($ignore = false) {
		return parent::insert($ignore);
	}

	/**
	 * Insert the given data
	 *
	 * @param string $tmp_file
	 *   the path to the temp file or MainFileObj::MOVE_FILE (optional, default = "")
	 * @param boolean $ignore
	 *   Don't throw an error if data is already there (optional, default=false)
	 *
	 * @return boolean true on success, else false
	 */
	public function insert($tmp_file = "", $ignore = false) {
		$this->transaction_auto_begin();

		//Set the file object type, other objects which extends this file object should override the constance
		if (empty($this->values['type'])) {
			$this->values['type'] = self::TYPE;
		}

		//If the temp file was provided, generate a free path for the current file
		if (!empty($tmp_file)) {
			$this->get_free_path();
		}

		//Save the database values
		if (parent::insert()) {

			//If we provide a tmp_file we must move it to the new location
			if (!empty($tmp_file) && ($this->mkdir() && move_uploaded_file($tmp_file, $this->get_path()))) {
				$this->transaction_auto_commit();
				return true;
			}

			//All went fine if at this point we did not provide a tmp file
			if (empty($tmp_file)) {
				$this->transaction_auto_commit();
				return true;
			}
			$this->transaction_auto_rollback();
		}
		return false;
	}

	/**
	 * Download this file
	 *
	 * @param string $override_src_file
	 *   here we can setup a absolute file path which file we want to download (optional, default = "")
	 */
	public function download($override_src_file = "") {
		if (!empty($override_src_file)) {
			$file = $override_src_file;
		}
		else {
			$file = $this->get_path();
		}

		//Set http headers
		header("Expires: ".gmdate("D, d M Y H:i:s", time() + (60 * 60 * 24))." GMT+1");
		header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT+1");
		header("Content-Length: ".filesize($file));

		if (!isset($this->plain_text_extensions[$this->extension])) {
			header("Content-Transfer-Encoding: binary");
			$content_type = "application/".$this->extension;

			$obj = new MimeTypeObj($this->extension);
			if ($obj->load_success()) {
				$content_type = $obj->mime_type;
			}
			header("Content-type: ".$content_type."\n");
			header("Content-Disposition: attachment; filename=\"".$this->filename."\";\n\n");
		}
		else {
			header("Content-type: " . $this->plain_text_extensions[$this->extension] . "\n");
		}
		readfile($file);
		die();
	}

	/**
	 * Returns the file content
	 *
	 * @return string the file contents
	 */
	public function get_contents() {
		return file_get_contents($this->get_path());
	}

	/**
	 * Make all needed directories to storing the file and check if the directory is valid
	 *
	 * @return boolean true if directory exist, else false
	 */
	public function mkdir() {
		if (preg_match("/(.*)\/[^\/]+$/is", $this->get_path(), $matches) && !is_dir($matches[1])) {
			mkdir($matches[1], 0777, true);
			return true;
		}
		return is_dir($matches[1]);
	}

	/**
	 * Setup our filename to a free one and return this string
	 * the filename of our model will be set directly
	 *
	 * @return string the free path
	 */
	public function get_free_path() {

		//Get the director for this file
		$path = dirname($this->get_path())."/";
		$i = 0;
		//Get the filename of the file
		$filename = $this->filename;

		//Loop as long as the file exists
		while (file_exists($path.$this->filename)) {

			//Increment
			$this->filename = $i++."-".$filename;
		}
		return $path.$this->filename;
	}

	/**
	 * Returns the current path to the file
	 *
	 * @param boolean $without_sitepath
	 *   wether we wont not to prepend the SITEPATH or not (optional, default false)
	 *
	 * @return string the path
	 */
	public function get_path($without_sitepath = false) {
		$path = "/uploads/".self::TYPE."/".$this->filename;
		if ($without_sitepath == false) {
			$path = SITEPATH.$path;
		}
		return $path;
	}

	/**
	 * Will return the extension for this filename without the dot.
	 *
	 * @return string the extension string
	 */
	public function get_extension() {
		$ext = '';
		if (preg_match("/\.([^\.]+)$/", $this->filename, $matches)) {
			$ext = $matches[1];
		}
		return $ext;
	}

	/**
	 *
	 * @param boolean $leave_on_server
	 *   if set to true the file will only be deleted from database, the file it self will leave on the server (optional, default = false)
	 *
	 * @return boolean true on success, else false
	 */
	public function delete($leave_on_server = false) {
		if ($leave_on_server == false) {
			@unlink($this->old_file_path);
			@unlink($this->get_path());
		}
		return parent::delete();
	}

	/**
	 * Initialize the mime types
	 */
	protected function init_mime_types() {
		$this->plain_text_extensions = array(
			/*"jpg" => true,
			"jpeg" => true,
			"gif" => true,
			"png" => true,*/
			"txt" => true,
		);
	}

}

?>