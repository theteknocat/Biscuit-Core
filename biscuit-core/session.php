<?php
/**
 * A set of functions for handling session data
 * 
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: session.php 14321 2011-09-30 17:37:07Z teknocat $
 */
class Session {
	private function __construct() {
		// Prevent instantiation
	}
	/**
	 * Start a new session
	 *
	 * @param $allow_db_sessions Optional - whether or not to allow database session storage, for example to disable it when testing
	 * @return void
	 * @author Peter Epp
	 */
	public static function start($allow_db_sessions = true) {
		session_cache_limiter('none');	// Disables automatic sending of Cache-Control, Pragma and Expires headers
		register_shutdown_function('session_write_close');
		if ($allow_db_sessions) {
			SessionStorage::init();
		}
		session_name(SESSION_NAME);
		if ($sessid = Request::form('session_id')) {
			self::set_id($sessid);
		}
		session_start();
		Console::log("        Session started with id: ".session_id());
		if (DEBUG) {
			$session_contents = Session::contents();
			Console::log_var_dump("Session contents",$session_contents);
		}
	}
	/**
	 * Explicitly set the session ID to a specific value
	 *
	 * @param string $sessid 
	 * @return void
	 * @author Peter Epp
	 */
	public static function set_id($sessid) {
		if (!empty($sessid)) {
			session_id($sessid);
		}
	}
	/**
	 * Return the current session ID
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public static function id() {
		return session_id();
	}
	/**
	 * Destroy the current session
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function destroy() {
		session_destroy();
	}
	/**
	 * Extend the current session expiry by the amount dictated by the current user's level. This function requires the Authenticator plugin.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function set_expiry() {
		Session::set('expires_at',Biscuit::instance()->ModuleAuthenticator()->session_expires_at());
	}
	/**
	 * Destroy and restart the session, with the optional feature of backing up the session first and then restoring it afterwards.
	 * The backup and restore functions are for when you want to clear a particular session variable and then restart the session for security reasons,
	 * but want to carry forward information from the old session to the new one.
	 *
	 * @return void
	 * @author Peter Epp
	 * @param $backup bool Optional - Whether or not to backup the session first
	 * @param $exceptions array Optional - An array of session variables you don't want to keep when backing up
	 **/
	public static function reset($backup = false,$backup_keepers = null) {
		if ($backup) {
			$session_backup = Session::backup($backup_keepers);
		}
		Session::destroy();
		Session::start();
		if ($backup && !empty($session_backup)) {
			Session::restore($session_backup);
		}
	}
	/**
	 * Write and close the session. When using database sessions, this function MUST be called
	 * whenever the script ends. Always call it manually before calling "exit".
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function close() {
		session_write_close();
	}
	/**
	 * Backup all session variables except those specified in $exceptions
	 *
	 * @return array The array of all the backed up session variables
	 * @author Peter Epp
	 * @param $keepers array List of session variables you want to backup
	 **/
	public static function backup($keepers = null) {
		if (is_array($keepers)) {
			$backup = array();
			$session_vars = Session::contents();
			foreach ($session_vars as $k => $v) {
				if (in_array($k,$keepers)) {
					$backup[$k] = $v;
				}
			}
			return $backup;
		}
		return false;
	}
	/**
	 * Restore session variables from backup
	 *
	 * @return void
	 * @author Peter Epp
	 * @param $backup array The array of backed up session variables returned by the flash_backup() function
	 **/
	public static function restore($backup) {
		foreach ($backup as $k => $v) {
			Session::set($k,$v);
		}
	}
	/**
	 * Returns the value of a given session variable
	 *
	 * @param string $index The array index of the value to get
	 * @return void
	 * @author Peter Epp
	 */
	public static function get($index) {
		if (isset($_SESSION[$index])) {
			return $_SESSION[$index];
		}
		return null;
	}
	/**
	 * Set a session variable
	 *
	 * @param string $index The array index of the variable to set
	 * @param string $value The value of the variable
	 * @return void
	 * @author Peter Epp
	 */
	public static function set($index,$value) {
		$_SESSION[$index] = $value;
	}
	/**
	 * Unset a session variable
	 *
	 * @param string $index The array index of the variable you want to kill
	 * @return void
	 * @author Peter Epp
	 */
	public static function unset_var($index) {
		if (isset($_SESSION) && isset($_SESSION[$index])) {
			unset($_SESSION[$index]);
		}
	}
	/**
	 * Determine if a session variable exists
	 *
	 * @param string $index The array index of the variable to test
	 * @return bool Whether or not the session variable is set
	 * @author Peter Epp
	 */
	
	public static function var_exists($index) {
		return (isset($_SESSION[$index]));
	}
	/**
	* Store a value in the session "flash" array with a specified index
	*
	* @return void
	* @author Peter Epp
	* @param $index string An associative array index name in which to store the value
	* @param $content mixed The value to store. This can be any type of variable.
	**/
	public static function flash($index,$content) {
		if (isset($_SESSION['flash'][$index])) {
			// If a flash var with the specified name already exists, add it to the exsisting one.
			if (!is_array($_SESSION['flash'][$index])) {
				// If the flash var is not already an array, make it into one
				$_SESSION['flash'][$index] = array($_SESSION['flash'][$index]);
			}
			$_SESSION['flash'][$index][] = $content;
		}
		else {
			$_SESSION['flash'][$index] = $content;
		}
	}
	/**
	 * Unset a specific flash variable
	 *
	 * @param string $index Array index of the variable to unset
	 * @return void
	 * @author Peter Epp
	 */
	public static function flash_unset($index) {
		unset($_SESSION['flash'][$index]);
	}
	/**
	 * Return the value of a flash variable
	 *
	 * @param string $index The array index of the flash variable to retrieve
	 * @return mixed
	 * @author Peter Epp
	 */
	public static function flash_get($index) {
		if (isset($_SESSION['flash']) && isset($_SESSION['flash'][$index])) {
			return $_SESSION['flash'][$index];
		}
		return null;
	}
	/**
	 * Whether or not flash vars exist
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public static function has_flash_vars() {
		$flash_vars = Session::get('flash');
		return ($flash_vars !== null);
	}
	/**
	 * Whether or not flash vars were just cleared at the end of the last request
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public static function flash_vars_just_cleared() {
		return Session::get('flash_vars_just_cleared');
	}
	/**
	* Dump out the contents of a session variable with a specified index in HTML format
	*
	* @return string The contents of the session variable at the specified index in the form of an HTML string
	* @author Peter Epp
	* @param $index string The associative array index of the desired session variable
	**/
	public static function flash_html_dump($index) {
		if (is_array($_SESSION['flash'][$index])) {
			return implode('<br>',$_SESSION['flash'][$index]);
		}
		else {
			return $_SESSION['flash'][$index];
		}
	}
	/**
	* Ascertain the existence of a session variable
	*
	* @return bool The state of existence of the specified session variable
	* @author Peter Epp
	* @param $index string The associate array index of the desired session variable
	**/
	public static function flash_isset($index) {
		return isset($_SESSION['flash'][$index]);
	}
	/**
	* Clear a specified session variable
	*
	* @return void
	* @author Peter Epp
	* @param $index string The associative array index of the desired session variable
	**/
	public static function flash_empty() {
		$flash_vars = Session::get('flash');
		if ($flash_vars !== null) {
			Session::set('flash_vars_just_cleared',true);
		} else {
			Session::unset_var('flash_vars_just_cleared');
		}
		Session::unset_var('flash');
	}
	/**
	 * Whether or not a given message is already in the session flash variable at a given index
	 *
	 * @param string $index The associative index to check
	 * @param string $message The message to look for
	 * @return void
	 * @author Peter Epp
	 */
	public static function already_flashed($index,$message) {
		$var = Session::flash_get($index);
		if (is_array($var)) {
			return (in_array($message,$var));
		}
		return ($var == $message);
	}
	/**
	 * If a user session exists determine if it has expired. If not, extend it and return a response based whether the request is normal or a keep-alive ping.
	 * This function requires the Authenticator plugin
	 *
	 * @return bool True or false on normal requests, void on ping requests
	 * @author Peter Epp
	 */
	public static function is_expired() {
		if (!Biscuit::instance()->ModuleAuthenticator()->user_is_logged_in()) {	// No-one logged in
			return true;
		}
		if (SERVER_TYPE == 'LOCAL_DEV' && DEBUG) {
			// For local dev machine in debug mode, extend the session so it continues infinitely.
			self::set_expiry();
		} else if (!empty($_SESSION['expires_at']) && $_SESSION['expires_at'] < time()) {
			return true;
		}
		return false;
	}
	/**
	 * Dump out the session array
	 *
	 * @return array Contents of $_SESSION
	 * @author Peter Epp
	 */
	public static function contents() {
		return $_SESSION;
	}
	/**
	 * Decode all data in a serialized session data string
	 *
	 * @param string $data_str 
	 * @return void
	 * @author Peter Epp
	 */
	public static function decode($data_str) {
		$vars=preg_split('/([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\|/',$data_str,-1,PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		for($i=0; $vars[$i]; $i++) {
			$result[$vars[$i++]]=unserialize($vars[$i]);     
		}
		return $result;
	}
}
?>