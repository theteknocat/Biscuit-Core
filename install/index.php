<?php
require_once(dirname(__FILE__).'/../config/system_globals.php');
require_once(SITE_ROOT.'/config/global.php');
require_once(FW_ROOT.'/bootstrap.php');
require_once(FW_ROOT.'/biscuit-core/i18n.php');

error_reporting(E_ERROR & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING & ~E_NOTICE);

session_name(SESSION_NAME);
session_start();

class Install {
	/**
	 * Place to store the current host name
	 *
	 * @var string
	 * @static
	 */
	private static $_host_name;
	/**
	 * Whether or not host config file exists
	 *
	 * @var bool
	 */
	private $_has_host_config = false;
	/**
	 * Whether or not we can connect to the database
	 *
	 * @var bool
	 */
	private $_db_can_connect = false;
	/**
	 * Whether or not the database tables are installed
	 *
	 * @var bool
	 */
	private $_db_tables_installed = false;
	/**
	 * Whether or not database connection validates based on user's input
	 *
	 * @var bool
	 */
	private $_db_connection_is_valid = false;
	/**
	 * Action to run
	 *
	 * @var string
	 */
	private $_action;
	/**
	 * Vars used for rendering content
	 *
	 * @var array
	 */
	private $_view_vars = array();
	/**
	 * The title of the current installer page
	 *
	 * @var string
	 */
	private $_page_title = 'Biscuit Installer';
	/**
	 * Full path to uploaded SQL file, if applicable
	 *
	 * @var string
	 */
	private $_uploaded_sql_file = '';
	/**
	 * Installation error messages
	 *
	 * @var array
	 */
	private $_errors = array();
	/**
	 * Reason installation completely failed and couldn't continue, if any
	 *
	 * @var string
	 */
	private $_install_failure_reason;
	/**
	 * Base database tables needed by Biscuit
	 *
	 * @var array
	 */
	private $_biscuit_base_tables = array(
		"access_levels",
		"acct_status",
		"extensions",
		"locales",
		"menus",
		"migrations",
		"model_last_updated",
		"module_pages",
		"modules",
		"page_aliases",
		"page_index",
		"password_reset_tokens",
		"permissions",
		"string_translations",
		"system_settings",
		"user_email_verifications",
		"users"
	);
	/**
	 * Request parameters (user input). Defaults can be set here
	 *
	 * @var array
	 */
	private $params = array(
		'install_data' => array('install_type' => 'clean', 'db_host' => 'localhost', 'use_smtp' => 'no', 'use_smtp_auth' => 'no')
	);
	/**
	 * Run the installer
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function run() {
		$is_installed = $this->_is_installed();
		if (!Request::is_post() && !Session::get('installer_running') && $is_installed) {
			// Locale needs to be set since capture include will try to fetch the current locale if the DB is connected, which it will be in this case
			I18n::instance()->set_locale();
			$this->_action = 'already_installed';
		} else {
			$this->set_params();
			Session::set('installer_running',true);
			$this->_action = Request::form('action');
			if (empty($this->_action)) {
				$this->_action = Request::query_string('action');
			}
			if (empty($this->_action)) {
				$this->_action = 'install';
			}
		}
		$action_method = 'action_'.$this->_action;
		if (is_callable(array($this, $action_method))) {
			// Dispatch to action
			call_user_func(array($this, $action_method));
		} else {
			die("Unrecognized action.");
		}
	}
	/**
	 * Set cleaned user input on object as params
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function set_params() {
		Request::set_user_input();
		$user_input = Request::user_input();
		if (!empty($user_input)) {
			foreach ($user_input as $key => $value) {
				$this->params[$key] = $value;
			}
		}
		$session_install_data = Session::get('install_data');
		if (!empty($session_install_data)) {
			$this->params['install_data'] = $session_install_data;
			Session::unset_var('install_data');
		}
	}
	/**
	 * Render the already installed notice
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function action_already_installed() {
		$this->set_view_var('host_name',$this->_host_name());
		$this->render();
	}
	/**
	 * Render manual install instructions and configuration file contents
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function action_manual_config() {
		if (empty($this->params['install_data']) || !Session::get('installer_running') || !Session::get('manual_install_required')) {
			Response::redirect('/framework/install/');
		}
		$host_config_file = $this->_host_config_file();
		if (!file_exists($host_config_file)) {
			$host_config_file = $this->_host_config_dir().'/config.php';
		}
		$this->set_view_var('host_configuration',$this->get_host_config($host_config_file));
		$this->set_view_var('global_configuration',$this->get_global_config());
		$host_config_file = substr($host_config_file,strlen(SITE_ROOT));
		$this->set_view_var('host_config_file',$host_config_file);
		Session::unset_var('installer_running');
		Session::unset_var('manual_install_required');
		$this->render();
	}
	/**
	 * Process step 1 post request and redirect, or render
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function action_install() {
		if (Request::is_post()) {
			if ($this->validate_install()) {
				$install_data = $this->params['install_data'];
				$queries = $this->_get_install_sql_queries($this->_uploaded_sql_file);
				if (!empty($queries) && !empty($this->_uploaded_sql_file)) {
					// If using a user-uploaded file, check that it has all the required tables
					if (!$this->_sql_dump_has_all_required_tables($queries)) {
						@unlink($this->_uploaded_sql_file);
						$this->_errors[] = "The SQL dump file you supplied does not contain table creation queries for all the required base tables. Please select a valid SQL dump file.";
					}
				} else if (empty($queries) && !empty($this->_uploaded_sql_file)) {
					@unlink($this->_uploaded_sql_file);
					$this->_errors[] = "The SQL dump file you supplied does not contain any database queries. Please select a valid SQL dump file.";
				}
				if (empty($this->_errors)) {
					$this->_perform_installation($queries);
				}
			}
			if (!empty($this->_errors)) {
				$error_messages = '<ul><li>'.implode('</li><li>',$this->_errors).'</li></ul>';
				$this->set_view_var('error_messages',$error_messages);
			}
		}
		$this->_set_attribute_defaults();
		$this->set_view_var('host_name',$this->_host_name());
		$this->set_view_var('has_host_config',$this->_has_host_config);
		$this->set_view_var('db_can_connect',$this->_db_can_connect);
		$this->set_view_var('db_tables_installed',$this->_db_tables_installed);
		$this->set_view_var('installer',$this);
		$this->set_view_var('can_import_sql',is_writable(SITE_ROOT.'/var/uploads'));
		$this->render();
	}
	/**
	 * Perform the actual installation
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function _perform_installation($install_queries) {
		$dbhost = $this->install_data('db_host');
		$dbname = $this->install_data('db_name');
		$dbuser = $this->install_data('db_username');
		$dbpass = $this->install_data('db_password');
		foreach ($install_queries as $query) {
			$result = DB::query($query);
			if (is_string($result) && substr($result,0,12) == 'MySQL Error:') {
				$this->_errors[] = $result;
			}
		}
		if (file_exists($this->_uploaded_sql_file)) {
			@unlink($this->_uploaded_sql_file);
		}
		if (empty($this->_errors)) {
			if (!is_writable(SITE_ROOT.'/config')) {
				Session::set('install_data',$this->params['install_data']);
				Session::set('manual_install_required',true);
				Response::redirect('/framework/install/manual_config');
				return;
			}
			$host_config_file = $this->_host_config_file();
			if (!file_exists($host_config_file)) {
				// Ensure the config directory for the host exists and get contents of default config file:
				Crumbs::ensure_directory($this->_host_config_dir());
				$host_config_file = $this->_host_config_dir().'/config.php';
			} else {
				// Make a backup!!
				copy($host_config_file,$host_config_file.'.bak');
			}
			$host_configuration = $this->get_host_config($host_config_file);
			file_put_contents($host_config_file,$host_configuration);
			$global_configuration = $this->get_global_config();
			file_put_contents(SITE_ROOT.'/config/global.php',$global_configuration);
			Session::reset();
			Session::flash('user_success','Congratulations, your Biscuit site has been successfully installed!');
			Response::redirect('/?empty_caches=1');
		}
	}
	/**
	 * Return the host configuration per user input
	 *
	 * @return string
	 * @author Peter Epp
	 */
	private function get_host_config($host_config_file) {
		if (!file_exists($host_config_file)) {
			$host_config_lines = file(FW_ROOT.'/config/default/config.php');
			$new_lines = array();
			foreach ($host_config_lines as $index => $line) {
				if ($index < 1 || $index > 8) {
					// Keep all but lines 2-9
					$new_lines[] = $line;
				}
			}
			$host_configuration = implode("",$new_lines);
		} else {
			// Get contents of current host config file:
			$host_configuration = file_get_contents($host_config_file);
		}
		// Update the various host config settings and save it
		$host_configuration = preg_replace('/define\([\"\']{1}DBHOST[\"\']{1},[\s]*[\"\']{1}[^\"\']*[\"\']{1}\)/i',"define('DBHOST','".$this->install_data('db_host')."')",$host_configuration);
		$host_configuration = preg_replace('/define\([\"\']{1}DBNAME[\"\']{1},[\s]*[\"\']{1}[^\"\']*[\"\']{1}\)/i',"define('DBNAME','".$this->install_data('db_name')."')",$host_configuration);
		$host_configuration = preg_replace('/define\([\"\']{1}DBUSER[\"\']{1},[\s]*[\"\']{1}[^\"\']*[\"\']{1}\)/i',"define('DBUSER','".$this->install_data('db_username')."')",$host_configuration);
		$host_configuration = preg_replace('/define\([\"\']{1}DBPASS[\"\']{1},[\s]*[\"\']{1}[^\"\']*[\"\']{1}\)/i',"define('DBPASS','".$this->install_data('db_password')."')",$host_configuration);
		$server_type = $this->install_data('server_type');
		$host_configuration = preg_replace('/define\([\"\']{1}SERVER_TYPE[\"\']{1},[\s]*[\"\']{1}[^\"\']*[\"\']{1}\)/i',"define('SERVER_TYPE','".$server_type."')",$host_configuration);
		if ($server_type == 'LOCAL_DEV') {
			$logging_level = 4;
			$debug = 'true';
			$no_cache = 'true';
		} else if ($server_type == 'TESTING') {
			$logging_level = 1;
			$debug = 'true';
			$no_cache = 'false';
		} else if ($server_type == 'PRODUCTION') {
			$logging_level = 1;
			$debug = 'false';
			$no_cache = 'false';
		}
		$host_configuration = preg_replace('/define\([\"\']{1}LOGGING_LEVEL[\"\']{1},[\s]*([0-9]+)\)/i',"define('LOGGING_LEVEL',".$logging_level.")",$host_configuration);
		$host_configuration = preg_replace('/define\([\"\']{1}DEBUG[\"\']{1},[\s]*(true|false)\)/i',"define('DEBUG',".$debug.")",$host_configuration);
		$host_configuration = preg_replace('/define\([\"\']{1}NO_CACHE[\"\']{1},[\s]*(true|false)\)/i',"define('NO_CACHE',".$no_cache.")",$host_configuration);
		if ($this->install_data('use_smtp') == 'yes') {
			$use_smtp = 'true';
			$smtp_host = $this->install_data('smtp_host');
			if ($this->install_data('use_smtp_auth') == 'yes') {
				$use_smtp_auth = 'true';
				$smtp_user = $this->install_data('smtp_user');
				$smtp_pass = $this->install_data('smtp_password');
			} else {
				$use_smtp_auth = 'false';
				$smtp_user = '';
				$smtp_pass = '';
			}
		} else {
			$use_smtp = 'false';
			$smtp_host = '';
			$use_smtp_auth = 'false';
			$smtp_user = '';
			$smtp_pass = '';
		}
		$host_configuration = preg_replace('/define\([\"\']{1}USE_SMTP_MAIL[\"\']{1},[\s]*(true|false)\)/i',"define('USE_SMTP_MAIL',".$use_smtp.")",$host_configuration);
		$host_configuration = preg_replace('/define\([\"\']{1}SMTP_HOST[\"\']{1},[\s]*[\"\']{1}[^\"\']*[\"\']{1}\)/i',"define('SMTP_HOST','".$smtp_host."')",$host_configuration);
		$host_configuration = preg_replace('/define\([\"\']{1}USE_SMTP_AUTH[\"\']{1},[\s]*(true|false)\)/i',"define('USE_SMTP_AUTH',".$use_smtp_auth.")",$host_configuration);
		$host_configuration = preg_replace('/define\([\"\']{1}SMTP_USERNAME[\"\']{1},[\s]*[\"\']{1}[^\"\']*[\"\']{1}\)/i',"define('SMTP_USERNAME','".$smtp_user."')",$host_configuration);
		$host_configuration = preg_replace('/define\([\"\']{1}SMTP_PASSWORD[\"\']{1},[\s]*[\"\']{1}[^\"\']*[\"\']{1}\)/i',"define('SMTP_PASSWORD','".$smtp_password."')",$host_configuration);
		return $host_configuration;
	}
	/**
	 * Return the global configuration per user input
	 *
	 * @return string
	 * @author Peter Epp
	 */
	private function get_global_config() {
		// Now update settings stored in the global site config
		$global_config_file = SITE_ROOT.'/config/global.php';
		// Make a backup!!
		copy($global_config_file,$global_config_file.'.bak');
		$global_configuration = file_get_contents($global_config_file);
		$global_configuration = preg_replace('/define\([\"\']{1}TECH_EMAIL[\"\']{1},[\s]*[\"\']{1}[^\"\']*[\"\']{1}\)/i',"define('TECH_EMAIL','".$this->install_data('email_tech_contact')."')",$global_configuration);
		return $global_configuration;
	}
	/**
	 * Set attribute default values where applicable
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function _set_attribute_defaults() {
		if (!Request::is_post()) {
			if ($this->_db_can_connect) {
				// If a DB connection can already be established (meaning there's already a config file with valid db data), and no values have been submitted in user input,
				// Set the default values to the ones already in config
				$this->params['install_data']['db_host'] = DBHOST;
				$this->params['install_data']['db_name'] = DBNAME;
				$this->params['install_data']['db_username'] = DBUSER;
				$this->params['install_data']['db_password'] = DBPASS;
			}
			if (defined('SERVER_TYPE')) {
				$this->params['install_data']['server_type'] = SERVER_TYPE;
			}
			if (defined('USE_SMTP_MAIL')) {
				$this->params['install_data']['use_smtp'] = (USE_SMTP_MAIL) ? 'yes' : 'no';
			}
			if (defined('SMTP_HOST')) {
				$this->params['install_data']['smtp_host'] = SMTP_HOST;
			}
			if (defined('USE_SMTP_AUTH')) {
				$this->params['install_data']['use_smtp_auth'] = (USE_SMTP_AUTH) ? 'yes' : 'no';
			}
			if (defined('SMTP_USERNAME')) {
				$this->params['install_data']['smtp_user'] = SMTP_USERNAME;
			}
			if (defined('SMTP_PASSWORD')) {
				$this->params['install_data']['smtp_password'] = SMTP_PASSWORD;
			}
			if (defined('TECH_EMAIL')) {
				$this->params['install_data']['email_tech_contact'] = TECH_EMAIL;
			}
		}
	}
	/**
	 * Validate install user input
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	private function validate_install() {
		if ($this->install_data('install_type') == 'migration') {
			if (!Request::files('sql_file')) {
				$this->_errors[] = 'Select an SQL file';
			} else {
				$uploaded_file = new FileUpload(Request::files('sql_file'),'/var/uploads',true);
				if (!$uploaded_file->is_okay()) {
					if ($uploaded_file->no_file_sent()) {
						$this->_errors[] = 'Select an SQL file';
					} else {
						$this->_errors[] = "SQL File upload failed: ".$uploaded_file->get_error_message();
					}
				} else {
					$this->_uploaded_sql_file = SITE_ROOT.'/var/uploads/'.$uploaded_file->file_name();
				}
			}
		}
		$this->_validate_db();
		if (!$this->attr_is_valid('smtp_host')) {
			$this->_errors[] = "Enter the SMTP host name";
		}
		if (!$this->attr_is_valid('smtp_user')) {
			$this->_errors[] = "Enter the SMTP username";
		}
		if (!$this->attr_is_valid('smtp_password')) {
			$this->_errors[] = "Enter the SMTP password";
		}
		if (!$this->attr_is_valid('email_tech_contact')) {
			$this->_errors[] = "Enter a valid email address for the technical contact";
		}
		if (!empty($this->_errors) && !empty($this->_uploaded_sql_file)) {
			@unlink($this->_uploaded_sql_file);
		}
		return empty($this->_errors);
	}
	/**
	 * Validate connection to database
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function _validate_db() {
		$dbhost = $this->install_data('db_host');
		$dbname = $this->install_data('db_name');
		$dbuser = $this->install_data('db_username');
		$dbpass = $this->install_data('db_password');
		if (!empty($dbhost) && !empty($dbname) && !empty($dbuser)) {
			$this->_db_connection_is_valid = DB::connect($dbhost, $dbname, $dbuser, $dbpass);
			if (!$this->_db_connection_is_valid) {
				$this->_errors[] = "Unable to connect to the database using the information you supplied. Please ensure that you have entered the correct information and try again.";
			}
		} else {
			if (empty($dbhost)) {
				$this->_errors[] = "Enter the database host name";
			}
			if (empty($dbname)) {
				$this->_errors[] = "Enter the name of the database to use";
			}
			if (empty($dbuser)) {
				$this->_errors[] = "Enter the database username";
			}
		}
	}
	/**
	 * Validate a specific attribute
	 *
	 * @param string $attr_name 
	 * @return bool
	 * @author Peter Epp
	 */
	public function attr_is_valid($attr_name) {
		if ($this->attr_is_required($attr_name)) {
			$special_validation_method = $attr_name.'_is_valid';
			if (is_callable(array($this, $special_validation_method))) {
				return $this->$special_validation_method();
			}
			return ($this->install_data($attr_name) !== null);
		}
		// If not required, always return true
		return true;
	}
	private function db_host_is_valid() {
		return ($this->install_data('db_host') !== null && $this->_db_connection_is_valid);
	}
	private function db_name_is_valid() {
		return ($this->install_data('db_name') !== null && $this->_db_connection_is_valid);
	}
	private function db_username_is_valid() {
		return ($this->install_data('db_username') !== null && $this->_db_connection_is_valid);
	}
	private function db_password_is_valid() {
		return ($this->install_data('db_password') !== null && $this->_db_connection_is_valid);
	}
	private function email_tech_contact_is_valid() {
		return Crumbs::valid_email($this->install_data('email_tech_contact'));
	}
	/**
	 * Whether or not a given attribute is required
	 *
	 * @param string $attr_name 
	 * @return void
	 * @author Peter Epp
	 */
	public function attr_is_required($attr_name) {
		$special_required_method = $attr_name.'_is_required';
		if (is_callable(array($this,$special_required_method))) {
			return $this->$special_required_method();
		}
		$required_attributes = array('install_type', 'server_type', 'db_host', 'db_name', 'db_username', 'email_tech_contact');
		return in_array($attr_name, $required_attributes);
	}
	/**
	 * Make sql file required when doing a migration install
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	private function sql_file_is_required() {
		return ($this->install_data('install_type') == 'migration');
	}
	/**
	 * Make SMTP host name required if use SMTP is selected
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	private function smtp_host_is_required() {
		return ($this->install_data('use_smtp') == 'yes');
	}
	/**
	 * Make SMTP username required if use SMTP auth is selected
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	private function smtp_user_is_required() {
		return ($this->install_data('use_smtp') == 'yes' && $this->install_data('use_smtp_auth') == 'yes');
	}
	/**
	 * Make SMTP password required if use SMTP auth is selected
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	private function smtp_password_is_required() {
		return ($this->install_data('use_smtp') == 'yes' && $this->install_data('use_smtp_auth') == 'yes');
	}
	/**
	 * Return all or a single user input param
	 *
	 * @param string $key_name 
	 * @return void
	 * @author Peter Epp
	 */
	public function params($key_name = null) {
		if (empty($key_name)) {
			return $this->params;
		}
		if (!empty($this->params[$key_name])) {
			return $this->params[$key_name];
		}
		return null;
	}
	/**
	 * Return a specific install_data param value
	 *
	 * @param string $key_name 
	 * @return void
	 * @author Peter Epp
	 */
	public function install_data($key_name) {
		if (!empty($this->params['install_data']) && !empty($this->params['install_data'][$key_name])) {
			return $this->params['install_data'][$key_name];
		}
		return null;
	}
	/**
	 * Render the current action view file using the install template
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function render() {
		$page_content = Crumbs::capture_include($this->_view_file(), $this->_view_vars);
		// Send headers, passing a higher than zero access level so it sets private caching
		Response::send_headers(1);
		print Crumbs::capture_include('views/install/template.php', array('page_title' => $this->_page_title, 'page_content' => $page_content));
	}
	/**
	 * Set the title for the current page
	 *
	 * @param string $title 
	 * @return void
	 * @author Peter Epp
	 */
	private function title($title) {
		$this->_page_title = $title;
	}
	/**
	 * Set a view var for rendering
	 *
	 * @param string $var_name 
	 * @param string $value 
	 * @return void
	 * @author Peter Epp
	 */
	private function set_view_var($var_name, $value) {
		$this->_view_vars[$var_name] = $value;
	}
	/**
	 * Return the view file name for the current action
	 *
	 * @return string
	 * @author Peter Epp
	 */
	private function _view_file() {
		return 'views/install/'.$this->_action.'.php';
	}
	/**
	 * Parse the install SQL dump file and return just the queries we need to run (omitting comments, empty lines and lock/unlock tables)
	 *
	 * @return array
	 * @author Peter Epp
	 */
	private function _get_install_sql_queries($db_dump_file = null) {
		if (empty($db_dump_file)) {
			$db_dump_file = FW_ROOT.'/install/framework_mysql5_InnoDB.sql';
		}
		$sql_dump_lines = file($db_dump_file);
		// Filter out the unwanted lines from the dump file:
		foreach ($sql_dump_lines as $index => $line) {
			if (substr($line,0,1) == '#' || substr($line,0,2) == '--') {
				// Ignore comments
				unset($sql_dump_lines[$index]);
			} else if (preg_match('/^[\s]+$/',$line)) {
				// Remove line if it contains only whitespace
				unset($sql_dump_lines[$index]);
			}
		}
		// Compile remaining lines into string with line breaks:
		$sql_dump_str = implode("\n",$sql_dump_lines);
		// Split sql dump string into whole queries (by semi-colon followed by line break):
		$queries = explode(";\n",$sql_dump_str);
		// Check for and remove empty queries:
		foreach ($queries as $index => $query) {
			$queries[$index] = trim($query);
			if (empty($queries[$index])) {
				unset($queries[$index]);
			}
		}
		return $queries;
	}
	/**
	 * Check that the queries found in the dump file contain create syntax for all the base tables required by Biscuit
	 *
	 * @param array $queries 
	 * @return void
	 * @author Peter Epp
	 */
	private function _sql_dump_has_all_required_tables($queries) {
		$valid_table_count = 0;
		foreach ($queries as $query) {
			if (preg_match('/^CREATE TABLE `([^`]+)`/', $query, $matches)) {
				if (in_array($matches[1],$this->_biscuit_base_tables)) {
					$valid_table_count += 1;
				}
			}
		}
		return ($valid_table_count == count($this->_biscuit_base_tables));
	}
	/**
	 * Check if Biscuit is already installed by first checking if a config file for the site exists and if so including it, then see if database connection
	 * can be established and if so check that all the base tables have been installed. If all of that checks out return true, otherwise false
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	private function _is_installed() {
		$host_config_file = $this->_host_config_file();
		if (file_exists($host_config_file)) {
			$this->_has_host_config = true;
			require_once($host_config_file);
			if (DB::connect()) {
				$this->_db_can_connect = true;
				$table_names = DB::fetch("SHOW TABLES");
				if (!empty($table_names)) {
					$installed_table_count = 0;
					$missing_table_names = array();
					foreach ($this->_biscuit_base_tables as $table_name) {
						if (in_array($table_name, $table_names)) {
							$installed_table_count += 1;
						} else {
							$missing_table_names[] = $table_name;
						}
					}
					if ($installed_table_count == count($this->_biscuit_base_tables)) {
						$this->_db_tables_installed = true;
						// All base db tables are installed, which means Biscuit has been fully installed
						return true;
					} else {
						$this->set_view_var('missing_table_names', $missing_table_names);
					}
				}
			}
		}
		return false;
	}
	/**
	 * Return the full path to the configuration file for the current host
	 *
	 * @return string|bool Full file path if file was found, otherwise false
	 * @author Peter Epp
	 */
	private function _host_config_file() {
		if (empty($this->_host_config_file)) {
			$hostname = $this->_host_name();
			$config_basedir = SITE_ROOT."/config/";
			$config_file = $config_basedir.$hostname."/config.php";
			if (!file_exists($config_file)) {
				$no_config = true;
				// If no file is found matching the host name (without "www" prefix), try looking for a file for the parent hostname in case it's a sub-domain. This opens up support for multipile domains
				if (count(explode('.',$hostname)) > 2) {
					preg_match('/.+\.([^\.]+)\.([^\.]+)$/',$hostname,$matches);
					$parent_hostname = $matches[1].'.'.$matches[2];
					$config_file = $config_basedir.$parent_hostname."/config.php";
					$no_config = !file_exists($config_file);
				}
				if ($no_config) {
					// If no config file exists for the parent domain, see if a canonical domain name is defined and if there's a config file for it
					if (defined('CANONICAL_DOMAIN') and CANONICAL_DOMAIN != '') {
						$config_file = $config_basedir.CANONICAL_DOMAIN."/config.php";
						$no_config = !file_exists($config_file);
					}
					if ($no_config) {
						$config_file = false;
					}
				}
			}
			$this->_host_config_file = $config_file;
		}
		return $this->_host_config_file;
	}
	/**
	 * Return the directory for the host configuration file
	 *
	 * @return string
	 * @author Peter Epp
	 */
	private function _host_config_dir() {
		return SITE_ROOT.'/config/'.$this->_host_name();
	}
	/**
	 * Return either the short or full host name
	 *
	 * @param string $format 
	 * @return void
	 * @author Peter Epp
	 */
	private function _host_name($format = "short") {
		if (empty(self::$_host_name)) {
			$full_hostname = Request::server('HTTP_HOST');
			if ($format == "full") {
				self::$_host_name = $full_hostname;
			} else if ($format == "short") {
				// Remove the "www." from the hostname if present:
				if (substr($full_hostname,0,4) == "www.") {
					$hostname = substr($full_hostname,4);
				}
				else {
					$hostname = $full_hostname;
				}
				self::$_host_name = $hostname;
			}
		}
		return self::$_host_name;
	}
}

$installer = new Install();

$installer->run();
