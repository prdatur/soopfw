<?php

/**
 * Provide the ajax upload handling
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com> (Main author Andrew Valums http://http://valums.com)
 */
class qqUploadedFileXhr
{

	/**
	 * Save the file to the specified path
	 *
	 * @param string $path, the destination path
	 * @return boolean true on success, else false
	 */
	function save($path) {
		$input = fopen("php://input", "r");
		$temp = tmpfile();
		$real_size = stream_copy_to_stream($input, $temp);
		fclose($input);

		if ($real_size != $this->get_size()) {
			return false;
		}

		$target = fopen($path, "w");
		fseek($temp, 0, SEEK_SET);
		stream_copy_to_stream($temp, $target);
		fclose($target);

		return true;
	}

	/**
	 * Returns the uploaded file name
	 *
	 * @return string
	 */
	function get_name() {
		return $_GET['qqfile'];
	}

	/**
	 * Returns the uploaded file size
	 *
	 * @throws Exception
	 * @return int
	 */
	function get_size() {
		if (isset($_SERVER["CONTENT_LENGTH"])) {
			return (int)$_SERVER["CONTENT_LENGTH"];
		}
		else {
			throw new Exception('Getting content length is not supported.');
		}
	}

}

