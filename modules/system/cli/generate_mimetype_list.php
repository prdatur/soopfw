<?php

/**
 * Provide cli commando (clifs) to re-generate the mime type file
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package cli
 */
class cli_generate_mimetype_list extends CLICommand
{
	/**
	 * Overrides CLICommand::description
	 * The description for help
	 *
	 * @var string
	 */
	protected $description = "Create or update the mime type info file. this must not be executed to often..";

	/**
	 * Execute the command
	 *
	 * @return boolean return true if no errors occured, else false
	 */
	public function execute() {
		console_log('Try to generate mime type list, if you run this for the first time or you have not enabled memcached, this can take a while.', Core::MESSAGE_TYPE_NOTICE);
		$source_list = 'http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types';
		$data = file_get_contents($source_list);
		if (empty($data)) {
			console_log('Could not fetch mime type list using src: ' . $source_list, 'error');
		}
		$list = array();
		foreach (explode("\n", $data) AS $line) {
			if (preg_match("/^\s*([^#][^\s]+)\s+([^\s][^\n]+)/", $line, $matches)) {
				foreach (explode(' ', $matches[2]) AS $ext) {
					$ext = trim($ext);
					if (empty($ext)) {
						continue;
					}
					$list[$ext] = $matches[1];
				}

			}
		}
		if (!empty($list)) {
			ksort($list);
			$data = '<?php
$this->mime_types = ' . preg_replace('/  ([^\'\s]+) => /', '  \'\\1\' => ', var_export($list, true)) . '
?>';
			if (file_put_contents(SITEPATH . '/config/mime_types.php', $data) !== false) {
				foreach ($list AS $ext => $mime) {
					$mime_obj = new MimeTypeObj();
					$mime_obj->extension = $ext;
					$mime_obj->mime_type = $mime;
					$mime_obj->insert();
				}
				return true;
			}
			else {
				console_log('Could not write mime type file to: ' . SITEPATH . '/config/mime.types please check write permissions', 'error');
			}
		}
		else {
			console_log('Could not find any mime type within content, please check the url: ' . $source_list . ' if it is a valid mime type list of apache', 'error');
		}
		return false;
	}

	/**
	 * Overrides CLICommand::on_success
	 * callback for on_success
	 */
	public function on_success() {
		console_log('Mimetype list generated', 'ok');
	}

}

