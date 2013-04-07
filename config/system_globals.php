<?php
/**
 * These are settings that apply system wide and need to be set before anything else.
 *
 */

/**
 * Path to the site root
 */
define('SITE_ROOT',realpath(dirname(__FILE__)."/../.."));
/**
 * Path to the framework root
 */
define('FW_ROOT',SITE_ROOT.'/framework');
/**
 * Path to the temporary folder.  This folder will be created on first run if it doesn't exist
 */
define('TEMP_DIR',FW_ROOT."/tmp");
// Set PHP include path:
$include_paths = SITE_ROOT.":".FW_ROOT;
ini_set("include_path",$include_paths.":".ini_get("include_path"));		// Prepend to default include path

?>