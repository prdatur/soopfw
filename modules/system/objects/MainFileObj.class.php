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
	 * All binary mime types
	 * @var array
	 */
	private $mime_types = array();

	/**
	 * All plain text mime types
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
		$this->values['type'] = self::TYPE;

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
			if (isset($this->mime_types[$this->extension])) {
				$content_type = $this->mime_types[$this->extension];
			}
			header("Content-type: ".$content_type."\n");
			header("Content-Disposition: attachment; filename=\"".$this->filename."\";\n\n");
		}
		else {
			header("Content-type: ".$this->mimetype."\n");
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
			"jpg" => true,
			"jpeg" => true,
			"gif" => true,
			"png" => true,
			"txt" => true,
		);

		$this->mime_types = array(
			"ez" => "application/andrew-inset",
			"hqx" => "application/mac-binhex40",
			"cpt" => "application/mac-compactpro",
			"doc" => "application/msword",
			"bin" => "application/octet-stream",
			"dms" => "application/octet-stream",
			"lha" => "application/octet-stream",
			"lzh" => "application/octet-stream",
			"exe" => "application/octet-stream",
			"class" => "application/octet-stream",
			"so" => "application/octet-stream",
			"dll" => "application/octet-stream",
			"oda" => "application/oda",
			"pdf" => "application/pdf",
			"ai" => "application/postscript",
			"eps" => "application/postscript",
			"ps" => "application/postscript",
			"smi" => "application/smil",
			"smil" => "application/smil",
			"wbxml" => "application/vnd.wap.wbxml",
			"wmlc" => "application/vnd.wap.wmlc",
			"wmlsc" => "application/vnd.wap.wmlscriptc",
			"bcpio" => "application/x-bcpio",
			"vcd" => "application/x-cdlink",
			"pgn" => "application/x-chess-pgn",
			"cpio" => "application/x-cpio",
			"csh" => "application/x-csh",
			"dcr" => "application/x-director",
			"dir" => "application/x-director",
			"dxr" => "application/x-director",
			"dvi" => "application/x-dvi",
			"spl" => "application/x-futuresplash",
			"gtar" => "application/x-gtar",
			"hdf" => "application/x-hdf",
			"js" => "application/x-javascript",
			"skp" => "application/x-koan",
			"skd" => "application/x-koan",
			"skt" => "application/x-koan",
			"skm" => "application/x-koan",
			"latex" => "application/x-latex",
			"nc" => "application/x-netcdf",
			"cdf" => "application/x-netcdf",
			"sh" => "application/x-sh",
			"shar" => "application/x-shar",
			"swf" => "application/x-shockwave-flash",
			"sit" => "application/x-stuffit",
			"tar" => "application/x-tar",
			"tcl" => "application/x-tcl",
			"tex" => "application/x-tex",
			"texi" => "application/x-texinfo",
			"t" => "application/x-troff",
			"tr" => "application/x-troff",
			"roff" => "application/x-troff",
			"man" => "application/x-troff-man",
			"me" => "application/x-troff-me",
			"ms" => "application/x-troff-ms",
			"ustar" => "application/x-ustar",
			"src" => "application/x-wais-source",
			"xht" => "application/xhtml+xml",
			"zip" => "application/zip",
			"au" => "audio/basic",
			"snd" => "audio/basic",
			"mid" => "audio/midi",
			"midi" => "audio/midi",
			"kar" => "audio/midi",
			"mpga" => "audio/mpeg",
			"mp2" => "audio/mpeg",
			"mp3" => "audio/mpeg",
			"aif" => "audio/x-aiff",
			"aiff" => "audio/x-aiff",
			"aifc" => "audio/x-aiff",
			"m3u" => "audio/x-mpegurl",
			"ram" => "audio/x-pn-realaudio",
			"rm" => "audio/x-pn-realaudio",
			"rpm" => "audio/x-pn-realaudio-plugin",
			"ra" => "audio/x-realaudio",
			"wav" => "audio/x-wav",
			"pdb" => "chemical/x-pdb",
			"xyz" => "chemical/x-xyz",
			"bmp" => "image/bmp",
			"gif" => "image/gif",
			"ief" => "image/ief",
			"jpeg" => "image/jpeg",
			"jpg" => "image/jpeg",
			"jpe" => "image/jpeg",
			"png" => "image/png",
			"tiff" => "image/tiff",
			"tif" => "image/tif",
			"djvu" => "image/vnd.djvu",
			"djv" => "image/vnd.djvu",
			"wbmp" => "image/vnd.wap.wbmp",
			"ras" => "image/x-cmu-raster",
			"pnm" => "image/x-portable-anymap",
			"pbm" => "image/x-portable-bitmap",
			"pgm" => "image/x-portable-graymap",
			"ppm" => "image/x-portable-pixmap",
			"rgb" => "image/x-rgb",
			"xbm" => "image/x-xbitmap",
			"xpm" => "image/x-xpixmap",
			"xwd" => "image/x-windowdump",
			"igs" => "model/iges",
			"iges" => "model/iges",
			"msh" => "model/mesh",
			"mesh" => "model/mesh",
			"silo" => "model/mesh",
			"wrl" => "model/vrml",
			"vrml" => "model/vrml",
			"css" => "text/css",
			"html" => "text/html",
			"htm" => "text/html",
			"asc" => "text/plain",
			"txt" => "text/plain",
			"rtx" => "text/richtext",
			"rtf" => "text/rtf",
			"sgml" => "text/sgml",
			"sgm" => "text/sgml",
			"tsv" => "text/tab-seperated-values",
			"wml" => "text/vnd.wap.wml",
			"wmls" => "text/vnd.wap.wmlscript",
			"etx" => "text/x-setext",
			"xml" => "text/xml",
			"xsl" => "text/xml",
			"mpeg" => "video/mpeg",
			"mpg" => "video/mpeg",
			"mpe" => "video/mpeg",
			"qt" => "video/quicktime",
			"mov" => "video/quicktime",
			"mxu" => "video/vnd.mpegurl",
			"avi" => "video/x-msvideo",
			"movie" => "video/x-sgi-movie",
			"ice" => "x-conference-xcooltalk",
			"sv4cpio" => "application/x-sv4cpio",
			"sv4crc" => "application/x-sv4crc",
			"texinfo" => "application/x-texinfo",
			"xhtml" => "application/xhtml+xml"
		);
	}

}

?>