<?php
error_reporting(0);
/**
 * Biscuit Framework
 * 
 * @package Core
 * @version $id$
 * @author Peter Epp
 * @author Lee O'Mara
 * @copyright 2008 Kellett Communications
 * @license TBD
 */
//------------- Include Libraries ---------------\\

require_once(dirname(__FILE__)."/lib/Configuration.php");

Configuration::initialize();

// Request helpers:
require_once("lib/Request.php");

// Response helpers:
require_once("lib/Response.php");

// Session handling libraries:
require_once("lib/SessionStorage.php");
require_once("lib/Session.php");

// Database helpers:
require_once("lib/DB.php");

// Console component (logging and error handling):
require_once("lib/Console.php");

// Recursive helpers:
require_once("lib/Recursive.php");

// Common helpers:
require_once("lib/Crumbs.php");

// Include PEAR library, if available
if (defined("INCLUDE_PEAR") && INCLUDE_PEAR == true && $pear_file = Crumbs::file_exists_in_load_path("PEAR.php")) {
	require_once($pear_file);
	if (DEBUG) {
		Console::log("PEAR was found and included");
	}
}

// Email handler
require_once("lib/Mailer.php");

// Upload handler:
require_once("lib/FileUpload.php");

// Image manipulation library:
require_once("lib/ImageManipulation.php");

// AKInflector library:
require_once("lib/AkInflector.php");

// JSON library, if needed for PHP 4 compatibility:
if (!function_exists("json_encode") || !function_exists("json_decode")) {
	require_once("lib/JSON.php");
}

// Date library that allows dates before 1970 and after 2038 on any OS
require_once("lib/dateclass/date.class.php");

// Permissions library:
require_once("lib/Permissions.php");

// Event manager library:
require_once("lib/EventManager.php");

// Request token helpers (for use on post requests):
require_once("lib/RequestTokens.php");

// Include abstract libraries:
require_once("lib/AbstractObserver.php");
require_once("lib/AbstractModel.php");
require_once("lib/AbstractPlugin.php");
require_once("lib/AbstractPluginController.php");

// Include Biscuit libraries:
require_once("lib/BiscuitModel.php");
require_once("lib/PluginCore.php");
require_once("lib/Biscuit.php");
?>