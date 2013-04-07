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
 * @version 2.0
 */

if (version_compare(PHP_VERSION,"5.2.0","<")) {
	trigger_error("Biscuit 2.0 Requires PHP 5.2.0 or higher.", E_USER_ERROR);
}

Bootstrap::set_start_time();

spl_autoload_register('Bootstrap::core_lib_autoload');

/**
 * Handle configuration, loading, migrations and teardown
 *
 * @package Core
 * @author Peter Epp
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
			require_once("lib/ak_inflector.php");
		}
		if (strstr($class_name,"Exception")) {
			$filepath = "lib/exceptions/".AkInflector::underscore($class_name).".php";
		} else {
			$filepath = "lib/".AkInflector::underscore($class_name).".php";
		}
		$core_lib = dirname(__FILE__)."/".$filepath;
		if (file_exists($core_lib)) {
			require_once($core_lib);
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

			Console::log("    Start session");
		}

		$allow_db_sessions = ($level >= self::BASIC && !$Config->has_critical_errors());

		Session::start($allow_db_sessions);

		if ($Config->has_critical_errors()) {
			trigger_error("Problems with configuration are preventing Biscuit from loading:<br>".$Config->error_output('critical', true),E_USER_ERROR);
		}

		if ($Config->has_errors()) {
			$message = "<p>Some problems with your configuration were detected:</p>".$Config->error_output('all', true);
			Session::flash("user_error", $message);
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