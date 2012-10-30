<?php

/**
 * Provides a class to help command line apps
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Cli
 */
class CliHelper
{

	/**
	 * Display the log message
	 *
	 * By default, only warnings and errors will be displayed, if 'verbose' is specified, it will also display notices.
	 *
	 * @param string $message
	 * @param string $type
	 * @return
	 *   False in case of an error or failed type, True in all other cases.
	 */
	public static function console_log($message, $type='notice') {
		$red = "\033[31;40m\033[1m[%s]\033[0m";
		$yellow = "\033[1;33;40m\033[1m[%s]\033[0m";
		$green = "\033[1;32;40m\033[1m[%s]\033[0m";

		$verbose = true;

		$return = TRUE;
		switch ($type) {
			case 'plain':
				$type_msg = "";
				break;
			case 'warning' :
			case 'cancel' :
				$type_msg = sprintf($yellow, $type);
				break;
			case Core::MESSAGE_TYPE_ERROR:
			case 'failed' :
				$type_msg = sprintf($red, $type);
				$return = FALSE;
				break;
			case Core::MESSAGE_TYPE_SUCCESS:
			case 'ok' :
			case 'completed' :
			case 'status':
				$type_msg = sprintf($green, $type);
				break;
			case Core::MESSAGE_TYPE_NOTICE:
			case 'notice' :
			case 'message' :
			case 'info' :
				if (!$verbose) {
					// print nothing. exit cleanly.
					return TRUE;
				}
				$type_msg = sprintf($yellow, $type);
				break;
			default :
				return TRUE;
				break;
		}

		$columns = 120;

		$width[1] = 11;

		$width[0] = ($columns - 11);

		$format = sprintf("%%-%ds%%%ds", $width[0], $width[1]);

		if (!is_scalar($message)) {
			$message = print_r($message, true);
		}
		// Place the status message right aligned with the top line of the error message.
		$message = wordwrap(rtrim($message), $width[0]);
		$lines = explode("\n", $message);
		$lines[0] = sprintf($format, $lines[0], $type_msg);
		$message = implode("\n", $lines) . "\n";

		// try to log to file
		if (defined('console_log_file')) {
			if (!is_file(console_log_file)) {
				$touch_status = @touch(console_log_file);
				if (!$touch_status) {
					trigger_error('Can not create file for loggin: ' . console_log_file, E_USER_ERROR);
				}
				elseif (!is_writable(console_log_file)) {
					trigger_error('Loggin file isn ot writeable: ' . console_log_file, E_USER_ERROR);
				}
				else {
					file_put_contents(console_log_file, $message, FILE_APPEND);
				}
			}
		}

		// if we run on cli, echo
		if (empty($_SERVER['HTTP_HOST'])) {
			$handle = STDERR;
			if (isset($handle)) {
				fwrite($handle, $message);
			}
			else {
				print $message;
			}
		}

		return $return;
	}

	/**
	 * Reads a string from the command line.
	 *
	 * @param string $question
	 * 	 the message to show in front of the waiting input line
	 * @return string
	 * 	 The entered string
	 */
	public static function get_line($question) {
		echo $question;
		return trim(fgets(STDIN));
	}

	/**
	 * Reads the user input and validate it against a "yes" input.
	 *
	 * The following values will be return true:
	 * 1,y,yes,z,zes,j,ja,true
	 *
	 * @param string $question
	 * 	 the message to show in front of the waiting input line
	 * @param string $default
	 *   the default value which will be used if the user just press enter
	 * @return boolean
	 * 	 return true if the user entered a "true" (yes,y,j,ja,true,1)
	 *   special behaviour on z,zes for keyboard layout english/german
	 */
	public static function get_boolean_input($question, $default = false) {
		$line = strtolower(self::get_line($question . ' [' . (($default) ? 'y' : 'n') . ']:'));
		if (empty($line)) {
			$line = $default;
		}
		switch ($line) {
			case '1':
			case 'y':
			case 'yes':
			case 'z':
			case 'zes':
			case 'j':
			case 'ja':
			case 'true':
				return true;
		}
		return false;
	}

	/**
	 * Reads the user input and returns the value
	 *
	 * @param string $question
	 * 	 the message to show in front of the waiting input line
	 * @param string $default
	 *   the default value which will be used if the user just press enter
	 * @return string
	 * 	 returns the entered string, if left empty, default will returned
	 */
	public static function get_string_input($question, $default = '') {
		$line = self::get_line($question . ' [' . $default . ']:');
		if (empty($line)) {
			$line = $default;
		}
		return $line;
	}

	/**
	 * Returns the entered string but the input will not be visible to the user.
	 *
	 * @param string $prompt
	 *   The message to show
	 *
	 * @return string
	 * 	 The entered string
	 */
	public static function get_silent_string_input($prompt = "Enter Password:") {
		if (preg_match('/^win/i', PHP_OS)) {
			$vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
			file_put_contents(
					$vbscript, 'wscript.echo(InputBox("' . addslashes($prompt) . '", "", "password here"))');
			$command = "cscript //nologo " . escapeshellarg($vbscript);
			$password = rtrim(shell_exec($command));
			unlink($vbscript);
			return $password;
		}
		else {
			$command = "/usr/bin/env bash -c 'echo OK'";
			if (rtrim(shell_exec($command)) !== 'OK') {
				trigger_error("Can't invoke bash");
				return;
			}
			$command = "/usr/bin/env bash -c 'read -s -p \"" . addslashes($prompt) . "\" mypassword && echo \$mypassword'";
			$password = rtrim(shell_exec($command));
			echo "\n";
			return $password;
		}
	}

}

