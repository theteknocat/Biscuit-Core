<?php
require_once(dirname(__FILE__)."/../config/system_globals.php");
/**
 * Class for handling site configuration. It will initialize and validate the site's configuration file.
 *
 * @package Core
 * @author Peter Epp
 */
class Configuration {
	/**
	 * Initialize site configuration file
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function initialize() {
		Configuration::load_site_globals();
		Configuration::load_host_settings();
		Configuration::validate();
		Configuration::set_base_urls();
	}
	/**
	 * Load global config for the site, if present
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function load_site_globals() {
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
	function load_host_settings() {
		$hostname = Configuration::host_name();
		$config_file = SITE_ROOT."/config/".$hostname."/config.php";
		if (!file_exists($config_file)) {
			trigger_error("Unable to locate configuration file for host \"".$hostname."\".  To setup a new host configuration, review the instructions at the top of the default configuration file found in /framework/config/default.<br>", E_USER_ERROR);
			exit;
		}
		else {
			require_once($config_file);
		}
	}
	/**
	 * Return either the short or full host name
	 *
	 * @param string $format 
	 * @return void
	 * @author Peter Epp
	 */
	function host_name($format = "short") {
		$full_hostname = $_SERVER['HTTP_HOST'];
		if ($format == "full") {
			return $full_hostname;
		}
		else if ($format == "short") {
			// Remove the "www." from the hostname if present:
			if (substr($full_hostname,0,4) == "www.") {
				$hostname = substr($full_hostname,4);
			}
			else {
				$hostname = $full_hostname;
			}
			return $hostname;
		}
	}
	/**
	 * Set the standard and secure base URLs for the site
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function set_base_urls() {
		if (SSL_DISABLED) {
			$ssl_protocol = "http";
		}
		else {
			$ssl_protocol = "https";
		}
		$full_hostname = Configuration::host_name("full");
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
	function validate() {
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
			trigger_error("Your configuration file is missing the following required constants:<br>".implode(", ",$missing_vars)."<br>", E_USER_ERROR);
			exit;
		}
	}
}
?>