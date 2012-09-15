<?php

/**
 * Provides a class to handle errors
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Tools
 */
class SoopfwErrorHandler
{
	/**
	 * Replace the standard php error handler ( this is a callback functions)
	 *
	 * @param int $number
	 *   the error number
	 * @param string $message
	 *   The error message
	 * @param string $file
	 *   the file where there error appeared
	 * @param string $line
	 *   the line where the appeared
	 * @param string $variables
	 *   the variables
	 *
	 * @return string the error string.
	 */
	public static function cc_error_handler($errno = E_NOTICE, $errstr = "", $errfile = "", $errline = "", $variables = "") {
		$core = Core::get_instance(false, Core::RUN_MODE_DEVELOPEMENT, null);

		// if error has been supressed with an @
		if (error_reporting() == 0 || (($errno & error_reporting()) == 0 )) {
			return;
		}

		static $firstErr = true;

		// check if function has been called by an exception
		if (func_num_args() == 1) {
			// caught exception
			$exc = func_get_arg(0);
			$errno = $exc->getCode();
			$errstr = $exc->getMessage();
			$errfile = $exc->getFile();
			$errline = $exc->getLine();

			$backtrace = $exc->getTrace();
		}
		else {
			// called by trigger_error()
			$exception = null;

			$backtrace = debug_backtrace();
			unset($backtrace[0]);
			$backtrace = array_reverse($backtrace);
		}

		$errorType = array(
			E_ERROR => 'ERROR',
			E_WARNING => 'WARNING',
			E_PARSE => 'PARSING ERROR',
			E_NOTICE => 'NOTICE',
			E_CORE_ERROR => 'CORE ERROR',
			E_CORE_WARNING => 'CORE WARNING',
			E_COMPILE_ERROR => 'COMPILE ERROR',
			E_COMPILE_WARNING => 'COMPILE WARNING',
			E_USER_ERROR => 'USER ERROR',
			E_USER_WARNING => 'USER WARNING',
			E_USER_NOTICE => 'USER NOTICE',
			E_STRICT => 'STRICT NOTICE',
			E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR'
		);

		// create error message
		if (array_key_exists($errno, $errorType)) {
			$err = $errorType[$errno];
		}
		else {
			$err = 'CAUGHT EXCEPTION (' . $errno . ')';
		}

		$errMsg = $errMsgPlain = "";

		if (empty($_SERVER['HTTP_HOST']))
			$_SERVER['HTTP_HOST'] = '';
		if (empty($_SERVER['REQUEST_URI']))
			$_SERVER['REQUEST_URI'] = '(unknown)';
		if (empty($_SERVER["HTTP_REFERER"]))
			$_SERVER['HTTP_REFERER'] = '(unknown)';
		if (empty($_SERVER["HTTP_USER_AGENT"]))
			$_SERVER['HTTP_USER_AGENT'] = '(unknown)';

		$current_user = "";
		if (!empty($core) && !empty($core->session)) {
			$current_user = $core->session->current_user();
		}
		if (empty($current_user)) {
			$user = '(not logged in)';
		}
		else {
			$user = $current_user->user_id;
		}

		if ($firstErr) {

			$errMsg = "
		<div style=\"width:70%;margin:0px auto;font-family:Arial;font-size:14px;border:1px solid black;\">
			<div style=\"background-color:#6699CC;font-weight:bold;color:white;padding:5px;text-align:left;\">User Infos</div>
			<div style=\"background-color:#D2DDF2;padding:5px;text-align:left;\">
<pre style=\"width:100%; overflow:auto;\">User................: " . htmlspecialchars($user) . "
User................: " . htmlspecialchars($user) . "
Request URI.........: " . htmlspecialchars($_SERVER['HTTP_HOST']) . htmlspecialchars($_SERVER['REQUEST_URI']) . "
Referer.............: " . htmlspecialchars($_SERVER['HTTP_REFERER']) . "
User's Browser......: " . htmlspecialchars($_SERVER['HTTP_USER_AGENT']) . "</pre></div>
		</div>
		<br />
		";


			$errMsgPlain = "
User................: " . htmlspecialchars($user) . "
Request URI.........: " . htmlspecialchars($_SERVER['HTTP_HOST']) . htmlspecialchars($_SERVER['REQUEST_URI']) . "
Referer.............: " . htmlspecialchars($_SERVER['HTTP_REFERER']) . "
User's Browser......: " . htmlspecialchars($_SERVER['HTTP_USER_AGENT']);
		}

		$bgcolor = "#FFCC33";
		$color = "black";
		switch ($errno) {
			case E_ERROR:
			case E_USER_ERROR: $bgcolor = '#C43E3E';
				$color = 'white';
				break;
		}

		$errMsg .= "<br>
		<div style=\"width:70%;margin:0px auto;font-family:Arial;font-size:14px;border:1px solid black;\">
			<div style=\"background-color:" . $bgcolor . ";font-weight:bold;color:" . $color . ";padding:5px;text-align:left;\">" . $err . ": " . $errstr . " in " . $errfile . " on line " . $errline . "</div>
			<div style=\"background-color:#D2DDF2;padding:5px;text-align:left;\">
			<pre style=\"width:100%; overflow:auto;\">";

		$lastfile = '';
		$errMsg .= "<b>\$_GET:</b><br>";
		$errMsg .= var_export($_GET, true);
		$errMsg .= "<br><br><b>\$_POST:</b><br>";
		$errMsg .= var_export($_POST, true);
		$errMsg .= "<br><br><b>\$_COOKIE:</b><br>";
		$errMsg .= var_export($_COOKIE, true);
		$errMsg .= "<br><br><b>Backtrace:</b><br>";
		$errMsgPlain.= "Error: " . $err . ": " . $errstr . " in " . $errfile . " on line " . $errline . "\n";

		$errMsgPlain .= "Backtrace:\n";
		foreach ($backtrace as $row) {
			if (empty($row['file']))
				$row['file'] = '(unknown)';
			if (empty($row['line']))
				$row['line'] = '(unknown)';
			if ($lastfile != $row['file']) {
				$errMsg .= "File: <b>" . $row['file'] . "</b><br>";
				$errMsgPlain .= "File: " . $row['file'] . "\n";
			}

			$display_args = true;

			$lastfile = $row['file'];
			$errMsg .= "<b>  Line: " . $row['line'] . ": </b>";
			$errMsgPlain .= "  Line: " . $row['line'] . ": \n";
			if (!empty($row['class'])) {
				if (strtolower($row['class']) == 'db' && (strtolower($row['function']) == '__construct' || strtolower($row['function']) == 'add_server')) {
					$display_args = false;
				}
				$errMsg .= $row['class'] . $row['type'] . $row['function'];
				$errMsgPlain .= $row['class'] . $row['type'] . $row['function'];
			}
			else {
				if (preg_match("/mysql_.?connect/i", $row['function'])) {
					$display_args = false;
				}
				$errMsg .= $row['function'];
				$errMsgPlain .= $row['function'];
			}
			if (empty($row['args'])) {
				$errMsg .= ' (no args)';
				$errMsgPlain .= ' (no args)';
			}
			else {
				$errMsg .= ' (Args: ';
				$errMsgPlain .= ' (Args: ';
				$separator = '';
				foreach ($row['args'] as $arg) {

					if ($display_args === true) {
						$value = self::getArgument($arg);
					}
					else {
						$value = '***';
					}

					$errMsg .= htmlspecialchars($separator . $value);
					$errMsgPlain .= $separator . $value;
					$separator = ', ';
				}
				$errMsg .= ')';
				$errMsgPlain .= ')';
			}
			$errMsg .= "\n\n";
			$errMsgPlain .= "\n";
		}

		$errMsg .= "</pre></div></div>";
		if (ini_get("display_errors")) {
			if (defined('is_shell')) {
				echo $errMsgPlain;
			}
			else {
				echo $errMsg;
			}
		}

		if (ini_get('log_errors')) {
			error_log($errMsgPlain);
		}


		$firstErr = false;
		switch ($errno) {
			case E_ERROR:
			case E_USER_ERROR:

				exit(1);
				break;
		}
		return true;
	}

	/**
	 * Parses a value to a string which is more preciser than var_export.
	 *
	 * @param mixed $arg
	 *   the value
	 *
	 * @return string the formated argument
	 */
	public static function getArgument($arg) {
		switch (strtolower(gettype($arg))) {
			case 'string':
				return( '"' . str_replace(array("\n"), array(''), $arg) . '"' );

			case 'boolean':
				return (bool) $arg;

			case 'object':
				return 'object(' . get_class($arg) . ')';

			case 'array':
				$ret = 'array(';
				$separtor = '';

				foreach ($arg as $k => $v) {
					#$ret .= $separtor . self::getArgument($k) . ' => ' . self::getArgument($v);
					$separtor = ', ';
				}
				$ret .= ')';

				return $ret;

			case 'resource':
				return 'resource(' . get_resource_type($arg) . ')';

			default:
				return var_export($arg, true);
		}
	}

}

?>