<?php
/**
 * Set of static methods for connecting to a MySQL database and performing various queries
 *
 * @package Core
 * @author Peter Epp
 **/
class DB {
	/**
	 * Open database connection
	 *
	 * @return void
	 * @author Peter Epp
	 **/
	function connect() {
		if (USE_PERSISTENT_DB) {
			$db_link = @mysql_pconnect(DBHOST, DBUSER, DBPASS);
		}
		else {
			$db_link = @mysql_connect(DBHOST, DBUSER, DBPASS);
		}
		if (!$db_link) {
			trigger_error("Cannot connect to MySQL database:<br><br>".DB::error(),E_USER_ERROR);
		}
		else {
			if (!@mysql_select_db(DBNAME, $db_link)) {
				trigger_error("Cannot select database:<br><br>".DB::error(),E_USER_ERROR);
			}
		}
	}
	/**
	 * Close database connection
	 *
	 * @return void
	 * @author Peter Epp
	 **/
	function disconnect() {
		@mysql_close();
	}
	/**
	 * Similar to the query() static function, but intended specifically for selecting multiple rows from a table
	 *
	 * @return mixed Returns an indexed array of the tables rows (each of which is an associative array of the row) on success, false on failure, or kills the script on a mysql error
	 * @author Peter Epp
	 * @param string $query SELECT query string to run
	 **/
	function fetch($query) {
		$result = mysql_query($query);
		if (!DB::error() && DB::num_rows($result) > 0) {
			for ($i=0;$i < DB::num_rows($result);$i++) {
				$row = DB::row($result);
				if (count($row) > 1) {
					$return[$i] = $row;
				}
				else {
					$return[$i] = reset($row);
				}
			}
			DB::free($result);
			return $return;
		}
		else if (DB::error()) {
			trigger_error("Failed to fetch data from database:<br><br>".DB::error()."<br>Original query:<br>".$query,E_USER_ERROR);
		}
		return false;
	}
	/**
	 * Call fetch using prepared statement
	 *
	 * @param string $statement 
	 * @param string $values 
	 * @return void
	 * @author Peter Epp
	 */
	function pfetch($statement,$values) {
		return DB::fetch(DB::prepare_statement($statement,$values));
	}
	/**
	 * Fetch one row of a table by the value of any column. Result is strictly limited to one row.
	 *
	 * @return mixed Returns an associative array of the data row on success, false on failure, or kills the script on a mysql error
	 * @author Peter Epp
	 * @param string $query
	 **/
	function fetch_one($query) {
		$result = mysql_query($query);
		if (!DB::error() && DB::num_rows($result) > 0) {
			$row = DB::row($result);
			DB::free($result);
			if (count($row) > 1) {
				return $row;
			}
			else {
				return reset($row);
			}
		}
		else if (DB::error()) {
			trigger_error("Failed to fetch data from database:<br><br>".DB::error()."<br>Original query:<br>".$query,E_USER_ERROR);
		}
		return false;
	}
	/**
	 * Call fetch one using prepared statement
	 *
	 * @param string $statement 
	 * @param string $values 
	 * @return void
	 * @author Peter Epp
	 */
	function pfetch_one($statement,$values) {
		return DB::fetch_one(DB::prepare_statement($statement,$values));
	}
	/**
	 * Run an insert query and return the new insert ID.  Only works with INSERT queries (duh).
	 *
	 * @param string $query MySql insert query
	 * @return void
	 * @author Peter Epp
	 */
	function insert($query) {
		mysql_query($query);
		if (DB::error()) {
			trigger_error("Failed to insert data into database:<br><br>".DB::error()."<br>Original query:<br>".$query,E_USER_ERROR);
			return false;
		}
		return @mysql_insert_id();
	}
	/**
	 * Call insert one using prepared statement
	 *
	 * @param string $statement 
	 * @param string $values 
	 * @return void
	 * @author Peter Epp
	 */
	function pinsert($statement,$values) {
		return DB::insert(DB::prepare_statement($statement,$values));
	}
	/**
	 * Run any DB query, dying on error
	 *
	 * @return mixed Depending on the specific query, this may be a boolean or a result set
	 * @author Peter Epp
	 * @param string $query MySQL query
	 **/
	function query($query) {
		$result = mysql_query($query);
		if (DB::error()) {
			trigger_error("Failed to perform database query:<br><br>".DB::error()."<br>Original query:<br>".$query,E_USER_ERROR);
		}
		return true;
	}
	/**
	 * Call query one using prepared statement
	 *
	 * @param string $statement 
	 * @param string $values 
	 * @return void
	 * @author Peter Epp
	 */
	function pquery($statement,$values) {
		return DB::query(DB::prepare_statement($statement,$values));
	}
	/**
	 * Basic statement prepare method
	 *
	 * @param string $statement 
	 * @param string $values 
	 * @return void
	 * @author Peter Epp
	 */
	function prepare_statement($statement,$values) {
		$clean_values = DB::sanitize($values);
		$statement = trim(rtrim($statement));
		$statement_bits = explode("?",$statement);
		$query = "";
		foreach ($values as $index => $value) {
			$query .= $statement_bits[$index]."'".$value."'";
		}
		if (count($statement_bits > count($values))) {
			$query .= end($statement_bits);
		}
		return $query;
	}
	/**
	 * Take an associative array of data for DB insertion, sanitize it, and build it into a comma-separated string of `column` = 'data' for use in an UPDATE or INSERT query
	 *
	 * @return string Portion of a DB query string that follows "SET" in the format "`column1_name` = 'column1_data', `column2_name` = 'column2_data'" etc
	 * @author Peter Epp
	 * @param array $data Associative array of data for DB insertion. Array keys must match the column names, and the primary key column should not be included
	 **/
	function safe_query_from_data($data) {
		// Sanitize data for DB insertion:
		$data = DB::sanitize($data);
		// Build string of "`column_name` = 'data'"
		$query = "";
		$i = 0;
		foreach($data as $k => $v) {
			if (strtolower($v) !== 'null') {
				$val = "'".$v."'";
			}
			else {
				$val = $v;
			}
			$query .= "`".$k."` = ".$val."".(($i < count($data)-1) ? ", " : "");
			$i += 1;
		}
		return $query;
	}
	/**
	 * Recursively iterate through an associative array and sanitize the values for DB insertion using mysql_real_escape_string().
	 *
	 * @return array The sanitized array of data
	 * @author Peter Epp
	 * @param array $array The associative array of data to be sanitized
	 **/
	function sanitize($array) {
		// Recursively santitize all data in an array for MySql insertion
		foreach($array as $k => $v) {
			if (is_array($v)) {
				$array[$k] = DB::sanitize($v);
			}
			else {
				$array[$k] = DB::escape($v);
			}
		}
		return $array;
	}
	/**
	 * Escape a value for database insertion
	 *
	 * @param string $value 
	 * @return void
	 * @author Peter Epp
	 */
	
	function escape($value) {
		if (get_magic_quotes_gpc()) {
			$value = stripslashes($value);
		}
		return mysql_real_escape_string($value);
	}
	/**
	 * Return one row from a resultset as an associative array
	 *
	 * @param mixed $result Resultset
	 * @return void
	 * @author Peter Epp
	 */
	function row(&$result) {
		return mysql_fetch_assoc($result);
	}
	/**
	 * Return the number of rows in a resultset
	 *
	 * @param mixed $result Resultset
	 * @return void
	 * @author Peter Epp
	 */
	function num_rows(&$result) {
		return @mysql_num_rows($result);
	}
	/**
	 * Return the number of rows affected by a query
	 *
	 * @return int Number of affected rows
	 * @author Peter Epp
	 */
	function affected_rows() {
		return @mysql_affected_rows();
	}
	/**
	 * Find out if a database table exists
	 *
	 * @param string $table_name Name of the table to find
	 * @return bool
	 * @author Peter Epp
	 */
	function table_exists($table_name) {
		$db_table = DB::fetch_one("SHOW TABLES LIKE '".$table_name."'");
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
	function column_exists_in_table($column_name,$table_name) {
		$query = "SHOW COLUMNS FROM `".DB::escape($table_name)."` LIKE '".DB::escape($column_name)."'";
		$column = DB::fetch_one($query);
		return (!empty($column));
	}
	/**
	 * Find and return MySQL version as a numeric value
	 *
	 * @return float MySQL version number
	 * @author Peter Epp
	 */
	function version() {
		$my_version = DB::fetch_one('SELECT VERSION() AS `mysql_version`');
		$version_info = explode('.',$my_version);
		$major_ver = $version_info[0];
		$minor_ver = $version_info[1];
		$revision = $version_info[2];
		$num_version = (float)($major_ver.".".$minor_ver);
		return $num_version;
	}
	/**
	 * Return mysql error message
	 *
	 * @return string
	 * @author Peter Epp
	 */
	function error() {
		return mysql_error();
	}
	/**
	 * Free a resultset
	 *
	 * @param mixed $result Resultset
	 * @return void
	 * @author Peter Epp
	 */
	function free(&$result) {
		@mysql_free_result($result);
	}
}
?>