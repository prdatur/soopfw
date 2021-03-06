<?php

/**
 * Provides a HTML-Filefield which can handle normal post and also ajax-post based fileuploads
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form.Inputs
 */
class Filefield extends AbstractHtmlInput
{
	/**
	 * Define constances
	 */
	const CONFIG_USE_AJAX = 'is_ajax';
	const CONFIG_ACTION = 'action';

	/**
	 * The original filename of uploaded file
	 *
	 * @var string
	 */
	private $file_name = "";

	/**
	 * The size of uploaded file in bytes
	 *
	 * @var int
	 */
	private $file_size = 0;

	/**
	 * The php temp path of uploaded file
	 *
	 * @var string
	 */
	private $file_tmpname = "";

	/**
	 * The mime type of uploaded file
	 *
	 * @var string
	 */
	private $file_mimetype = "";

	/**
	 * The file extension of uploaded file
	 *
	 * @var string
	 */
	private $file_extension = "";

	/**
	 * Determines if this filefield object will be uploaded via ajax or not
	 * Default: false
	 *
	 * @var boolean
	 */
	private $is_ajax = false;

	/**
	 * The max file size which can have an uploaded file
	 *
	 * @var int
	 */
	private $size_limit = 52428800;

	/**
	 * Determines which file extensions are allowed.
	 * empty array will allow all.
	 *
	 * @var array
	 */
	private $file_extensions = array();

	/**
	 * the file type
	 * @var string
	 */
	private $file_type = 'mainfile';

	/**
	 * Stores the current file object as a MainFileObj.
	 * This is set if we have already a value and could load a valid file.
	 *
	 * @var MainFileObj
	 */
	private $current_file = null;

	/**
	 * This url is used within an ajax request to post the file
	 * Note:
	 *   The ajax file must handle the file upload as within the example and default ajax upload file "/system/AjaxDefaultUpload.ajax"
	 *   Please look at this file how you must handle the file upload.
	 *   Also note that this action handles also the "delete" action within the file upload.
	 *   A delete request is a post to the action url with post param "fid" so every time a fid in post param is provided than we must delete the file
	 * Default: "/system/AjaxDefaultUpload.ajax"
	 *
	 * @var string The action url
	 */
	private $action = "/system/AjaxDefaultUpload.ajax";

	/**
	 * constructor
	 *
	 * @param string $name
	 *   the input name
	 * @param string $value
	 *   the input value
	 * @param string $label
	 *   the input label (optional, default='')
	 * @param string $description
	 *   the input description (optional, default = '')
	 * @param string $class
	 *   the input css class (optional, default = '')
	 * @param string $id
	 *   the input id (optional, default = '')
	 * @param boolean $handle_upload
	 *   whether this element handles the upload directly or not (optional, default = true)
	 */
 	public function __construct($name, $value = "", $label = "", $description = "", $class = "", $id = "", $handle_upload = true) {
		parent::__construct($name, $value, $label, $description, $class, $id);
		$this->config("id", (empty($id)) ? "form_id_".$name : $id);
		$this->config("handle_upload", $handle_upload);
		$this->config("default_file_id", $value);

		$this->size_limit($this->core->get_dbconfig('system', System::CONFIG_DEFAULT_UPLOAD_MAX_FILE_SIZE, $this->size_limit));
		$this->init();
	}

	/**
	 * Set the file type.
	 *
	 * @param string $file_type
	 *   the file type
	 */
	public function set_type($file_type) {
		$this->file_type = $file_type;
	}

	/**
	 * Set the file extensions.
	 *
	 * @param array $file_extensions
	 *   the allowed extensions
	 */
	public function file_extensions(array $file_extensions) {
		foreach($file_extensions AS $ext) {
			$this->file_extensions[$ext] = true;
		}
	}

	/**
	 * Set the allowed file extensions
	 *
	 * @param mixed $extensions
	 *   The allowed extensions, can be a single string value or an array holding all allowed values
	 *
	 * @deprecated since version 1.0 please referer to file_extensions()
	 */
	public function extensions($extensions) {
		if (!empty($extensions) && !is_array($extensions)) {
			$extensions = array($extensions);
		}
		$this->file_extensions($extensions);
	}

	/**
	 * Set the maximum size which can have a file
	 *
	 * @param int $size_limit
	 *   the size in bytes
	 */
	public function size_limit($size_limit) {
		$this->size_limit = (int)$size_limit;
	}

	/**
	 * Set the javascript-function name which will be called on a successfull upload
	 *
	 * @param string $on_complete_functions
	 *   the function name
	 */
	public function on_complete($on_complete_functions) {
		$this->config('on_complete', $on_complete_functions);
	}

	/**
	 * Whether to set this field to an ajax upload or not
	 *
	 * @param boolean $is_ajax
	 *   set this filefield to an ajax upload or not
	 */
	public function set_ajax($is_ajax) {
		$this->is_ajax = $is_ajax;
		if ($this->is_ajax) {
			//If we want an ajax filefield, we need to provide javascript the needed information
			//Also we need to disable this object to handle the upload it self.
			$css_classes = $this->config_array("css_class");
			if (empty($css_classes)) {
				$css_classes = array();
			}

			$pre_values = array();
			$current_fid = $this->config('value');
			if(!empty($current_fid)) {
				$file_obj = new MainFileObj($current_fid);
				if($file_obj->load_success()) {
					$pre_values = array(
						'file_name' => $file_obj->filename,
						'file_size' => $file_obj->size,
						'fid' => $current_fid
					);
				}
			}

			$this->core->js_config("system_ajax_file_uploads", array(
				'action' => $this->action,
				'id' => $this->config("id"),
				'name' => $this->config("name"),
				'extensions' => array_keys($this->file_extensions),
				'size_limit' => $this->size_limit,
				'css_class' => implode(" ", $css_classes),
				'on_complete' => $this->config('on_complete'),
				'pre_values' => $pre_values,
				'file_type' => $this->file_type,
				), true);
			$this->config("handle_upload", false);
			$this->init();
		}
		else {
			$this->config("handle_upload", true);
			$this->init();
		}
	}

	/**
	 * Get or set config values, we must override this for filefield couse we have additional special variables
	 *
	 * @param string $val
	 *   the key
	 * @param string $val
	 *   the value as a string, if not set, current value will be returned (optional, default = NS)
	 *
	 * @return mixed the value for the key as a string or if in set-mode return true, if you return a value which are not set, return false
	 */
	function config($k, $v = NS) {
		switch ($k) {
			case 'action':
				if ($v === NS) {
					return $this->action;
				}
				$this->action = $v;
				break;
			case 'is_ajax':
				if ($v === NS) {
					return $this->is_ajax;
				}
				$this->set_ajax($v);
				break;
			case 'size_limit':
				if ($v === NS) {
					return $this->size_limit;
				}
				$this->size_limit($v);
				break;
			default:
				return parent::config($k, $v);
		}
	}

	/**
	 * Returns the current file object.
	 *
	 * Will be null if no file could be loaded.
	 *
	 * @return MainFileObj The file object or null if it could not be loaded.
	 */
	public function get_current_file() {
		return $this->current_file;
	}
	/**
	 * Returns the HTML-Code string for the element
	 *
	 * @param boolean $include_label
	 *   If the label should be included within the output. (Optional, default = true)
	 * @param boolean $include_description
	 *   If the description should be included within the output. (Optional, default = true)
	 * 
	 * @return string 
	 *   the HTML code for the element
	 */
	public function fetch($include_label = true, $include_description = true) {
		if (!$this->is_ajax) {
			$current_fid = (int)$this->config('value');
			if (!empty($current_fid)) {
				$file = new MainFileObj($current_fid);
				if ($file->load_success()) {
					$this->current_file = $file;
					$suffix = '<span>';
					$suffix .= '<input type="hidden" name="' . $this->config('name') . '" value="' . $current_fid . '"/>';
					$suffix .= t('Current file: <b>@filename</b>', array('@filename' => $file->filename)) . ' - <a href="javascript:void(0);" class="filefield_delete_file">' . t('Delete') . '</a>';
					$suffix .= '<br /></span>';
					$this->config("suffix", $suffix);
				}
			}
		}
		//Get the normal html-string for the upload field
		$html = parent::fetch($include_label, $include_description);

		//If we are on the ajax mode we need to add the ajax file upload handler so javascript can work.
		if ($this->is_ajax) {
			//first the the label string if not empty
			$return = $this->get_label();
			//Provide a suffix which we can configurate
			$suffix = $this->config("suffix");
			if(!empty($suffix)) {
				$return .= $suffix;
			}

			$tmp_tpl = "<div id=\"file-uploader-".$this->config("id")."\" class=\"soopfw_ajax_file_uploader_handler\">
						<noscript> <p>Please enable JavaScript to use file uploader.</p> </noscript>
					</div>";

			//Append the main input template string and the followed description
			$return .= $tmp_tpl.$this->get_description();
			return $return;
		}

		return $html;
	}

	/**
	 * init the input
	 */
	public function init() {
		$this->config("template", "<input type=\"file\" {name}{value}{id}{class}{style}{other}/>");

		//Check if the file was uploaded, if so set our variables
		if (isset($_FILES[$this->config("name")]) && $_FILES[$this->config("name")]['error'] == 0) {
			$this->file_name = $_FILES[$this->config("name")]['name'];
			$this->file_size = $_FILES[$this->config("name")]['size'];
			$this->file_mimetype = $_FILES[$this->config("name")]['type'];
			$this->file_tmpname = $_FILES[$this->config("name")]['tmp_name'];

			if (preg_match("/.*\.([^.]+)$/", $this->file_name, $matches)) {
				$this->file_extension = $matches[1];
			}
		}

		//Store the file if we handle the upload and if the file was successfully uploaded
		if ($this->config("handle_upload") == true) {
			if (isset($_FILES[$this->config("name")]) && $_FILES[$this->config("name")]['error'] == 0) {
				$this->config("key_is_set", true);
				if (!empty($this->size_limit) && $this->file_size > $this->size_limit) {
					$this->core->message(t('The uploaded file is too big, max file size is @max_file_size', array('@max_file_size' => Converter::format_bytes($this->size_limit))));
				}
				else if(!empty($this->file_extensions) && !isset($this->file_extensions[$this->file_extension])) {
					$this->core->message(t('File extension "@ext" is not allowed. Allowed are: @allowed_exts', array(
						'@ext' => $this->file_extension,
						'@allowed_exts' => implode(", ", $this->file_extensions),
					)));
				}
				else {
					$main_file_obj = new MainFileObj();
					$main_file_obj->type = $this->file_type;

					//If we are logged in set the current user as the file owner
					if ($this->session->is_logged_in()) {
						$main_file_obj->owner = $this->session->current_user()->user_id;
					}

					$main_file_obj->filename = $this->file_name;
					$main_file_obj->size = $this->file_size;
					$main_file_obj->mimetype = $this->file_mimetype;

					//If we saved the file successfully provide the fileid as the element value
					if ($main_file_obj->save_or_insert($this->file_tmpname)) {

						$old_fid = (int)$this->config("default_file_id");
						if (!empty($old_fid)) {
							$file = new MainFileObj($old_fid);
							if ($file->load_success()) {
								$file->delete();
							}
						}
						$this->config("value", $main_file_obj->fid);
					}
				}
			}
		}
		else {
			//If we do not handle the storeing, we just do the normal value behaviour on post
			if (isset($_POST[$this->config("name")])) {
				$this->config("value", $_POST[$this->config("name")]);
			}
		}
	}

}