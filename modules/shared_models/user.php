<?php
/**
 * The general model for handling all user-related data functions.  It's purpose is only to provide a base user system, and alone is useful for sites that only ever
 * need one or two permanent accounts for site administrators.  When building a site that needs a user management system, this model should either be extended by or
 * used in conjunction with an account management plugin.
 *
 * @package Modules
 * @author Peter Epp
 */
class User extends AbstractModel {
	/**
	 * Return the full name of the current user
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function full_name() {		return sprintf("%s %s",$this->first_name(),$this->last_name()); }

	public function password_is_required() {
		return $this->is_new();
	}
	public function password_is_valid() {
		$user_input = $this->user_input('user');
		$user_provided_password = $user_input['password'];
		if ($this->is_new() || !empty($user_provided_password)) {
			$password_is_valid = $this->valid_password_string($user_provided_password);
			if (!$password_is_valid) {
				$this->set_error('password','Provide a password at least 8 characters long containing upper and lower case letters with at least one number and one symbol');
			}
			return $password_is_valid;
		}
		Console::log("User already exists and no password entered, consider it valid");
		return true;
	}
	public function password_confirmation() {
		return $this->user_input('password_confirmation');
	}
	public function password_confirmation_is_valid() {
		$user_input = $this->user_input('user');
		$user_provided_password = $user_input['password'];
		$is_valid = ($this->password_confirmation() && $this->password_confirmation() == $user_provided_password);
		if (!$is_valid) {
			$this->set_error('password_confirmation','Confirm your password');
		}
		return $is_valid;
	}
	private function valid_password_string($password) {
		return (strlen($password) >= 8 && preg_match("/[a-z]+/",$password) && preg_match("/[A-Z]+/",$password) && preg_match("/[0-9]+/",$password) && preg_match("/[\!\@\#\$\%\^\&\/\?\\\=\+\-\_\,\.]+/",$password));
	}
	/**
	 * Validate that all required attributes are set
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public function validate() {
		$is_valid = parent::validate();
		$user_input = $this->user_input('user');
		$user_provided_password = $user_input['password'];
		if ($this->is_new() || !empty($user_provided_password)) {
			$this->password_confirmation_is_valid();
		}
		return !$this->errors();
	}
	protected function _set_attribute_defaults() {
		$user_input = $this->user_input('user');
		$user_provided_password = $user_input['password'];
		if (!$this->is_new() && empty($user_provided_password)) {
			$this->_set_attribute('password',$this->user_input('current_password'));
		} else {
			$this->_set_attribute('password',Biscuit::instance()->ModuleAuthenticator()->hash_password($user_provided_password));
		}
	}
}
?>