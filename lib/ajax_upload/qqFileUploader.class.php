<?php
/**
 * Provide the main Uploadhandler for ajax uploads
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com> (Main author Andrew Valums http://http://valums.com)
 * @package lib.ajax_upload
 */
class qqFileUploader extends Object
{
	/**
	 * Define constances
	 */
	const STATUS_CANCELLED = 1;
	const STATUS_TO_BIG = 2;
	const STATUS_NOT_WRITEABLE = 3;
	const STATUS_NO_FILES = 4;
	const STATUS_EMPTY_FILES = 5;
	const STATUS_INVALID_EXTENSION = 6;
	const STATUS_SQL_ERROR = 7;
	const STATUS_MAX_SIZE_TO_BIG = 8;

	/**
	 * The allowed file extensions
	 *
	 * @var array
	 */
	private $allowed_extensions = array();

	/**
	 * The maximum file size in bytes
	 *
	 * @var int
	 */
	private $size_limit = 52428800; //50MB //10485760; 10MB  2097152; //2MB

	/**
	 * The Fileupload object, if on ajax mode its qqUploadedFileXhr or normal upload form it is qqUploadedFileForm
	 *
	 * @var mixed
	 */
	private $file;

	/**
	 * The created file id
	 * @var int
	 */
	private $created_fid = 0;

	/**
	 * Construct
	 * @param array $allowed_extensions The allowed file extensions (optional, default = array())
	 * @param int $size_limit The maximum file size in bytes (optional, default = 2097152)
	 */
	function __construct(array $allowed_extensions = array(), $size_limit = 2097152) {
		parent::__construct();
		$allowed_extensions = array_map("strtolower", $allowed_extensions);
		$this->allowed_extensions = $allowed_extensions;
		$this->size_limit = $size_limit;
	}

	/**
	 * Returns the created file id
	 *
	 * @return int the created fileid
	 */
	public function get_fid() {
		return $this->created_fid;
	}

	/**
	 * Handles the upload, initialize the file upload object
	 *
	 * @param MainFileObj $destination the complete destination file
	 * @param boolean $replace_old_file if set to true, existing file will be overriden, if false then a counting number will be appended to destination file (optional, default = false)
	 * @return int return the status code on of qqFileUploader::STATUS_* on error, on success return Core::GLOBEL_RETURN_CODE_SUCCESS
	 */
	public function handle_upload(MainFileObj $destination, $replace_old_file = false) {
		//Get the php ini configuration for post max size
		$post_size = $this->to_bytes(ini_get('post_max_size'));
		//Get the php ini configuration for upload max size
		$upload_size = $this->to_bytes(ini_get('upload_max_filesize'));

		//Check if the configured size limit is less than the post or upload max size configuration within the php ini
		if ($post_size < $this->size_limit || $upload_size < $this->size_limit) {
			$size = max(1, $this->size_limit / 1024 / 1024).'M';
			return self::STATUS_MAX_SIZE_TO_BIG;
		}

		/**
		 * Check if we have a get qqFile if so we are on ajax mode,
		 * if not and the qqfile is present within _FILES array we have normal post formular
		 * if this is also not there we have an error
		 */
		if (isset($_GET['qqfile'])) {
			$this->file = new qqUploadedFileXhr();
		}
		elseif (isset($_FILES['qqfile'])) {
			$this->file = new qqUploadedFileForm();
		}
		else {
			$this->file = false;
		}

		if (!$this->file) {
			return self::STATUS_NO_FILES;
		}

		//If we have not previous setup the owner of the file try to get the current logged in user
		if (empty($destination->owner)) {
			$destination->owner = $this->session->current_user()->username;
		}

		//Get information about the filename
		$pathinfo = pathinfo($this->file->get_name());

		//Setup the filename
		$destination->filename = $pathinfo['filename'];

		//Get the file extension
		$ext = "";
		if (isset($pathinfo['extension'])) {
			$destination->filename .= ".".$pathinfo['extension'];
			$ext = $pathinfo['extension'];
		}

		//If the file extension is not allowed, abort
		if ($this->allowed_extensions && !in_array(strtolower($ext), $this->allowed_extensions)) {
			return self::STATUS_INVALID_EXTENSION;
		}

		//Get the destination path where the file will be saved, this includes also the filename
		$destination_file = $destination->get_path();

		//Get the upload directory based up on the destination file
		$upload_directory = dirname($destination_file);

		//Create all needed directories to store the file
		$destination->mkdir();

		//If the upload directory is not writeable or does not exist, return an error
		if (!is_writable($upload_directory)) {
			return self::STATUS_NOT_WRITEABLE;
		}

		//Get the file size
		$size = $this->file->get_size();

		//If the filesize is empty we have not uploaded it correctly
		if ($size == 0) {
			return self::STATUS_EMPTY_FILES;
		}

		//Return an error if the file size exceed the size limit
		if ($size > $this->size_limit) {
			return self::STATUS_TO_BIG;
		}

		/**
		 * Normaly if the destination already exists it would override the old one
		 * If we do not want it to override, determine a free destination path
		 */
		if (!$replace_old_file) {
			/// don't overwrite previous files that were uploaded
			$destination->get_free_path();
		}

		//Get the maybe corrected path (get_free_path)
		$path = $destination->get_path();

		//If we could save the file to the destination path we setup our file object and insert it to the database
		if ($this->file->save($path)) {
			$destination->mimetype = mime_content_type($path);
			$destination->size = $size;
			if ($destination->insert()) {
				$this->created_fid = $destination->fid;
				return Core::GLOBEL_RETURN_CODE_SUCCESS;
			}
			return self::STATUS_SQL_ERROR;
		}
		else {
			return self::STATUS_CANCELLED;
		}
	}

	/**
	 * Converts a string like 100k to a real integer as bytes
	 *
	 * @param string $str the size string
	 * @return int the converted bytes
	 */
	private function to_bytes($str) {
		$val = trim($str);
		$last = strtolower($str[strlen($str) - 1]);
		switch ($last) {
			case 'g': $val *= 1024;
			case 'm': $val *= 1024;
			case 'k': $val *= 1024;
		}
		return $val;
	}

}

?>