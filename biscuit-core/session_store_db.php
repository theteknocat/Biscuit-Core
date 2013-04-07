<?php
/**
 * Session database storage handler.  This class is automatically initialized and used by the Session class whenever you start a new session.
 * Set the system setting "USE_DB_SESSIONS" to true in config.php to enable use of this module.
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: session_store_db.php 14737 2012-11-30 22:56:56Z teknocat $
 */
class SessionStoreDb {
	/**
	 * Database link identifier
	 *
	 * @var link
	 */
	private $db_link;
	/**
	 * Database hostname
	 *
	 * @var string
	 */
	private $dbhost;
	/**
	 * Database user name
	 *
	 * @var string
	 */
	private $dbuser;
	/**
	 * Database password
	 *
	 * @var string
	 */
	private $dbpass;
	/**
	 * Database name
	 *
	 * @var string
	 */
	private $dbname;
	/**
	 * Whether or not to use persistent connections
	 *
	 * @var string
	 */
	private $persistent;
	/**
	 * Constructor. Stores the global database settings in the object.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function __construct() {
		$this->dbhost       = DBHOST;
		$this->dbuser       = DBUSER;
		$this->dbpass       = DBPASS;
		$this->dbname       = DBNAME;
	}
	/**
	 * Open a database connection to the session data
	 *
	 * @return bool Connection success
	 * @author Peter Epp
	 */
	public function open() {
		$this->db_link = mysql_connect($this->dbhost, $this->dbuser, $this->dbpass);
		if ($this->db_link) {
			return mysql_select_db($this->dbname, $this->db_link);
		}
		return false;
	}
	/**
	 * Close the database session
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public function close() {
		mysql_close($this->db_link);
		return true;
	}
	/**
	 * Read the data for a given session
	 *
	 * @param string $id Session ID
	 * @return mixed Data on success or false on failure
	 * @author Peter Epp
	 */
	public function read($id) {
		$id = $this->dbescape($id);
		if ($result = mysql_query("SELECT `data` FROM `php_sessions` WHERE `id` = '$id'",$this->db_link)) {
			if (mysql_num_rows($result)) {
				$row = mysql_fetch_assoc($result);
				return $row['data'];
			}
		}
		return false;
	}
	/**
	 * Write the session data to the database
	 *
	 * @param string $id ID of session to write
	 * @param string $data Serialized session contents
	 * @return void
	 * @author Peter Epp
	 */
	public function write($id,$data) {
		Console::log("Writing data for session ID: ".$id);
		$data = $this->dbescape($data);
		$id = $this->dbescape($id);
		$access_time = time();
		return mysql_query("REPLACE INTO `php_sessions` VALUES ('$id','$access_time','$data')",$this->db_link);
	}
	/**
	 * Clean up old sessions
	 *
	 * @param timestamp $max_time Maximum session age
	 * @return void
	 * @author Peter Epp
	 */
	public function clean($max_time) {
		$old = $this->dbescape((time()-$max_time));
		return mysql_query("DELETE FROM `php_sessions` WHERE `access_time` < '$old'",$this->db_link);
	}
	/**
	 * Destroy a given session
	 *
	 * @param string $id ID of session to destroy
	 * @return bool Success
	 * @author Peter Epp
	 */
	public function destroy($id) {
		$id = $this->dbescape($id);
		return mysql_query("DELETE FROM `php_sessions` WHERE `id` = '$id'",$this->db_link);
	}
	/**
	 * Sanitize a value for DB query
	 *
	 * @param string $value Dirty value
	 * @return string Sanitized value
	 * @author Peter Epp
	 */
	private function dbescape($value) {
		if (get_magic_quotes_gpc()) {
			$value = stripslashes($value);
		}
		return mysql_real_escape_string($value,$this->db_link);
	}
	/**
	 * Factory function.
	 *
	 * @return void
	 * @author Peter Epp
	 * @static
	 */
	public static function init() {
		self::install();
		$save_handler = ini_get("session.save_handler");
		if ($save_handler != "user") {
			ini_set("session.save_handler", "user");
		}
		$me = new self();
		session_set_save_handler(array($me,"open"),array($me,"close"),array($me,"read"),array($me,"write"),array($me,"destroy"),array($me,"clean"));
	}
	/**
	 * Create the session storage DB table
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function install() {
		if (!DB::table_exists("php_sessions")) {
			DB::query("CREATE TABLE `php_sessions` (
				`id` VARCHAR( 32 ) NOT NULL,
				`accessed` INT(12) NOT NULL,
				`data` LONGTEXT NOT NULL,
				PRIMARY KEY (`id`)
			)");
		}
	}
}
?>