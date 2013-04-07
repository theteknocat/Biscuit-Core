<?php
/**
 * Biscuit - The Rapid Application Development Framework
 *
 * This framework has been developed by Peter Epp with contributions by Kellett Communications Inc.
 * 
 * This framework is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.1 $Id: bootstrap.php 14357 2011-10-28 22:23:04Z teknocat $
 */

if (version_compare(PHP_VERSION,"5.2.0","<")) {
	die("Biscuit requires PHP 5.2.0 or higher.");
}
// Ensure compatibility with PHP 5.3 or less by making sure deprecated error number constants are defined:
if (!defined('E_DEPRECATED')) {
	define('E_DEPRECATED', 8192);
}
if (!defined('E_USER_DEPRECATED')) {
	define('E_USER_DEPRECATED', 16384);
}

Bootstrap::set_start_time();

spl_autoload_register('Bootstrap::core_lib_autoload');
spl_autoload_register('Bootstrap::extension_auto_load');
spl_autoload_register('Bootstrap::module_auto_load');

/**
 * Handle configuration, loading, migrations and teardown
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.1 $Id: bootstrap.php 14357 2011-10-28 22:23:04Z teknocat $
 */
class Bootstrap {
	/**
	 * Command-line run level. This will expect a command-line argument defining where the site configuration file is located. It will not bother checking
	 * server configuration, existence of directories and will not log anything
	 */
	const COMMAND_LINE = 0;
	/**
	 * Minimum run level - base configuration only, does not load any modules or extensions, nor run migrations
	 */
	const MINIMAL = 1;
	/**
	 * Basic run level - base configuration plus run migrations, if requested
	 */
	const BASIC   = 2;
	/**
	 * Full run level - full configuration and initialization, run migrations if requested and load all modules and extensions
	 */
	const FULL    = 3;
	/**
	 * The current run level
	 *
	 * @var int
	 */
	private static $_run_level;
	/**
	 * Start time of script execution
	 *
	 * @var string
	 */
	private static $_execution_start_time;
	/**
	 * Auto-load core libraries, if they exist, otherwise let PHP deal with it.
	 *
	 * We don't use this to auto-load anything else because it would be more overhead than the existing methods of including
	 * module and extension classes.
	 *
	 * @package Core
	 * @author Peter Epp
	 */
	public static function core_lib_autoload($class_name) {
		if (!class_exists('AkInflector',false)) {
			// This one needs to be included manually if not found since this method needs it in order to find and include classes by naming convention.
			require_once("biscuit-core/ak_inflector.php");
		}
		if (strstr($class_name,"Exception")) {
			$filepath = "biscuit-core/exceptions/".AkInflector::underscore($class_name).".php";
		} else {
			$filepath = "biscuit-core/".AkInflector::underscore($class_name).".php";
		}
		$core_lib = dirname(__FILE__)."/".$filepath;
		if (file_exists($core_lib)) {
			require_once($core_lib);
			return true;
		}
		return false;
	}
	/**
	 * Try to auto load an extension file
	 *
	 * @package Core
	 * @author Peter Epp
	 */
	public static function extension_auto_load($class_name) {
		if (!class_exists('AkInflector',false)) {
			require_once('biscuit-core/ak_inflector.php');
		}
		$extension_path = AkInflector::underscore($class_name).'/extension.php';
		if ($full_file_path = Crumbs::file_exists_in_load_path($extension_path)) {
			require_once($full_file_path);
			return true;
		}
		return false;
	}
	/**
	 * Try to auto load a module controller file
	 *
	 * @package Core
	 * @author Peter Epp
	 */
	public static function module_auto_load($class_name) {
		if (!class_exists('AkInflector',false)) {
			require_once('biscuit-core/ak_inflector.php');
		}
		$module_classname = Crumbs::normalized_module_name($class_name);
		$module_path    = AkInflector::underscore($module_classname).'/controller.php';
		if (substr($class_name,0,6) == 'Custom') {
			// If the class name begins with "Custom", look explicitly for the custom controller. Otherwise, when the Crumbs::module_classname() method is called
			// it can end up return a Custom classname when it's not actually customized, since that's the first class name it checks for
			$module_path = 'customized/'.$module_path;
		}
		if ($full_file_path = Crumbs::file_exists_in_load_path($module_path)) {
			if (preg_match('/\/customized\//',$full_file_path)) {
				// If module controller file is a customized one, we need to load the parent controller, so figure out the parent file path and include it

				// Make full path site root relative without preceding slash:
				$parent_file = substr($full_file_path,strlen(SITE_ROOT)+1);
				// Remove "customized":
				$parent_file = preg_replace('/\/customized\//','/',$parent_file);
				// Get the full path to the parent:
				$full_parent_file_path = Crumbs::file_exists_in_load_path($parent_file);
				// Include the parent controller file:
				require_once $full_parent_file_path;
			}
			require_once($full_file_path);
			return true;
		}
		return false;
	}
	/**
	 * Load and configure the framework at different levels:
	 *
	 * BOOTSTRAP_MIN - Base configuration only, does not load any modules or extensions, nor run migrations
	 * BOOTSTRAP_BASIC - Base configuration plus run migrations, if requested
	 * BOOTSTRAP_FULL (default) - Full configuration and initialization, run migrations if requested and load all modules and extensions
	 *
	 * @param string $level 
	 * @return void
	 * @author Peter Epp
	 */
	public static function load($level = self::FULL) {
		// Set a default timezone to avoid warnings when using date functions prior to setting the timezone base on the site configuration
		date_default_timezone_set('America/Edmonton');
		
		self::$_run_level = $level;

		$host_name = null;
		if ($level == self::COMMAND_LINE) {
			global $argv;
			$arguments = $argv;
			// Look for host name in arguments:
			array_shift($arguments);	// Get rid of the script name which is always passed as the first argument
			if (!empty($arguments)) {
				// For now the only argument we're interested in is the host-name to pass to the configuration class
				foreach ($arguments as $argument) {
					if (substr($argument,0,11) == '--host-name') {
						$host_name = substr($argument,12);
						$host_name = preg_replace('/http:\/\//','',$host_name);
					}
				}
			}
			if (empty($host_name)) {
				echo "Error. To load Biscuit from the command line you must pass it the host name as an argument in the format --host-name=[http://]domain.com\n";
				self::end_program(true);
			}
		}

		$Config = Configuration::load($host_name);

		Console::log_request_markers(Request::uri());

		Console::log("Initialization:");

		if ($level != self::COMMAND_LINE) {
			Console::log("    Configure error handling");
			Console::set_err_level();
			Console::set_err_handler();
		}

		if ($level != self::COMMAND_LINE) {
			$Config->check_server_config();
		}

		if (!$Config->has_critical_errors()) {
			Console::log("    Initiate database connection");
			DB::connect();

			if ($level >= self::BASIC) {
				self::run_migrations();
			}

			Console::log("    Define global system settings");

			$Config->define_system_settings();

			Crumbs::set_timezone();

			Console::log("    Current System Time: ".date("r"));

		}

		$allow_db_sessions = ($level >= self::BASIC && !$Config->has_critical_errors());

		Console::log("    Start session");

		Session::start($allow_db_sessions);

		Console::log("    Set Locale");

		if ($Config->has_critical_errors()) {
			trigger_error("Problems with configuration are preventing Biscuit from loading:<br>".$Config->error_output('critical', ($level != self::COMMAND_LINE)),E_USER_ERROR);
			return false;
		}

		I18n::instance()->set_locale();

		if ($Config->has_errors()) {
			$config_error_output = $Config->error_output('all', ($level != self::COMMAND_LINE));
			if (!empty($config_error_output)) {
				$message = "<p>Some problems with your configuration were detected:</p>".$config_error_output;
				Session::flash("user_error", $message);
			}
			if (SERVER_TYPE == 'PRODUCTION') {
				Console::log("Configuration problems exist:\n".$Config->error_output('all', false), Console::FORCE_LOG);
			}
		}

		if ($level == self::FULL) {
			Console::log("    Start Biscuit");
			$Biscuit = Biscuit::instance();
			$Biscuit->register_configuration($Config);
			return $Biscuit;
		}
		return true;
	}
	/**
	 * Run migrations if requested
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function run_migrations() {
		if (Request::query_string("run_migrations") == 1) {
			Console::log("    Running migrations...");
			Migrations::run();
			$redirect_to = preg_replace("/\?.+/","",Request::uri());
			Response::redirect($redirect_to);
		}
	}
	/**
	 * Do any wrap up and call exit
	 *
	 * @param bool $full_run_complete Whether or not Biscuit ran all the way through before exiting. This is just for logging purposes, and should only be set to true at the end of core.php.
	 * @return void
	 * @author Peter Epp
	 */
	public static function end_program($full_run_complete = false, $retain_flash_vars = false) {
		if (self::$_run_level >= self::BASIC) {
			// Fire the shutdown event
			Event::fire("shutdown");
		}
		// Disconnect from the database:
		DB::close();
		if (self::$_run_level >= self::BASIC) {
			if (!$full_run_complete) {
				Console::log("\nBiscuit was asked to exit prior to completing it's run.\n");
			} else if (!$retain_flash_vars) {
				Session::flash_empty();
			}
			self::log_execution_time();
		}
		exit;
	}
	/**
	 * Set the starting time of script execution
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function set_start_time() {
		self::$_execution_start_time = microtime(true);
	}
	/**
	 * Record total execution time in the console log
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private static function log_execution_time() {
		$current_time = microtime(true);
		$total_time = $current_time - self::$_execution_start_time;
		Console::log("Execution Time: ".$total_time." seconds");
	}
	/**
	 * Return the current run level
	 *
	 * @author Peter Epp
	 */
	public static function run_level() {
		return self::$_run_level;
	}
	/**
	 * Whether or not the current run level is of browser level (ie. not command-line)
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public static function is_browser_run_level() {
		return self::$_run_level > self::COMMAND_LINE;
	}
}
?>