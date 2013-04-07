<?php
require_once('biscuit-core/vendor/FirePHPCore/fb.php');
/**
 * A simple object class for setting up custom PHP error console logging for use with the framework. A custom log file can be defined in the system settings database, otherwise a default will be chosen. It also includes a standalone error handling function with a method for setting it as the main PHP error handler.
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: console.php 14682 2012-06-18 19:00:50Z teknocat $
 **/
class Console {
	/**
	 * Constant with value of true to use in arguments when logging for semantic purposes
	 */
	const FORCE_LOG = true;
	/**
	 * List of error type names indexed by error code
	 *
	 * @var array
	 */
	private static $_error_types = array(
		E_USER_NOTICE     => 'Notice',
		E_NOTICE          => 'Notice',
		E_USER_WARNING    => 'Warning',
		E_WARNING         => 'Warning',
		E_STRICT          => 'Strict Standards',
		E_USER_DEPRECATED => 'Deprecated',
		E_DEPRECATED      => 'Deprecated',
		E_PARSE           => 'Parse Error',
		E_CORE_WARNING    => 'Core Warning',
		E_CORE_ERROR      => 'Core Error',
		E_USER_ERROR      => 'Error',
		E_ERROR           => 'Error'
	);
	/**
	 * Regular expression for parsing request markers in log files
	 *
	 * @author Peter Epp
	 */
	private static $_log_marker_regex = '/<\!--- REQUEST\: ([^\|]+)\|([0-9]+) ---!>/';

	private function __construct() {
		// Prevent instantiation
	}
	/**
	 * Set the error handler function that PHP should use, and return it's value for cases where the previous error handler may need to be restored again.
	 *
	 * @return void
	 * @author Peter Epp
	 **/
	public static function set_err_handler() {
		set_exception_handler(array('Console','exception_handler'));
		return set_error_handler(array('Console','error_handler'));
	}
	/**
	 * Set the level of error reporting based on the system global LOGGING_LEVEL
	 *
	 * @return void
	 * @author Peter Epp
	 **/
	public static function set_err_level() {
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
	 * Log a start of new request marker in all log files for log parsing
	 *
	 * @param string $request_uri 
	 * @param string $type 
	 * @return void
	 * @author Peter Epp
	 */
	public static function log_request_markers($request_uri) {
		if (preg_match('/system-admin\/log_(viewer|delete)/', Request::uri())) {
			return;
		}
		self::rotate_logs();
		$message = "<!--- REQUEST: ".$request_uri."|".time()." ---!>";
		self::log($message,false,'console',true);
		self::log_error($message,false,true);
		self::log_event($message,false,true);
		self::log_query($message,false,true);
		self::log($message,false,'var_dump',true);
	}
	/**
	 * Delete log files that are over 2MB in size
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function rotate_logs() {
		$log_types = array('console', 'error', 'event', 'query', 'var_dump');
		foreach ($log_types as $log_type) {
			$log_file = self::log_file($log_type);
			if (file_exists($log_file)) {
				$log_file_size = filesize($log_file);
				if ($log_file_size >= 2097152) {
					// Never let the log file exceed 2MB
					@unlink($log_file);
				}
			}
		}
	}
	/**
	 * Shortcut for logging to the error log
	 *
	 * @param string $message 
	 * @param string $force_log 
	 * @return void
	 * @author Peter Epp
	 */
	public static function log_error($message,$force_log = false,$skip_fb_log = false) {
		if (preg_match('/system-admin\/log_(viewer|delete)/', Request::uri())) {
			return;
		}
		self::log($message,$force_log,"error",$skip_fb_log);
	}
	/**
	 * Shortcut for logging to the event log
	 *
	 * @param string $message 
	 * @param string $force_log 
	 * @return void
	 * @author Peter Epp
	 */
	public static function log_event($message,$force_log = false,$skip_fb_log = false) {
		if (preg_match('/system-admin\/log_(viewer|delete)/', Request::uri())) {
			return;
		}
		self::log($message,$force_log,"event",$skip_fb_log);
	}
	/**
	 * Shortcut for logging to the database query log
	 *
	 * @param string $message 
	 * @param string $force_log 
	 * @return void
	 * @author Peter Epp
	 */
	public static function log_query($message,$force_log = false,$skip_fb_log = false) {
		if (preg_match('/system-admin\/log_(viewer|delete)/', Request::uri())) {
			return;
		}
		self::log($message,$force_log,"query",$skip_fb_log);
	}
	/**
	 * Shortcut for logging to the variable dump log
	 *
	 * @param string $message 
	 * @param string $force_log 
	 * @return void
	 * @author Peter Epp
	 */
	public static function log_var_dump($description,$variable,$force_log = false) {
		if (preg_match('/system-admin\/log_(viewer|delete)/', Request::uri())) {
			return;
		}
		$backtrace = debug_backtrace();
		$called_by = $backtrace[1]['class'].$backtrace[1]['type'].$backtrace[1]['function'];	// Who called the log function
		// Compose the log message in CSV format:
		$message = '"'.$called_by.'","'.addslashes($description).'","'.addslashes(serialize($variable)).'"';
		self::fb_log('var_dump',$description,$variable);
		self::log($message,$force_log,"var_dump");
	}
	/**
	 * Log any specified message to the error log file, but only when the logging level is greater than 2 (test or developer level) or the $force_log override is true.
	 *
	 * @return void
	 * @author Peter Epp
	 * @param $message string The message to put in the log file
	 * @param $force_log bool Optional - Whether or not to override the DETAILED_LOGGING option
	 **/
	public static function log($message,$force_log = false,$type = "console",$skip_fb_log = false) {
		if (preg_match('/system-admin\/log_(viewer|delete)/', Request::uri())) {
			return;
		}
		$skip_logging = (defined("SKIP_LOGGING") && SKIP_LOGGING === true);
		$logging_level = ((defined('LOGGING_LEVEL')) ? LOGGING_LEVEL : 1);
		if (Bootstrap::is_browser_run_level() && !$skip_logging && ($logging_level >= 2 || $force_log)) {
			// Log an error message
			if (is_array($message) || is_object($message)) {
				// Force var dump log
				$backtrace = debug_backtrace();
				$called_by = $backtrace[1]['class'].$backtrace[1]['type'].$backtrace[1]['function'];	// Who called the log function
				$message = '"'.$called_by.'","Unknown","'.addslashes(serialize($message)).'"';
				$type = 'var_dump';
				self::fb_log('var_dump',"Unknown",$message);
			}
			if (empty($message)) {
				$message = '--NOTICE - The message supplied for logging was empty.';
			}
			$message = $message."\n";
			if (!$skip_fb_log && $type != "var_dump") {
				self::fb_log($type,$message);
			}
			$log_file = Console::log_file($type);
			if (is_writable(SITE_ROOT.'/var/log')) {
				error_log($message,3,$log_file);
			} else {
				error_log($message);
			}
		}
	}
	/**
	 * Log to Firebug with FirePHP
	 *
	 * @param string $type Log type
	 * @param string $message Log content
	 * @param string $extra Mainly just for FB::dump
	 * @return void
	 * @author Peter Epp
	 */
	public static function fb_log($type,$message,$extra = null) {
		if (Request::is_ajax() && DEBUG && SERVER_TYPE != "PRODUCTION" && !Response::headers_sent()) {
			switch ($type) {
				case "console":
					FB::log($message,"Message");
					break;
				case "event":
				case "query":
					FB::log($message,ucwords($type));
					break;
				case "error":
					FB::error($message,"Error");
					break;
				case "var_dump":
					FB::dump($message,$extra);
					break;
			}
		}
	}
	/**
	 * Create a log folder in the site root if it does not exist, and return the full path of the log file
	 *
	 * @return string The log file path
	 * @author Peter Epp
	 */
	public static function log_file($type = "console") {
		$log_path = SITE_ROOT."/var/log";
		return $log_path."/".$type."_log";
	}
	/**
	 * Dump out the raw contents of a log file
	 *
	 * @param string $log_type "console" or "error"
	 * @return string Contents of log file
	 * @author Peter Epp
	 */
	public static function log_dump($log_type) {
		if (DEBUG && SERVER_TYPE == "LOCAL_DEV") {	// Only output log if debugging mode enabled AND this is the local development machine.
			$log_file = SITE_ROOT."/var/log/".$log_type."_log";
			if (!file_exists($log_file)) {
				return "<pre>Log is empty.</pre>";
			} else {
				return '<pre>'.file_get_contents($log_file).'</pre>';
			}
		} else if (DEBUG && SERVER_TYPE == "PRODUCTION") {
			return '<pre>Cannot output log contents for security reasons.</pre>';
		}
		return '';
	}
	/**
	 * Parse the contents of a log file and return as an associative array by request marker
	 *
	 * @param string $type 
	 * @return array
	 * @author Peter Epp
	 */
	public static function parse_log_file($type) {
		$log_file = SITE_ROOT.'/var/log/'.$type."_log";
		if (!file_exists($log_file)) {
			return array();
		} else {
			// Fetch log contents:
			$log_contents = file_get_contents($log_file);
			// Split array on new lines:
			$contents_array = preg_split('/[\n\r]/',$log_contents);
			// Filter out empty elements:
			$contents_array = array_filter($contents_array);
			// Reorganize contents array into associative array by request marker:
			$contents_by_request = array();
			$current_marker = '';
			foreach ($contents_array as $value) {
				if (preg_match(self::$_log_marker_regex,$value) && $current_marker != $value) {
					$current_marker = $value;
					$contents_by_request[$current_marker] = array();
				}
				if ($value != $current_marker) {
					$contents_by_request[$current_marker][] = self::parse_log_entry($value,$type);
				}
			}
			// Reverse order so newest log entries are at the top
			$contents_by_request = array_reverse($contents_by_request);
			return $contents_by_request;
		}
	}
	/**
	 * Parse an individual log line item and return content formatted according to the log type
	 *
	 * @param string $value 
	 * @param string $type 
	 * @return void
	 * @author Peter Epp
	 */
	private static function parse_log_entry($value,$type) {
		switch ($type) {
			case "error":
			case "event":
			case "query":
			case "var_dump":
				$values = Crumbs::csv_explode($value);
				foreach ($values as $index => $item_value) {
					$item_value = stripslashes($item_value);
					if ($index == 2 && $type == 'var_dump') {
						$values[$index] = unserialize($item_value);
					} else {
						$values[$index] = $item_value;
					}
				}
				return $values;
				break;
			case "console":
				return $value;
				break;
		}
	}
	/**
	 * Porse a log request marker and return the request URI and date in an array
	 *
	 * @param string $marker 
	 * @return array
	 * @author Peter Epp
	 */
	public static function parse_log_marker($marker) {
		preg_match(self::$_log_marker_regex,$marker,$matches);
		return array(
			'request_uri' => $matches[1],
			'timestamp'   => $matches[2]
		);
	}
	/**
	 * Delete a log file
	 *
	 * @param string $log_type "console" or "error"
	 * @return void
	 * @author Peter Epp
	 */
	public static function log_delete($log_type) {
		if (DEBUG && SERVER_TYPE == "LOCAL_DEV") {	// Only do it if in debug mode and on local dev machine
			$log_file = SITE_ROOT."/var/log/".$log_type."_log";
			if (file_exists($log_file)) {
				unlink($log_file);
			}
		}
	}
	/**
	 * Log a message and force output the 500 Internal Server Error HTTP status header. This function has not yet been tested.
	 *
	 * @return void
	 * @author Peter Epp
	 * @param $message string Message to store in log
	 **/
	public static function throw_500($error_info) {
		$config = Configuration::instance();
		// Throw a 500 internal server error and log an error message
		$report_sent = false;
		if (empty($error_info['backtrace'])) {
			$backtrace = self::formatted_backtrace();
		} else {
			$backtrace = self::formatted_backtrace($error_info['backtrace']);
		}
		if (!$config->has_critical_errors() && (SERVER_TYPE == "PRODUCTION" || (DEBUG && SERVER_TYPE != 'LOCAL_DEV'))) {
			$auth_data = Session::get('auth_data');
			if (empty($auth_data) || empty($auth_data['username'])) {
				$username = 'Guest (nobody logged in)';
			} else {
				$username = $auth_data['username'];
			}
			$report_data = array(
				'error_file'    => $error_info['errfile'],
				'error_line'    => $error_info['errline'],
				'error_date'    => date("r"),
				'full_url'      => 'http://'.Request::host().Request::uri(),
				'username'      => $username,
				'error_message' => ((!empty($error_info['errstr'])) ? $error_info['errstr'] : "None supplied."),
				'backtrace'     => $backtrace
			);
			$report_sent = Console::send_error_report($report_data);
		}
		$show_debug_info = ($config->has_critical_errors() || (DEBUG && SERVER_TYPE != "PRODUCTION"));
		$html_message = <<<HTML
<p><strong>File:</strong> {$error_info['errfile']}<br><strong>Line:</strong> {$error_info['errline']}</p>
{$error_info['errstr']}
HTML;
		$error_output = array(
			'message'       => $html_message,
			'is_debug_mode' => $show_debug_info,
			'backtrace'     => (($show_debug_info) ? print_r($backtrace,true) : ''),
			'report_sent'   => $report_sent
		);
		if (defined("TECH_EMAIL")) {
			$error_output['contact_email'] = TECH_EMAIL;
		}
		if (Request::is_ajax() || Request::is_ajaxy_iframe_post() || Response::headers_sent()) {
			Session::set('error_output',$error_output);
			if (!Response::headers_sent()) {
				Response::send_headers();
			}
			// TODO: have it popup a nice Javascript alert instead of redirecting
			echo '<script language="javascript" type="text/javascript">top.location.href="/error500";</script>';
		}
		else {
			Response::http_status(500);
			Response::send_headers();
			include('views/error500.php');
		}
		Bootstrap::end_program();
	}
	/**
	 * Get and format backtrace for debugging
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public static function formatted_backtrace($backtrace = null) {
		if (empty($backtrace)) {
			$backtrace = debug_backtrace();
			// Bump the first 3 elements of the backtrace array because they are always this method, the throw500 method and the the trigger_error method
			array_shift($backtrace);
			array_shift($backtrace);
			array_shift($backtrace);
		}
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
	public static function send_error_report($report_data) {
		$subject = "Application Error on ".Request::host();
		if (defined("TECH_EMAIL")) {
			$recipient = TECH_EMAIL;
		}
		else {
			Console::log_error("Error report could not be sent because no tech email address is defined.",true);
			return false;
		}
		$from_email = "noreply@".Request::header("Host");
		$from_name = Request::header("Host")." Web Server";
		$options = array(
			"To"          => $recipient,
			"From"        => $from_email,
			"FromName"    => $from_name,
			"Subject"     => $subject,
			"Priority"    => 1,
			"use_html"    => true
		);
		$mail = new Mailer();
		$result = $mail->send_mail("views/email/error_report",$options,$report_data);
		if (!$result) {
			Console::log("Error report failed to send: ".$result);
			return false;
		}
		else {
			Console::log("Error report sent to ".$recipient);
			return true;
		}
	}
	/**
	 * Handle all uncaught exceptions
	 *
	 * @param object $e PHP Exception object instance
	 * @return void
	 * @author Peter Epp
	 */
	public static function exception_handler($e) {
		$exception_class = get_class($e);
		if ($exception_class == 'Exception') {
			$message = "Uncaught Exception: ".$e->getMessage();
		} else {
			$exception_name = ucwords(AkInflector::humanize(AkInflector::underscore($exception_class)));
			$message = $exception_name.": ".$e->getMessage();
		}
		$error_info = array(
			'errno'      => $e->getCode(),
			'errstr'     => $message,
			'errfile'    => $e->getFile(),
			'errline'    => $e->getLine(),
			'backtrace'  => $e->getTrace()
		);
		Console::throw_500($error_info);
	}
	/**
	 * Handle PHP errors
	 *
	 * @return void
	 * @author Peter Epp
	 **/
	public static function error_handler($errno,$errstr,$errfile,$errline,$errcontext) {
		$is_notice = ($errno == E_DEPRECATED || $errno == E_STRICT || $errno == E_NOTICE || $errno == E_USER_NOTICE);
		// Filter out warnings and notices from lagging unless logging level is 4
		$allow_notice = ($is_notice && defined("LOGGING_LEVEL") && LOGGING_LEVEL > 3);
		if (!$is_notice || $allow_notice) {
			$errstr = trim($errstr);
			// Strip SITE_ROOT from error file path:
			$errfile = substr($errfile,strlen(SITE_ROOT));
			// Compile CSV string for log
			if (array_key_exists($errno,self::$_error_types)) {
				$error_type = self::$_error_types[$errno].' ('.$errno.')';
			} else {
				$error_type = 'Unknown ('.$errno.')';
			}
			$erroro_type =
			$error_msg = '"'.$error_type.'","'.addslashes($errstr).'","'.$errfile.'","'.$errline.'"';
			self::log_error($error_msg,true);
		}
		if ($errno == E_ERROR || $errno == E_USER_ERROR) {
			$error_info = array(
				'errno'      => $errno,
				'errstr'     => $errstr,
				'errfile'    => $errfile,
				'errline'    => $errline,
				'errcontext' => $errcontext
			);
			Console::throw_500($error_info);
		}
	}
}
?>