<?php
// Ensure that the user model is included:
require_once("plugins/User.php");
/**
 * Provides user authentication functions
 *
 * @package Plugins
 * @author Peter Epp
 **/
class Authenticator extends AbstractPluginController {
	/**
	 * Array of info about the current page's access level (ie. login url, access level name, access level number)
	 *
	 * @var array
	 */
	var $access_info = array();
	/**
	* Information about the logged in user
	*
	* @var array
	**/
	var $user;

	function run($params) {
		$this->check_install();
		$this->set_login_level($params);
		$this->_set_access_info();
		$this->get_user();
		$this->check_page_access();
		parent::run($params);		// Dispatch to action
	}
	function action_index() {
		if ($this->is_login_url()) {
			if (!empty($this->params['ref_page'])) {
				Session::flash_unset('login_redirect');
				Session::flash('login_redirect',$this->params['ref_page']);
			}
			$this->render($this->login_view_file());
		}
		else {
			Console::log("                        Not a login page, moving on...");
		}
	}
	/**
	 * Return the name of the login view file to use for the current page (ie if the page we're on is /some_subsection/login then the login view file would be some_subsection_login)
	 *
	 * @return string The name of the login view file without the .php extension
	 * @author Peter Epp
	 */
	function login_view_file() {
		$my_page = $this->Biscuit->full_page_name;
		$page_bits = explode("/",$my_page);
		$view_file = implode("_",$page_bits);
		return $view_file;
	}
	/**
	 * Check if the user can access the current page, whether or not their session has expired (if logged in), and act accordingly
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function check_page_access() {
		// If no input processing occurred above, then we just do the normal user authentication:
		if (Authenticator::user_is_logged_in()) {
			if (Session::is_expired() && !Request::is_ping_keepalive()) {
				$this->action_logout("Your login session has expired.");
				return;
			}
			Session::set_expiry();
			if (Request::is_ping_keepalive()) {
				Console::log("Nothing more than a keep-alive ping. No need to continue.");
				Biscuit::end_program();
			}
			else if ($this->params['action'] != "logout") {
				// Set a notification that a user is currently logged in
				EventManager::notify("user_is_logged_in");
			}
		}
		if ($this->Biscuit->access_level > PUBLIC_USER) {
			// If the current page is not public, authenticate the currently logged-in user (if any):
			if (!Authenticator::user_is_logged_in()) {
				Session::flash_unset('login_redirect');
				Session::flash("login_redirect","/".$this->Biscuit->full_page_name);
				Session::flash("user_message","Please login to access the requested page.");
				if (Request::is_ajax()) {
					$this->Biscuit->render_js('document.location.href="'.$this->access_info['login_url'].'";');
					Biscuit::end_program();
				}
				else {
					if ($this->access_info['login_url'] == Request::uri()) {
						// This should never happen unless someone was silly enough to set the access level of the login page to somehing other than public
						trigger_error('Trying to redirect to login page from login page', E_USER_NOTICE);
					}
					if (Request::uri() != null) {
						Session::flash_unset('login_redirect');
    					Session::flash('login_redirect',Request::uri());
					}
					Response::redirect($this->access_info['login_url']); // Redirect to the login page for this access level
				}
			}
			elseif (!Permissions::can_access($this->Biscuit->access_level)) {
			    // The current page has higher than public access restriction, and the currently logged 
			    // in user does not have a sufficient access level for this page..
				if (Request::referer() && Request::referer() != Request::uri()) {
				   $redirect_to = Request::referer();
				} else {
				   $redirect_to = '/';
				}
				Session::flash('user_message', "You do not have sufficient member privileges to access the requested page.");
				if (Request::is_ajax()) {
					$this->Biscuit->render_js('document.location.href="'.$redirect_to.'";');
					Biscuit::end_program();
				} else {
					Response::redirect($redirect_to);
				}
				
			}
		}
	}
	function get_user() {
		if (Authenticator::user_is_logged_in()) {
			$this->user = Authenticator::return_current_user();
			$this->_set_logged_in_session_data();
		}
	}
	/**
	 * Instantiate and return a model for the currently logged in user. This function requires a check on whether a user is logged in before calling it.
	 * This method was created so it can be called independently when a reference to the instantiated copy of Authenticator is not available, like within a model.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function return_current_user() {
		$auth_data = Session::get("auth_data");
		return User::find($auth_data['id']);
	}
	/**
	 * Log a user in if their credentials are valid, otherwise spit out an error
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_login() {
		if ($this->valid_credentials()) {
			if (Request::is_ajax() && Request::type() == "login") {
				$this->Biscuit->render("+OK");
				Biscuit::end_program();
			}
			else {
				$login_info = $this->params['login_info'];
				Session::flash('user_message', 'Logged in');
				Console::log("                        Successful login for ".$login_info['username']);

				$this->_set_logged_in_session_data();
				Session::set_expiry();
				$access_level_home_url = $this->_get_access_level_home($this->user->user_level());

				$redirect_page = (!empty($this->params['login_redirect'])) ? 
					$this->params['login_redirect'] : 
					$access_level_home_url;

				if (!empty($this->access_info['login_url']) && $redirect_page == $this->access_info['login_url']) {
					$redirect_page = $access_level_home_url;
				}
				if (empty($redirect_page)) {
					$redirect_page = '/';		// Failsafe
				}
				Response::redirect($redirect_page);
			}
		}
		else {
			if (Request::is_ajax() && Request::type() == "login") {
				$this->Biscuit->render("-ERROR");
				Biscuit::end_program();
			}
			else {
				if ($this->credentials_submitted()) {
					Session::flash("user_message", "Invalid username or password");
				}
				if (isset($this->params['login_redirect'])) {
					Session::flash_unset('login_redirect');
	    			Session::flash('login_redirect',$this->params['login_redirect']);
				}
				$this->render($this->login_view_file());
			}
		}
	}
	function action_logout($user_msg = "You have been logged out.") {
		if (!Session::already_flashed('user_message',$user_msg)) {
			Session::flash("user_message",$user_msg);
		}
		// Reset the session, keeping the flash variables in tact
		Console::log("                        Clearing session...");
		$keepers[] = 'flash';
		if ($this->Biscuit->plugin_exists('HitCounter')) {
			$keepers[] = "counted";
		}
		Session::reset(true,$keepers);
		Console::log("                        Redirecting user...");
		if ($this->Biscuit->access_level > PUBLIC_USER) {
			$gopage = $this->_get_access_level_login_url($this->Biscuit->access_level);
		}
		else {
			// If we've logged out from a publicly accessible page we should stay on it after the logout
			$gopage = '/'.$this->Biscuit->full_page_name;
		}
		if (Request::is_ajax()) {
			if ($this->Biscuit->access_level > PUBLIC_USER) {
				// Only redirect if the page doesn't have public access
				$this->Biscuit->render_js('document.location.href="'.$gopage.'"');
				Biscuit::end_program();
			}
			else {
				// Otherwise let the page continue to run and render
				// However, if "logout" is still the action other plugins may fail to load their content
				// As such we'll clear the action from the query so other plugins will default to "index"
				Request::clear_query('action');
				Request::clear_form('action');
				$this->Biscuit->set_user_input();
			}
		}
		else {
			Response::redirect($gopage);
		}
	}

	function action_retrieve_password() {
		if (isset($this->params['email_address'])) {
			$user = User::find_by_username($this->params['email_address']);
			if ($user !== false) {
				$mail = new Mailer();
				$options['Subject'] = "Password retrieval";
				$options['To'] = $this->params['email_address'];
				$options['ToName'] = $user->full_name();
				$user_data = array('password' => $user->password());
				$result = $mail->send_mail('plugins/Authenticator/password_retrieval',$options,$user_data);
				if ($result == "+OK") {
					Session::flash('user_message',"Your password has been sent to ".$this->params['email_address']);
				}
				else {
					Session::flash('user_message',$result);
				}
				Response::redirect("/login");
			}
			else {
				Session::flash("user_message","The email address you entered could not be found.");
				Response::redirect("/retrieve_password");
			}
		}
	}

	function action_reset_password() {
		if (!isset($this->params['step']) || $this->params['step'] == "") {
			// Default to the first step of the password reset form, which is just to display the page:
			$this->params['step'] = 1;
		}
		if (Request::is_post() && $this->process_reset()) {
			if ($this->params['step'] == 1) {
				Response::redirect('/reset_password');
			}
			else if ($this->params['step'] == 2) {
				Response::redirect('/login');
			}
		}
	}
	function process_reset() {
		if ($this->reset_validate()) {
			switch ($this->params['step']) {
				case 1:
					$this->params['access_code'] = Crumbs::random_password(13); // Generate a random temporary password
					$mail = new Mailer();
					$options['Subject'] = "Password Reset Access Code";
					$options['To'] = $this->params['user_name'];
					$result = $mail->send_email('plugins/authenticator/pwd_reset_code',$options,$viewvars);
					if ($result == "+OK") {
						Session::flash('access_code',$access_code);
					}
					else {
						Session::flash('user_message',$result);
						Response::redirect("/reset_password");
					}
					break;
				case 2:
					$my_input = DB::sanitize($this->params);
					$query = "UPDATE users SET password = '".md5($my_input['new_password1'])."' WHERE username = '".$my_input['user_name']."'";
					DB::query($query);
					Session::flash("user_message","Your password has been successfully changed");
					Response::redirect("/login");
					break;
			}
		}
		else {
			return false;
		}
	}
	function reset_validate() {
		switch ($this->params['step']) {
			case 1:
				if (Crumbs::valid_email($this->params['user_name']) == false) {
					Session::flash('user_message',"Please enter a valid email address");
					return false;
				}
				return true;
				break;
			case 2:
				$errors = false;
				if ($this->params['security_answer'] == "") {
					$errors = true;
					Session::flash('user_message',"Please answer your security question");
				}
				$secret_code = Session::get('captcha');
				if (!Captcha::matches($this->params['security_code'])) {
					$errors = true;
					Session::flash('user_message',"Please enter the security code shown in the image");
				}
				$access_code = Session::get('access_code');
				if ($this->params['tmp_code'] != $access_code) {
					$errors = true;
					Session::flash('user_message',"Please enter the access code that was sent to you by email.");
				}
				if (strlen($this->params['new_password1']) < 7) {
					$errors = true;
					Session::flash('user_message',"Please enter a password at least 7 characters long");
				}
				if ($this->params['new_password2'] != $this->params['new_password1']) {
					$errors = true;
					Session::flash('user_message',"Your confirmation password does not match");
				}
				if (!$errors) {
					// Validate user account existence
					$user = User::find_by_username($this->params['user_name']);
					if ($user !== false) {
						$this->params['security_question'] = $user->security_question;
						$this->params['step'] = 2;
						$this->params['contact_name'] = $user->full_name();
					}
					else {
						$errors = true;
						Session::flash("user_message","There are no active accounts matching that email address");
					}
				}
				return !$errors;
				break;
		}
	}
	/**
	 * Validate the credentials submitted by the user
	 *
	 * @return void bool Whether or not credentials are valid
	 * @author Peter Epp
	 */
	function valid_credentials() {
		if (!$this->credentials_submitted()) {
			Console::log("                        Login_info is empty");
			return false;
		}
		if ($this->credentials_submitted_empty()) {
			Console::log("                        Empty username or password");
			return false;
		}

		$login_info = $this->params['login_info'];
		$this->user = $this->_get_user_data($login_info);
		if (!$this->user || !$this->_passwords_match($login_info['password'], $this->user->password())) {
			Console::log("                        User not found or password mismatch");
			return false;
		}
		return true;
	}
	/**
	 * Check if login information was submitted
	 *
	 * @return bool Whether or not login info was submitted
	 * @author Peter Epp
	 */
	function credentials_submitted() {
		return !empty($this->params['login_info']);
	}
	/**
	 * Check if one of the submitted login info fields is empty
	 *
	 * @return bool Whether or not a login field was empty
	 * @author Peter Epp
	 */
	function credentials_submitted_empty() {
		return (empty($this->params['login_info']['username']) || empty($this->params['login_info']['password']));
	}
	/**
	 * Set $this->login_level
	 *
	 * @return void
	 **/
	function set_login_level($params) {
		// Determine the access level for the page
		if ($this->Biscuit->access_level > 0) {
			// If it's not a public page, set the access level to that of the current page as defined in the page_index table
			$this->login_level = $this->Biscuit->access_level;
		}
		elseif (!empty($params['login_level'])) {
			// Otherwise if the current page is public (access level 0), and there's a "login_level" form field submitted, which would be the case if the user
			// submitted a login form, set the login level to that of the submitted form
			$this->login_level = $params['login_level'];
		}
		else {
			// Otherwise default to the public level
			$this->login_level = PUBLIC_USER;
		}	
		Console::log("                        Login level: ". $this->login_level);
	}

	/**
	 * Is a user currently logged in?
	 *
	 * @return boolean
	 * @author Lee O'Mara
	 **/
	function user_is_logged_in() {
		if (Session::var_exists('auth_data')) {
			$auth_data = Session::get('auth_data');
		}
		return (!empty($auth_data) && !empty($auth_data['id']));
	}
	/**
	 * Fetch the URL to redirect the user to after they logout
	 *
	 * @return string URL relative to the site root
	 * @author Peter Epp
	 */
	function login_url() {
		if (Authenticator::user_is_logged_in()) {
			$auth_data = Session::get('auth_data');
			// Grab the login page for this user's level:
			return DB::fetch_one("SELECT `login_url` FROM `access_levels` WHERE `id` = ".$auth_data['user_level']);
		}
		return "/login";
	}
	/**
	 * Return the home url for the current user based on their user level.  This method does not check if a user is logged in, so you must check that before calling this method
	 *
	 * @return string
	 * @author Peter Epp
	 */
	function user_home_url() {
		$auth_data = Session::get('auth_data');
		return DB::fetch_one("SELECT `home_url` FROM `access_levels` WHERE `id` = ".$auth_data['user_level']);
	}
	/**
	 * Map global variable names to access level numbers so that scripts and plugins do not need to rely on every site have specific level numbers.
	 * Default globals that are expect by the system are PUBLIC_USER, WEBMASTER, ADMINISTRATOR and SYSTEM_LORD, so errors may occur if these four
	 * levels do not exist in the database. SYSTEM_LORD is the programmer level, and is intended for use when programmer-level functions are incorporated
	 * such as install/updating scripts.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function define_access_levels() {
		$level_data = DB::fetch("SELECT * FROM access_levels ORDER BY id");
		if ($level_data !== false) {
			Console::log("        Authenticator: Defining system access levels:");
			for ($i=0;$i < count($level_data);$i++) {
				define($level_data[$i]['var_name'],$level_data[$i]['id']);
				Console::log("            ".$level_data[$i]['var_name']." = ".$level_data[$i]['id']);
			}
		}
	}
	/**
	* Return a user object by username, if the user exists in the database
	* 
	* Override this method in descendants to accommodate varying table fields
	* 
	* @return abject False if no user found
	**/
	function _get_user_data($login_info) {
		return User::find_by_username($login_info['username']);
	}
	/**
	* Set $this->access info
	* 
	* The access_name and login_url for a specific login level
	*
	* @return void
	**/
	function _set_access_info() {
		// Collect the access level data for the current login level:
		$this->access_info = DB::fetch_one("SELECT * FROM `access_levels` WHERE `id` = ".$this->login_level);
	}

	/**
	* Do the given passwords match?
	*
	* @return boolean
	* @author Lee O'Mara
	**/
	function _passwords_match($user_input, $stored_pass) {
		return (Authenticator::hash_password($user_input) == $stored_pass);
	}
	/**
	 * Whether or not to use a hash function when matching password.
	 *
	 * @return mixed False if hash should not be used, otherwise name of hash function to use if defined
	 * @author Peter Epp
	 */
	function use_hash() {
		if (defined("USE_PWD_HASH")) {
			$use_hash = USE_PWD_HASH;
		}
		else {
			$use_hash = "no";
		}
		if ($use_hash != "no" && function_exists($use_hash)) {
			return $use_hash;
		}
		return false;
	}
	/**
	 * Hash the provided password for storing in the database (if required) using the hash method defined in the system settings
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function hash_password($password) {
		$use_hash = Authenticator::use_hash();
		if (!$use_hash) {
			return $password;
		}
		return $use_hash($password);
	}
	/**
	* Return the URL for the homepage of this level user
	*
	* @return string
	* @author Lee O'Mara
	**/
	function _get_access_level_home($user_level) {
		return DB::fetch_one("SELECT home_url FROM access_levels WHERE id = ".$user_level);
	}
	/**
	 * Get the login url for a specified user level
	 *
	 * @param string $user_level 
	 * @return void
	 * @author Peter Epp
	 */
	
	function _get_access_level_login_url($user_level) {
		return DB::fetch_one("SELECT login_url FROM access_levels WHERE id = ".$user_level);
	}
	/**
	 * Return the url's for login pages for all user levels
	 *
	 * @return array Nidexed array of login url's
	 * @author Peter Epp
	 */
	function _get_access_level_login_urls() {
		$urls = DB::fetch("SELECT login_url FROM access_levels WHERE login_url != '' GROUP BY login_url");
		if (!$urls) {
			return array();
		}
		return $urls;
	}
	/**
	 * Check if the current page is a login page
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	function is_login_url() {
		$my_page = '/'.$this->Biscuit->full_page_name;
		$login_pages = $this->_get_access_level_login_urls();
		return (in_array($my_page,$login_pages));
	}
	/**
	* Set values in the session after a successful login
	*
	* @return void
	* @author Lee O'Mara
	**/
	function _set_logged_in_session_data() {
		$auth_data['id']         = (int)$this->user->id();
		$auth_data['username']   = $this->user->username();
		$auth_data['user_level'] = $this->user->user_level();
		Session::set('auth_data',$auth_data);
	}
	/**
	 * Return the session timeout in seconds for the currently logged in user's level
	 *
	 * @return int Number of seconds
	 * @author Peter Epp
	 */
	function session_timeout() {
		$auth_data = Session::get('auth_data');
		$session_timeout = DB::fetch_one("SELECT session_timeout FROM access_levels WHERE id = ".$auth_data['user_level']);
		return intval($session_timeout,10) * 60;
	}
	/**
	 * Return the unix timestamp of the session expiry time
	 *
	 * @return void
	 * @author Peter Epp
	 */
    function session_expires_at() {
        return mktime() + Authenticator::session_timeout();
    }
	/**
	 * Supply the name (or names) of the database table(s) used by this plugin
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function db_tablename() {
		return array(User::db_tablename(),'access_levels','permissions');
	}
	/**
	 * Return the query to create a new table for this plugin
	 *
	 * @param string $table_name The name of the table whose query you want
	 * @return string SQL query
	 * @author Peter Epp
	 */
	function db_create_query($table_name) {
		switch ($table_name) {
			case 'users':
				return User::db_create_query();
				break;
			case 'access_levels':
				return "CREATE TABLE `access_levels` (
				  `id` tinyint(1) unsigned NOT NULL,
				  `var_name` varchar(32) NOT NULL,
				  `name` varchar(16) NOT NULL,
				  `description` text NOT NULL,
				  `login_url` varchar(255) default NULL,
				  `home_url` varchar(64) NOT NULL,
				  `session_timeout` int(3) unsigned NOT NULL default '0',
				  PRIMARY KEY  (`id`)
				) TYPE=MyISAM AUTO_INCREMENT=3 PACK_KEYS=0;";
				break;
			case 'permissions':
				return "CREATE TABLE `permissions` (
				  `permission_string` varchar(255) NOT NULL,
				  `access_level` int(11) NOT NULL,
				  PRIMARY KEY  (`permission_string`)
				) TYPE=MyISAM;";
				break;
		}
	}
	/**
	 * Populate the plugin's database with default values. For authenticator we setup default access levels and a single admin user
	 *
	 * @param string $table_name Name of the table to populate
	 * @return string Database insert query
	 * @author Peter Epp
	 */
	function db_populate_query($table_name) {
		switch ($table_name) {
			case 'users':
				return "INSERT INTO `users` VALUES (1,'admin','admin','Your','Name',2,0);";
				break;
			case 'access_levels':
				return "INSERT INTO `access_levels` VALUES (0,'PUBLIC_USER','Public','Everyone','','',999999),(99,'SYSTEM_LORD','System Lord','The programmer. For as and when programmer-level CMS is developed.','','',10);";
				break;
			case 'permissions':
				return "INSERT INTO `permissions` VALUES ('plugins:newsandeventsmanager:delete',0),('plugins:newsandeventsmanager:edit',0),('plugins:newsandeventsmanager:new',0);";
				break;
		}
	}
}
// Define access level globals needed by the Biscuit class before Authenticator has been instantiated:
Authenticator::define_access_levels();

?>