<?php
require_once(dirname(__FILE__)."/../config/system_globals.php");
/**
 * Class for handling site configuration. It will initialize and validate the site's configuration file.
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: configuration.php 13959 2011-08-08 16:25:15Z teknocat $
 */
class Configuration implements Singleton {
	/**
	 * List of configuration errors
	 *
	 * @var array Array of associative arrays
	 */
	private $_errors;
	/**
	 * Full path to the config file for the host, or false if none was found
	 *
	 * @var string|bool
	 */
	private $_host_config_file;
	/**
	 * Reference to instantiation of self
	 *
	 * @var Configuration
	 */
	private static $_instance;
	/**
	 * Place to store the name of the current host
	 *
	 * @var string|null
	 */
	private static $_host_name;
	/**
	 * Return a singleton instance of the Configuration object
	 *
	 * @return Biscuit
	 * @author Peter Epp
	 */
	public static function instance() {
		if (empty(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
	 * Shortcut to self::instance() for semantic purposes
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function load($host_name = null) {
		self::$_host_name = $host_name;
		return self::instance();
	}
	/**
	 * Initialize site configuration file
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function __construct() {
		$this->load_site_globals();
		$this->load_host_settings();
		if (Bootstrap::is_browser_run_level()) {
			$this->ensure_directory_setup();
		}
		$this->validate();
		$this->set_base_urls();
	}
	/**
	 * Load global config for the site, if present
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function load_site_globals() {
		$global_site_config = SITE_ROOT."/config/global.php";
		if (file_exists($global_site_config)) {
			require_once($global_site_config);
		}
	}
	/**
	 * Load settings for the current host
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function load_host_settings() {
		$config_file = $this->host_config_file();
		if ($config_file) {
			require_once($config_file);
		} else {
			Response::redirect('/framework/install/');
		}
	}
	/**
	 * Return the full path to the configuration file for the current host
	 *
	 * @return string|bool Full file path if file was found, otherwise false
	 * @author Peter Epp
	 */
	private function host_config_file() {
		if (empty($this->_host_config_file)) {
			$hostname = $this->host_name();
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
	 * Ensure that base directories are setup and are writable
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function ensure_directory_setup() {
		if (!Crumbs::ensure_directory(SITE_ROOT."/var/cache/pages")) {
			$this->set_error('warning',"Page cache directory (/var/cache/pages) does not exist or is not writable. Server-side page caching will not occur, which will impact performance.");
		}
		if (!Crumbs::ensure_directory(SITE_ROOT."/var/log")) {
			$this->set_error('warning',"Log directory (/var/log) does not exist or is not writable. All messages will be routed to the default PHP log file.");
		}
		if (!Crumbs::ensure_directory(SITE_ROOT."/var/uploads")) {
			$this->set_error('warning',"Uploads directory (/var/uploads) does not exist or is not writable. File uploads will fail.");
		}
		if (!Crumbs::ensure_directory(TEMP_DIR)) {
			$this->set_error('warning',"Temporary directory (/var/tmp) does not exist or is not writable. File uploads that require post-processing, such as images, will fail.");
		}
		if (!Crumbs::ensure_directory(SITE_ROOT.'/var/cache/css')) {
			$this->set_error('warning',"CSS cache directory (/var/cache/css) does not exist or is not writable. CSS files cannot be aggregated, which will impact performance.");
		}
		if (!Crumbs::ensure_directory(SITE_ROOT."/var/cache/js")) {
			$this->set_error('warning',"Javascript cache directory (/var/cache/js) does not exist or is not writable. JavaScript files cannot be aggregated, which will impact performance.");
		}
	}
	/**
	 * Return either the short or full host name
	 *
	 * @param string $format 
	 * @return void
	 * @author Peter Epp
	 */
	private function host_name($format = "short") {
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
	/**
	 * Set the standard and secure base URLs for the site
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function set_base_urls() {
		if (SSL_DISABLED) {
			$ssl_protocol = "http";
		}
		else {
			$ssl_protocol = "https";
		}
		$full_hostname = $this->host_name("full");
		/**
		 * Standard (non-secure) base URL
		 */
		define('STANDARD_URL',"http://".$full_hostname);
		/**
		 * Secure base URL
		 */
		define('SECURE_URL',$ssl_protocol."://".$full_hostname);
	}
	/**
	 * Validate configuration by checking for required constants
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function validate() {
		$required_constants = array(
			"DBHOST",
			"DBUSER",
			"DBPASS",
			"DBNAME",
			"LOGGING_LEVEL",
			"DEBUG",
			"SERVER_TYPE",
			"SSL_DISABLED",
			"SESSION_NAME"
		);
		// Check for the existence of all required constants:
		$missing_vars = array();
		foreach ($required_constants as $constant_name) {
			if (!defined($constant_name)) {
				$missing_vars[] = $constant_name;
			}
		}
		if (!empty($missing_vars)) {
			$this->set_error('critical',"Your configuration is missing the following required constants: ".implode(", ",$missing_vars));
		}
		if (defined("INCLUDE_PEAR") && INCLUDE_PEAR == true && !Crumbs::file_exists_in_load_path("PEAR.php")) {
			$this->set_error('critical',"PEAR is defined as required for this site but could not be found in any of the include paths.");
		}
	}
	/**
	 * Check server config
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function check_server_config() {
		$magic_quotes = (get_magic_quotes_gpc()) ? "On" : "Off";
		$register_globals = ini_get("register_globals");
		if (empty($register_globals) || $register_globals == "0" || strtolower($register_globals) == "off") {
			$register_globals = "Off";
		}
		else {
			$register_globals = "On";
		}
		$display_errors = ini_get("display_errors");
		if (empty($display_errors) || $display_errors == "0" || strtolower($display_errors) == "off") {
			$display_errors = "Off";
		}
		else {
			$display_errors = "On";
		}
		if (DEBUG) {
			Console::log("    Server Information:");
			Console::log("        Server IP:            ".Request::server("SERVER_ADDR"));
			Console::log("        Server Name:          ".Request::server("SERVER_NAME"));
			Console::log("        Server Software:      ".Request::server("SERVER_SOFTWARE"));
			Console::log("    PHP Configuration:");
			Console::log("        upload_max_filesize:  ".ini_get("upload_max_filesize"));
			Console::log("        post_max_size:        ".ini_get("post_max_size"));
			Console::log("        max_input_time:       ".ini_get("max_input_time"));
			Console::log("        max_execution_time:   ".ini_get("max_execution_time"));
			Console::log("        magic_quotes_gpc:     ".$magic_quotes);
			Console::log("        register_globals:     ".$register_globals);
			Console::log("        display_errors:       ".$display_errors."\n");
		}
		if ($register_globals == "On") {
			$this->set_error("warning","Register globals is currently enabled in your PHP configuration. This may cause adverse affects and should therefore be disabled.");
		}
	}
	/**
	 * Return the latest date the configuration was modified
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public function latest_update() {
		$global_config_file = SITE_ROOT.'/config/global.php';
		$site_config_file   = $this->host_config_file();
		if (file_exists($global_config_file)) {
			$timestamps[] = filemtime($global_config_file);
		}
		$timestamps[] = filemtime($site_config_file);
		$system_settings_info = DB::fetch_one("SHOW TABLE STATUS LIKE 'system_settings'");
		if ($system_settings_info) {
			$timestamps[] = strtotime($system_settings_info['Update_time']);
		}
		rsort($timestamps);
		return reset($timestamps);
	}
	/**
	 * Define system settings found in the database as global variables
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function define_system_settings() {
		$settings_cache_file = SITE_ROOT.'/var/cache/system_settings.cache.php';
		if (file_exists($settings_cache_file)) {
			require_once($settings_cache_file);
			return;
		}
		// Read system settings from database:
		$settings = ModelFactory::instance('SystemSettings')->find_all();
		foreach ($settings as $setting) {
			define($setting->constant_name(),$setting->value());
		}
		if (is_writable(SITE_ROOT.'/config')) {
			// Cache lazily if config dir is writable
			$this->_cache_system_settings($settings);
		}
	}
	/**
	 * Cache system settings to a PHP file
	 *
	 * @param array $settings 
	 * @return void
	 * @author Peter Epp
	 */
	private function _cache_system_settings($settings) {
		$settings_cache_file = SITE_ROOT.'/var/cache/system_settings.cache.php';
		$output = <<<PHP
<?php

/**
 * Biscuit System settings - cached from the system_settings table.
 *
 * DO NOT EDIT THIS FILE!! To modify the system settings, login to Biscuit as the super
 * admin and edit the settings from the system admin menu.
 */


PHP;
		foreach ($settings as $setting) {
			$constant_name = $setting->constant_name();
			$value = $setting->value();
			$value = str_replace("'","\'",$value);
			$description = $setting->friendly_name();
			if ($setting->description()) {
				$description .= " - ".$setting->description();
			}
			if (!is_int($value) && !is_float($value)) {
				$value = "'".$value."'";
			}
			$output .= <<<PHP
/**
 * $description
 */
define('$constant_name',$value);


PHP;
		}
		file_put_contents($settings_cache_file,$output);
	}
	/**
	 * Set an error message
	 *
	 * @param string $type 'critical', 'warning', 'error'
	 * @param string $message 
	 * @return void
	 * @author Peter Epp
	 */
	private function set_error($type,$message) {
		$this->_errors[] = array(
			'type'    => $type,
			'message' => $message
		);
	}
	/**
	 * Whether or not the configuration has any sort of errors
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function has_errors() {
		return !empty($this->_errors);
	}
	/**
	 * Whether or not the configuration has critical errors that would cause program failures
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function has_critical_errors() {
		if (!empty($this->_errors)) {
			foreach ($this->_errors as $error) {
				if ($error['type'] == 'critical') {
					return true;
				}
			}
		}
		return false;
	}
	/**
	 * Compile errors of a given type into a string list for output.
	 *
	 * @param string $type 
	 * @param bool $html Optional - Whether or not to output HTML line breaks. Defaults to false.
	 * @return string
	 * @author Peter Epp
	 */
	public function error_output($type, $html = false) {
		if (!empty($this->_errors)) {
			$output_errors = array();
			foreach ($this->_errors as $error) {
				if ($type == 'all' || $error['type'] == $type) {
					if ($html && $error['type'] == 'warning' && SERVER_TYPE == 'PRODUCTION') {
						// Do not output warning HTML on production server
						continue;
					}
					$output_errors[] = AkInflector::humanize($error['type']).': '.$error['message'];
				}
			}
			if (!empty($output_errors)) {
				if ($html) {
					$glue = '</li><li>';
					$prefix = '<ul><li>';
					$suffix = '</li></ul>';
				} else {
					$glue = "\n";
					$prefix = '';
					$suffix = '';
				}
				return $prefix.implode($glue, $output_errors).$suffix;
			}
		}
		return '';
	}
}
?>