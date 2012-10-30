<?php

/**
 * Provide the default $_FILES form upload
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com> (Main author Andrew Valums http://http://valums.com)
 */
class qqUploadedFileForm
{

	/**
	 * Save the file to the specified path
	 * @param string $path, the destination path
	 * @return boolean true on success, else false
	 */
	function save($path) {
		if (!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)) {
			return false;
		}
		return true;
	}

	/**
	 * Returns the uploaded file name
	 * @return string
	 */
	function get_name() {
		return $_FILES['qqfile']['name'];
	}

	/**
	 * Returns the uploaded file size
	 * @return int
	 */
	function get_size() {
		return $_FILES['qqfile']['size'];
	}

}

