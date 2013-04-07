<?php
/**
 * The general model for handling all user-related data functions.  It's purpose is only to provide a base user system, and alone is useful for sites that only ever
 * need one or two permanent accounts for site administrators.  When building a site that needs a user management system, this model should either be extended by or
 * used in conjunction with an account management plugin.
 *
 * @package Plugins
 * @author Peter Epp
 */
class User extends AbstractModel {
	/**
	 * Username for logging in
	 *
	 * @var string
	 */
	var $username;
	/**
	 * Password for logging in
	 *
	 * @var string
	 */
	var $password;
	/**
	 * New password, usually passed to the model when submitting a form
	 *
	 * @var string
	 */
	var $new_password;
	/**
	 * First name
	 *
	 * @var string
	 */
	var $first_name;
	/**
	 * Last name
	 *
	 * @var string
	 */
	var $last_name;
	/**
	 * User's access level
	 *
	 * @var int
	 */
	var $user_level;
	/**
	 * User's account status
	 *
	 * @var int
	 */
	var $status;
	/**
	 * Find a single user by id
	 *
	 * @param int $id The id of the user
	 * @return object An instance of the user model
	 * @author Peter Epp
	 */
	function find($id) {
		$id = (int) $id;
		return User::user_from_query("SELECT * FROM users WHERE id = ".$id);
	}
	function find_by_username($username) {
		$username = DB::escape($username);
		return User::user_from_query("SELECT * FROM users WHERE username = '".$username."'");
	}
	/**
	 * Find all users in the database
	 *
	 * @return array A collection of instances of the user model, one for each user found in the database
	 * @author Peter Epp
	 */
	function find_all() {
		return User::users_from_query("SELECT * FROM users ORDER BY `first_name`, `last_name`");
	}

	/**
	 * Return the current user's login name
	 *
	 * @return string
	 * @author Peter Epp
	 */
	function username() {		return $this->get_attribute('username'); }
	/**
	 * Return the full name of the current user
	 *
	 * @return string
	 * @author Peter Epp
	 */
	function full_name() {		return sprintf("%s %s",$this->first_name(),$this->last_name()); }
	/**
	 * Return the first name of the current user
	 *
	 * @return string
	 * @author Peter Epp
	 */
	function first_name() {		return $this->get_attribute('first_name'); }
	/**
	 * Return the last name of the current user
	 *
	 * @return string
	 * @author Peter Epp
	 */
	function last_name() {		return $this->get_attribute('last_name'); }
	/**
	 * Return the current user's level
	 *
	 * @return int
	 * @author Peter Epp
	 */
	function user_level() {		return (int)$this->get_attribute('user_level'); }
	/**
	 * Return the current user's status
	 *
	 * @return int
	 * @author Peter Epp
	 */
	function status() {			return (int)$this->get_attribute('status'); }
	/**
	 * Return the current user's password
	 *
	 * @return string
	 * @author Peter Epp
	 */
	function password() {		return $this->get_attribute('password'); }
	function new_password() {	return $this->get_attribute('new_password'); }

	function save_filter($item_data) {
		if (!empty($item_data['new_password'])) {
			$item_data['password'] = Authenticator::hash_password($item_data['new_password']);
		}
		unset($item_data['new_password']);
		return $item_data;
	}
	/**
	 * This validate function is nothing mare than a placeholder that always returns true.  It exists to prevent errors when called by the AbstractModel.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function validate() {
		$this->has_been_validated(true);
		return true;
	}
	/**
	 * Whether or not the provided password matches the current one
	 *
	 * @param string $password Password to check
	 * @return bool
	 * @author Peter Epp
	 */
	function password_matches($password) {
		return (Authenticator::hash_password($password) == $this->password());
	}
	/**
	 * Build a user object from a database query
	 *
	 * @param string $query Database query
	 * @return object
	 * @author Peter Epp
	 */
	function user_from_query($query) {
		return parent::model_from_query("User",$query);
	}
	/**
	 * Build a collection of users from a database query
	 *
	 * @param string $query Database query
	 * @return array Collection of user objects
	 * @author Peter Epp
	 */
	function users_from_query($query) {
		return parent::models_from_query("User",$query);
	}
	function db_tablename() {
		return 'users';
	}
	function db_create_query() {
		return "CREATE TABLE  `users` (
		`id` INT( 8 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		`username` VARCHAR( 255 ) NOT NULL,
		`password` VARCHAR( 255 ) NOT NULL,
		`first_name` VARCHAR( 255 ) NOT NULL,
		`last_name` VARCHAR( 255 ) NOT NULL,
		`status` INT(2) NOT NULL DEFAULT 2,
		`user_level` INT(2) NOT NULL,
		UNIQUE INDEX username(`username`)
		) TYPE = MyISAM";
	}
}
?>