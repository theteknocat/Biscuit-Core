<?php
/**
 * Wrapper for the PDO library providing a set of static methods for handling database operations
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: db.php 14723 2012-11-28 17:15:25Z teknocat $
 **/
class DB {
	/**
	 * Reference to the PDO object
	 *
	 * @author Peter Epp
	 */
	private static $_pdo = null;
	/**
	 * The number of rows returned by the last SELECT query
	 *
	 * @author Peter Epp
	 */
	private static $_last_num_rows_returned = null;
	/**
	 * The number of rows affected by the last INSERT, UPDATE or DELETE query
	 *
	 * @author Peter Epp
	 */
	private static $_last_num_affected_rows = null;
	/**
	 * Prevent instantiation
	 *
	 * @author Peter Epp
	 */
	private function __construct() {
		// Prevent instantiation
	}
	/**
	 * Open database connection and return a PDO object on success
	 *
	 * @return void
	 * @author Peter Epp
	 **/
	public static function connect($dbhost = null, $dbname = null, $dbuser = null, $dbpass = null) {
		if (empty($dbhost) && defined('DBHOST')) {
			$dbhost = DBHOST;
		}
		if (empty($dbname) && defined('DBNAME')) {
			$dbname = DBNAME;
		}
		if (empty($dbuser) && defined('DBUSER')) {
			$dbuser = DBUSER;
		}
		if (empty($dbpass) && defined('DBPASS')) {
			$dbpass = DBPASS;
		}
		$dsn = "mysql:dbname=".$dbname.";host=".$dbhost;
		$driver_options = array(
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8' COLLATE 'utf8_general_ci'"
		);
		try {
			self::$_pdo = new PDO($dsn, $dbuser, $dbpass, $driver_options);
			return true;
		} catch (PDOException $e) {
			if (!Session::get('installer_running')) {
				Console::exception_handler($e);
			}
			self::$_pdo = null;
			return false;
		}
	}
	/**
	 * Whether or not we have a database connection
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public static function is_connected() {
		return (is_object(self::$_pdo));
	}
	/**
	 * Close database connection
	 *
	 * @return void
	 * @author Peter Epp
	 **/
	public static function close() {
		self::$_pdo = null;
	}
	/**
	 * Similar to the query() static function, but intended specifically for selecting multiple rows from a table
	 *
	 * @return mixed Returns an indexed array of the tables rows (each of which is an associative array of the row) on success, false on failure, or kills the script on a mysql error
	 * @author Peter Epp
	 * @param string $query SELECT query string to run
	 **/
	public static function fetch($query, $params = array()) {
		$stmt = self::query($query, $params);
		return self::rows($stmt);
	}
	/**
	 * Fetch one row of a table by the value of any column. Result is strictly limited to one row.
	 *
	 * @return mixed Returns an associative array of the data row on success, false on failure, or kills the script on a mysql error
	 * @author Peter Epp
	 * @param string $query
	 **/
	public static function fetch_one($query, $params = array()) {
		$stmt = self::query($query, $params);
		return self::row($stmt);
	}
	/**
	 * Run an insert query and return the new insert ID.  Only works with INSERT queries (duh).
	 *
	 * @param string $query MySql insert query
	 * @return void
	 * @author Peter Epp
	 */
	public static function insert($query, $params = array()) {
		self::query($query, $params);
		return (int)self::$_pdo->lastInsertId();
	}
	/**
	 * Run any DB query, dying on error
	 *
	 * @return mixed Depending on the specific query, this may be a boolean or a result set
	 * @author Peter Epp
	 * @param string $query MySQL query
	 **/
	public static function query($query, $params = array()) {
		self::log_query($query, $params);
		self::$_last_num_rows_returned = null;
		$stmt = self::$_pdo->prepare($query);
		$result = $stmt->execute((array)$params);
		self::$_last_num_affected_rows = $stmt->rowCount();
		if ($result === false) {
			$error_info = $stmt->errorInfo();
			if (!Session::get('installer_running')) {
				trigger_error('Database Query ('.$query.') failed: '.$error_info[2].' (Params: '.print_r($params, true).')', E_USER_ERROR);
			} else {
				return 'MySQL Error: '.$error_info[2];
			}
		}
		return $stmt;
	}
	/**
	 * Take an associative array of data for DB insertion and build it into a comma-separated string of `column` = ':column' for use in a PDO UPDATE or INSERT query
	 *
	 * @return string Portion of a DB query string that follows "SET" in the format "`column1_name` = 'column1_data', `column2_name` = 'column2_data'" etc
	 * @author Peter Epp
	 * @param array $data Associative array of data for DB insertion. Array keys must match the column names, and the primary key column should not be included
	 **/
	public static function query_from_data($data) {
		$pdo_data = self::pdo_assoc_array($data);
		$query_array = array();
		foreach($pdo_data as $k => $v) {
			$column_name = substr($k,1);	// Everything after the colon.
			$query_array[] = "`".$column_name."` = ".$k;
		}
		return implode(", ",$query_array);
	}
	/**
	 * Put a colon at beginning of all the key names in an associative array so it can then be passed to a query method.
	 *
	 * @param array $array An associative array of data
	 * @return array The array with the key names transformed to include a colon at the beginning
	 * @author Peter Epp
	 */
	public static function pdo_assoc_array($array) {
		$new_array = array();
		if (!empty($array)) {
			foreach ($array as $k => $v) {
				if (is_array($v)) {
					$v = serialize($v);
				}
				$db_key = $k;
				if (substr($db_key,0,1) != ":") {
					$db_key = ":".$db_key;
				}
				$new_array[$db_key] = $v;
			}
		}
		return $new_array;
	}
	/**
	 * Return one row from a resultset
	 *
	 * @param mixed $stmt Statement object, or false if the query was empty
	 * @return mixed
	 * @author Peter Epp
	 */
	public static function row(&$stmt) {
		if (is_object($stmt) && $stmt->rowCount() > 0) {
			if ($stmt->columnCount() > 1) {
				$result = $stmt->fetch(PDO::FETCH_ASSOC);
			}
			else {
				$result = $stmt->fetch(PDO::FETCH_NUM);
				$result = reset($result);
			}
			self::$_last_num_rows_returned = count($result);
			return $result;
		}
		return false;
	}
	/**
	 * Array of rows from a resultset.
	 *
	 * @param mixed $stmt Statement object, or false if the query was empty
	 * @return array
	 * @author Peter Epp
	 */
	public static function rows(&$stmt) {
		if (is_object($stmt) && $stmt->rowCount() > 0) {
			if ($stmt->columnCount() > 1) {
				$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			}
			else {
				$result = $stmt->fetchAll(PDO::FETCH_NUM);
				foreach ($result as $index => $value) {
					$result[$index] = reset($value);
				}
			}
			self::$_last_num_rows_returned = count($result);
			return $result;
		}
		return false;
	}
	/**
	 * Return the number of rows affected by a query
	 *
	 * @return int Number of affected rows
	 * @author Peter Epp
	 */
	public static function affected_rows() {
		return self::$_last_num_affected_rows;
	}
	/**
	 * Return the number of rows returned by the last SELECT query
	 *
	 * @return mixed Integer or null
	 * @author Peter Epp
	 */
	public static function num_rows() {
		return self::$_last_num_rows_returned;
	}
	/**
	 * Find out if a database table exists
	 *
	 * @param string $table_name Name of the table to find
	 * @return bool
	 * @author Peter Epp
	 */
	public static function table_exists($table_name) {
		$db_table = self::fetch_one("SHOW TABLES LIKE '{$table_name}'");
		return ($db_table == $table_name);
	}
	/**
	 * Whether or not a column exists in a table
	 *
	 * @param string $column_name 
	 * @param string $table_name 
	 * @return bool
	 * @author Peter Epp
	 */
	public static function column_exists_in_table($column_name,$table_name) {
		$columns = self::fetch("SHOW COLUMNS FROM `{$table_name}`");
		foreach ($columns as $column) {
			if ($column['Field'] == $column_name) {
				return true;
				break;
			}
		}
		return false;
	}
	/**
	 * Find and return MySQL version as a numeric value
	 *
	 * @return float MySQL version number
	 * @author Peter Epp
	 */
	public static function version() {
		$my_version = self::fetch_one('SELECT VERSION() AS `mysql_version`');
		$version_info = explode('.',$my_version);
		$major_ver = $version_info[0];
		$minor_ver = $version_info[1];
		$revision = $version_info[2];
		$num_version = (float)($major_ver.".".$minor_ver);
		return $num_version;
	}
	/**
	 * Add a query along with info about the method that called it to the list of queries to log
	 *
	 * @param string $query 
	 * @return void
	 * @author Peter Epp
	 */
	private static function log_query($query, $params) {
		$backtrace = debug_backtrace();
		$db_method = AkInflector::humanize($backtrace[2]['function']);
		$called_by = $backtrace[3]['class'].$backtrace[3]['type'].$backtrace[3]['function'];
		// Tidy up the query:
		$query = preg_replace("/[\n\r]/","\t",$query);	// Replace line breaks with tabs
		$query = preg_replace("/\t+/"," ",$query);	// Replace tabs with spaces
		$query = preg_replace("/\s\s+/"," ",$query);	// Replace 2 or more spaces with 1 space
		// Build the log message in CSV format:
		$log_message = '"'.$db_method.'","'.$called_by.'","'.addslashes($query).'","'.print_r($params,true).'"';
		// Store in the query log:
		Console::log_query($log_message);
	}
}
