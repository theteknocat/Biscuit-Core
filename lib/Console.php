<?php
/**
 * A simple object class for setting up custom PHP error console logging for use with the framework. A custom log file can be defined in the system settings database, otherwise a default will be chosen. It also includes a standalone error handling function with a method for setting it as the main PHP error handler.
 *
 * @package Core
 * @author Peter Epp
 **/
class Console {
	/**
	 * Set the error handler function that PHP should use
	 *
	 * @return void
	 * @author Peter Epp
	 **/
	function set_err_handler() {
		// Set the error handler:
		return set_error_handler("error_handler");
	}
	/**
	 * Set the level of error reporting based on the system global LOGGING_LEVEL
	 *
	 * @return void
	 * @author Peter Epp
	 **/
	function set_err_level() {
		if (LOGGING_LEVEL > 3) {
			// Log EVERYTHING
			error_reporting(E_ERROR | E_WARNING | E_NOTICE | E_PARSE | E_STRICT | E_DEPRECATED);
		} else if (LOGGING_LEVEL == 3) {
			// Skip strict standards and deprecated
			error_reporting(E_ERROR | E_WARNING | E_NOTICE | E_PARSE);
		} else if (LOGGING_LEVEL == 2) {
			// Notices but no warnings
			error_reporting(E_ERROR | E_NOTICE | E_PARSE);
		} else {
			// Log errors only
			error_reporting(E_ERROR);
		}
	}
	/**
	 * Log any specified message to the error log file, but only when the logging level is greater than 2 (test or developer level) or the $force_log override is true.
	 *
	 * @return void
	 * @author Peter Epp
	 * @param $message string The message to put in the log file
	 * @param $force_log bool Optional - Whether or not to override the DETAILED_LOGGING option
	 **/
	function log($message,$force_log = false) {
		if (LOGGING_LEVEL >= 2 || $force_log) {
			// Log an error message
			if (is_array($message) || is_object($message)) {
				$message = print_r($message,true);
			}
			if (empty($message)) {
				$message = '--NOTICE - The message supplied for logging was empty.';
			}
			$message = $message."\n";
			$log_file = Console::log_file();
			$log_file_size = filesize($log_file);
			if ($log_file_size >= 2097152) {
				// Never let the log file exceed 2MB
				@unlink($log_file);
			}
			error_log($message,3,$log_file);
		}
	}
	/**
	 * Create a log folder in the site root if it does not exist, and return the full path of the log file
	 *
	 * @return string The log file path
	 * @author Peter Epp
	 */
	function log_file() {
		$log_path = SITE_ROOT."/log";
		if (!file_exists($log_path)) {
			mkdir($log_path);
			chmod($log_path,0774);	// Full access for both user and group, read-only for everyone else
		}
		return $log_path."/console_log";
	}
	/**
	 * Log a message and force output the 500 Internal Server Error HTTP status header. This function has not yet been tested.
	 *
	 * @return void
	 * @author Peter Epp
	 * @param $message string Message to store in log
	 **/
	function throw_500($message = '') {
		// Throw a 500 internal server error and log an error message
		Console::log("<-- Internal Server Error Triggered! -->",true);
		Console::log("Date/Time: ".date("r"),true);
		if (!empty($message)) {
			Console::log("Error Message:",true);
			Console::log($message,true);
		}
		$report_sent = false;
		if (SERVER_TYPE == "PRODUCTION" || DEBUG) {
			$backtrace = Console::formatted_backtrace();
			if (SERVER_TYPE != 'LOCAL_DEV') {
				$message_body = "Internal Server Error Triggered on: ".date("r")."

Requested Page: ".Request::uri()."
Referred from:  ".Request::referer()."

Error Message: ".((!empty($message)) ? $message : "None supplied.")."

Debug Backtrace:
				".$backtrace;
				$report_sent = Console::send_error_report($message_body,"Critical Error Occurred!");
			}
		}
		$show_debug_info = (DEBUG && SERVER_TYPE != "PRODUCTION");
		$error_info = array(
			'message'       => $message,
			'is_debug_mode' => $show_debug_info,
			'backtrace'     => (($show_debug_info) ? $backtrace : ''),
			'report_sent'   => $report_sent
		);
		if (defined("TECH_EMAIL")) {
			$error_info['contact_email'] = TECH_EMAIL;
		}
		if (Request::is_ajax() || Request::is_ajaxy_iframe_post()) {
			Session::set('error_info',$error_info);
			echo '<script language="javascript" type="text/javascript">top.location.href="/error500";</script>';
		}
		else {
			include("views/error500.php");
		}
		Biscuit::end_program();
	}
	/**
	 * Get and format backtrace for debugging
	 *
	 * @return string
	 * @author Peter Epp
	 */
	function formatted_backtrace() {
		$backtrace = debug_backtrace();
		// Bump the first 3 elements of the backtrace array because they are always this method, the throw_500 method and the the trigger_error function
		array_shift($backtrace);
		array_shift($backtrace);
		array_shift($backtrace);
		// Now walk through, pull out and format the backtrace data we want:
		$formatted_backtrace = '';
		foreach ($backtrace as $index => $data) {
			if (!empty($data['file'])) {
				$formatted_backtrace .= 'File: '.$data['file']."\n";
			}
			if (!empty($data['line'])) {
				$formatted_backtrace .= '-- Line: '.$data['line']."\n";
			}
			$function = '';
			if (!empty($data['class']) && !empty($data['type'])) {
				$function = $data['class'].$data['type'];
			}
			$function .= $data['function'].'()';
			$formatted_backtrace .= '-- Function: '.$function."\n";
		}
		return $formatted_backtrace;
	}
	/**
	 * Email an error report
	 *
	 * @param string $message Message body text
	 * @param string $subject Message subject text
	 * @return bool Success
	 * @author Peter Epp
	 */
	function send_error_report($message,$subject = "Error Report") {
		if (defined("TECH_EMAIL")) {
			$recipient = TECH_EMAIL;
		}
		else {
			Console::log("Error report could not be sent because no tech email address is defined.");
			return false;
		}
		$from_email = "noreply@".Request::header("Host");
		$from_name = Request::header("Host")." Web Server";
		$options = array(
			"To"          => $recipient,
			"From"        => $from_email,
			"FromName"    => $from_name,
			"Subject"     => $subject,
			"Priority"    => 1
		);
		$message_vars = array(
			'message_body'    => $message
		);
		$mail = new Mailer();
		$result = $mail->send_mail("error_report",$options,$message_vars);
		if (!$result) {
			Console::log("Error report failed to send: ".$result);
			return false;
		}
		else {
			Console::log("An error report was successfully sent to ".$recipient);
			return true;
		}
	}
	/**
	 * Render debug output
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function render_debug_info() {
		if (DEBUG && SERVER_TYPE != "PRODUCTION") {
			include('views/debug_output.php');
		}
	}
}
/**
 * Function to handle PHP errors instead of the default error handler. It uses the Console object above to log errors in a friendly format. Parameters are the ones normally passed by the PHP's error handler.
 *
 * @return void
 * @author Peter Epp
 **/
function error_handler($errno,$errstr,$errfile,$errline,$errcontext) {
	if (!defined("E_STRICT")) {
		define("E_STRICT",2048);
	}
	switch($errno) {
		case E_USER_NOTICE:
			$error_prefix = 		"     USER NOTICE     ";
			break;
		case E_NOTICE:
			$error_prefix = 		"        NOTICE       ";
			break;
		case E_USER_WARNING:
			$error_prefix = 		"     USER WARNING    ";
			break;
		case E_WARNING:
			$error_prefix = 		"       WARNING       ";
			break;
		case E_STRICT:
		case 2048:
				$error_prefix = 	"  STRICT STANDARDS   ";
				break;
		case E_PARSE:
			$error_prefix = 		"     PARSE ERROR     ";
			break;
		case E_CORE_WARNING;
			$error_prefix = 		"     CORE WARNING    ";
			break;
		case E_CORE_ERROR:
			$error_prefix = 		"      CORE_ERROR     ";
			break;
		case E_USER_ERROR:
			Console::throw_500($errstr);
			break;
		case E_ERROR:
		default:
			$error_prefix = 		"      ERROR #".$errno."    ";
			break;
	}
	$is_notice = ($errno == 2048 || $errno == E_STRICT || $errno == E_NOTICE);
	// Filter out warnings and notices from lagging unless logging level is 4
	$allow_notice = ($is_notice && LOGGING_LEVEL > 3);
	if (!$is_notice || $allow_notice) {
		$errstr = trim($errstr);
		$error_msg =	"<!!--".$error_prefix."--!!>\n";
		$error_msg .=	"    Request:    ".Request::uri()."\n";
		$error_msg .=	"    Message:    ".$errstr."\n";
		$error_msg .=	"    File:       ".$errfile."\n";
		$error_msg .=	"    Line:       ".$errline."\n";
		$error_msg .=	"    When:       ".date("r")."\n";
		$error_msg .=	"    Error Code: #".$errno."\n";
		$error_msg .=	"<!!-------------------------!!>";
		Console::log($error_msg,true);
	}
}
?>