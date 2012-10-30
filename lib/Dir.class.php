<?php

/**
 * Provide a class to search after directories or files, the hole object
 * can be used within a foreach loop thanks to implementing an iterator
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Tools
 */
class Dir implements Iterator
{

	/**
	 * The starting path
	 * @var string
	 */
	private $path = "";

	/**
	 * Wether to search recrusive or not
	 * @var boolean
	 */
	private $recrusive = true;

	/**
	 * An array which holds regular expression to skip specified directories
	 * @var array
	 */
	private $skip_dirs = array();

	/**
	 * An array which holds regular expression to skip specified files
	 * @var array
	 */
	private $skip_files = array();

	/**
	 * An array which holds regular expression to skip a directory which not match the expresssion
	 * @var array
	 */
	private $incl_dirs = array();

	/**
	 * An array which holds regular expression to skip a file which not match the expresssion
	 * @var array
	 */
	private $incl_files = array();

	/**
	 * Determines if we want just directories within output
	 * @var boolean
	 */
	private $just_dirs = false;

	/**
	 * Determines if we want just files within output
	 * @var boolean
	 */
	private $just_files = false;

	/**
	 * Wether we must start the search process or use the previous found array
	 * @var boolean
	 */
	private $init = true;

	/**
	 * An integer which will be used for imeplementing the iterator
	 * @var int
	 */
	private $key = 0;

	/**
	 * The found files
	 * @var array
	 */
	private $files = array();

	/**
	 * This string will be used as the prefix for a directory search
	 * @var string
	 */
	private $chroot = SITEPATH;

	/**
	 * Scans a directory for files or folders
	 *
	 * @param string $path 
	 *   the directory to scan, this is the directory under chroot
	 * @param boolean $recrusive 
	 *   Set to false if you do not want recrusion (optional, default = true)
	 * @param string $chroot 
	 *   This is the main directory, the scan goes not below this dir (optional, default = SITEPATH)
	 */
 	public function __construct($path, $recrusive = true, $chroot = SITEPATH) {

		//Replace all possibles to get under the chroot directory (security issue)
		$path = preg_replace("/\.\.+\//is", "", $path);
		$this->path = $path;
		$this->recrusive = $recrusive;

		//Pre init some usefull filter for security reasons
		$this->skip_regexp("^\.");
		$this->skip_dirs_regexp('(^\.+|\/\.+)');
		$this->chroot = $chroot;
	}

	/**
	 * Include file extension
	 * Apply a file extension filter, just plaintext string
	 *
	 * @param string $ext 
	 *   the extension
	 */
	public function file_extension($ext) {
		$this->incl_files[] = "/.*\.".preg_quote($ext, "/")."$/iUs";
	}

	/**
	 * Include file name
	 * Apply a filename filter, just plaintext string
	 * this is just the filename without extension, a file extension is needed
	 * to match this filter
	 *
	 * @param string $filename 
	 *   the filename
	 */
	public function file_name($filename) {
		$this->incl_files[] = "/^".preg_quote($filename, "/")."\.[a-z]+$/iUs";
	}

	/**
	 * Include file
	 * Apply a file  filter, just plaintext string
	 * This is the same as file_name but here the file extension is not need
	 *
	 * @param string $filename 
	 *   the filename
	 */
	public function file($filename) {
		$this->incl_files[] = "/^".preg_quote($filename, "/")."$/iUs";
	}

	/**
	 * Include the file expression
	 * Apply a file filter pure regexp allowed
	 *
	 * @param string $regexp 
	 *   the regular expression
	 */
	public function file_regexp($regexp) {
		$this->incl_files[] = "/".$regexp."/iUs";
	}

	/**
	 * Skip file extension
	 * Apply a file extension filter, just plaintext string
	 *
	 * @param string $ext 
	 *   the extension
	 */
	public function skip_extensions($ext) {
		$this->skip_files[] = "/.*\.".preg_quote($ext, "/")."$/iUs";
	}

	/**
	 * Skip file name
	 * Apply a filename filter, just plaintext string
	 * this is just the filename without extension, a file extension is needed
	 * to match this filter
	 *
	 * @param string $filename 
	 *   the filename
	 */
	public function skip_filename($filename) {
		$this->skip_files[] = "/^".preg_quote($filename, "/")."\.[a-z]+$/iUs";
	}

	/**
	 * Skip file
	 * Apply a file  filter, just plaintext string
	 * This is the same as skip_filename but here the file extension is not need
	 *
	 * @param string $filename 
	 *   the filename
	 */
	public function skip($filename) {
		$this->skip_files[] = "/^".preg_quote($filename, "/")."$/iUs";
	}

	/**
	 * Skip the file expression
	 * Apply a file filter pure regexp allowed
	 *
	 * @param string $regexp 
	 *   the regular expression
	 */
	public function skip_regexp($regexp) {
		$this->skip_files[] = "/".$regexp."/iUs";
	}

	/**
	 * Skip a directory
	 * Apply a directoriy filter, just plaintext string
	 *
	 * @param string $dir 
	 *   the directory
	 */
	public function skip_dirs($dir) {
		$this->skip_dirs[] = "/^".preg_quote($dir, "/")."/iUs";
	}

	/**
	 * Skip a directory
	 * Apply a directoriy filter pure regexp allowed
	 *
	 * @param string $regexp 
	 *   the directory as a regular expression
	 */
	public function skip_dirs_regexp($regexp) {
		$this->skip_dirs[] = "/".$regexp."/iUs";
	}

	/**
	 * Include a directory
	 * Apply a directoriy filter pure regexp allowed
	 *
	 * @param string $regexp 
	 *   the directory as a regular expression
	 */
	public function dir_regexp($regexp) {
		$this->incl_dirs[] = "/".$regexp."/iUs";
	}

	/**
	 * Set that we want only files within returning output
	 *
	 * @param boolean $bool 
	 *   set to true if your want only files within output (optional, default = true)
	 */
	public function just_files($bool = true) {
		if ($bool !== true) {
			$bool = false;
		}
		$this->just_files = $bool;
	}

	/**
	 * Set that we want only directories within returning output
	 *
	 * @param boolean $bool 
	 *   set to true if your want only directories within output (optional, default = true)
	 */
	public function just_dirs($bool = true) {
		if ($bool !== true) {
			$bool = false;
		}
		$this->just_dirs = $bool;
	}

	/**
	 * Start searching
	 *
	 * @return array the returning result array
	 */
	public function search() {
		$this->valid();
		return $this->files;
	}

	/**
	 * Check if we have a valid result array, this is also the first function
	 * which is called when the iterator starts, so we need to include here our
	 * primary search engine
	 *
	 * @return boolean true if valid, else false
	 */
	public function valid() {
		if ($this->init == true) { //Fresh start, we must init the opendir handle
			$this->files = $this->readdir($this->path);
			$this->rewind();
			$this->init = false;
		}
		return ($this->key < count($this->files));
	}

	/**
	 * Main search engine
	 *
	 * @param string $dir 
	 *   the dir where we want to start search
	 * 
	 * @return array the result array
	 */
	private function readdir($dir) {
		//If we have no directories or the provided one is not a directory return an empty array
		$chk_dir = str_replace('//', '/', $this->chroot."/".$dir);
		$chk_dir = preg_replace("/\/+$/", "", $chk_dir );
		if (empty($dir) || !is_dir($chk_dir)) {
			return array();
		}
		$entries = array();
		
		//Loop through all current entries within the directory
		if ($handle = opendir($chk_dir)) {
			while (false !== ($entry = readdir($handle))) {

				$check_dir = "";
				if($dir != $this->path) {
					$check_dir .= $dir.'/';
				}
				$check_dir .= $entry;
				
				//If the directory should not handle by a skip / include directory or the handle is not a ressource, skip this entry
				if (empty($handle) || $this->validate_dir($check_dir) == false) {
					continue;
				}
				//Setup the current full path for the entry
				$path = preg_replace("/\/\/+/is", "/", $chk_dir."/".$entry);
				//Check if the validation of the file succeed.
				if ($this->validate($entry, $dir) !== false) {
					//Add the current entry to the return result
					$tmp_class = new DirEntry();
					$tmp_class->directory = $dir;
					$tmp_class->path = $path;
					$tmp_class->last_modified = filemtime($path);
					$tmp_class->last_access = fileatime($path);
					$tmp_class->created = filectime($path);
					$tmp_class->filename = $entry;
					$tmp_class->is_file = is_file($path);
					$tmp_class->is_dir = (($tmp_class->is_file == true) ? false : true);
					$tmp_class->size = filesize($path);
					if (preg_match("/\.([a-z]+)$/iUs", $entry, $match)) {
						$tmp_class->ext = $match[1];
					}
					$entries[] = $tmp_class;
					unset($tmp_class);
				}
				//If we want recursion and the current entry is a directory, call readdir recrusive with the current entry
				if ($this->recrusive && is_dir($path)) {
					$entries = array_merge($entries, $this->readdir(preg_replace("/\/\/+/is", "/", $dir."/".$entry)));
				}
			}
			return $entries;
		}
		return $entries;
	}

	/**
	 * Validates a directory against the filters
	 *
	 * @param string $entry 
	 *   the directory
	 * 
	 * @return boolean true if directory is valid, else false
	 */
	public function validate_dir($entry) {

		//Check all skip_dirs filter, if one match skip this directory
		foreach ($this->skip_dirs as $regexp) {
			if (preg_match($regexp, $entry)) {
				return false;
			}
		}

		//Check all incl_dirs filter, if one does not match skip this entry
		foreach ($this->incl_dirs as $regexp) {
			if (!preg_match($regexp, $entry)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Validates a file through all file filters
	 *
	 * @param string $entry 
	 *   the entry name
	 * @param string $dir 
	 *   the complete path to the entry but without chroot
	 * 
	 * @return boolean true if valid, else false
	 */
	public function validate($entry, $dir) {

		//If the entry is empty return false
		if (empty($entry)) {
			return false;
		}

		//If we do only files and the entry is not a file retur nfalse
		if ($this->just_files == true && !is_file($this->chroot."/".$dir."/".$entry)) {
			return false;
		}

		//If we only want directories and the one provided is not a directory return false
		if ($this->just_dirs == true && !is_dir($this->chroot."/".$dir."/".$entry)) {
			return false;
		}

		//Check all incl_files filter against the entry, if one does not match skip this file
		foreach ($this->incl_files as $regexp) {
			if (!preg_match($regexp, $dir."/".$entry)) {
				return false;
			}
		}

		//Check all skip_files filter against the entry, if one does match skip this file
		foreach ($this->skip_files as $regexp) {
			if (preg_match($regexp, $dir."/".$entry)) {
				return false;
			}
		}
		return $entry;
	}

	/**
	 * Implements iterator key()
	 * 
	 * @return int the key
	 */
	public function key() {
		return $this->key;
	}

	/**
	 * Implements iterator current()
	 * 
	 * @return mixed the current element
	 */
	public function current() {
		return $this->files[$this->key];
	}

	/**
	 * Implements iterator rewind()
	 */
	public function rewind() {
		$this->key = 0;
	}

	/**
	 * Implements iterator next()
	 */
	public function next() {
		$this->key++;
	}

}

/**
 * This object will be the returning elements which includes all needed information about a file
 */
class DirEntry
{

	/**
	 * the filename without the extension
	 *
	 * @var string
	 */
	public $filename = "";

	/**
	 * the directory without the filename like dirname()
	 *
	 * @var string
	 */
	public $directory = "";

	/**
	 * The full path to the file (including filename)
	 *
	 * @var string
	 */
	public $path = "";

	/**
	 * If the entry is a file
	 *
	 * @var boolean
	 */
	public $is_file = false;

	/**
	 * If the entry is a directory
	 *
	 * @var boolean
	 */
	public $is_dir = false;

	/**
	 * The size of the entry in bytes
	 *
	 * @var int
	 */
	public $size = "";

	/**
	 * The file extension
	 * @var string
	 */
	public $ext = "";

	/**
	 * The last access time as a timestamp
	 *
	 * @var int
	 */
	public $last_access = 0;

	/**
	 * The last modified time as a timestamp
	 *
	 * @var int
	 */
	public $last_modified = 0;

	/**
	 * The created time as a timestamp
	 *
	 * @var int
	 */
	public $created = 0;

	/**
	 * Returns the complete file path.
	 * 
	 * @return string the complete file path 
	 */
	function __toString() {
		return $this->path;
	}

}

