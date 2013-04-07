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
 * Path to customized extension files
 */
define('CUSTOMIZED_EXTENSION_PATH',SITE_ROOT.'/extensions/customized');
/**
 * Path to regular extension files
 */
define('REGULAR_EXTENSION_PATH',SITE_ROOT.'/extensions');
/**
 * Path to framework extension files
 */
define('FW_EXTENSION_PATH',FW_ROOT.'/extensions');
/**
 * Path to customized module files
 */
define('CUSTOMIZED_MODULE_PATH',SITE_ROOT.'/modules/customized');
/**
 * Path to regular module files
 */
define('REGULAR_MODULE_PATH',SITE_ROOT.'/modules');
/**
 * Path to framework module files
 */
define('FW_MODULE_PATH',FW_ROOT.'/modules');
/**
 * Path to the temporary folder.  This folder will be created on first run if it doesn't exist
 */
define('TEMP_DIR',SITE_ROOT."/tmp");
/**
 * The format for GMT dates
 */
define('GMT_FORMAT', 'D, d M Y H:i:s \G\M\T');
/**
 * Value used for "parent" column in page_index table to indicated an orphan page that should not be included in menus
 */
define('HIDDEN_ORPHAN_PAGE',9999999);
/**
 * Value used for "parent" column in page_index table to indicate an orphan page
 */
define('NORMAL_ORPHAN_PAGE',999999);

// Set PHP include path:
$include_path = CUSTOMIZED_EXTENSION_PATH . PATH_SEPARATOR .
	REGULAR_EXTENSION_PATH . PATH_SEPARATOR .
	FW_EXTENSION_PATH . PATH_SEPARATOR .
	CUSTOMIZED_MODULE_PATH . PATH_SEPARATOR .
	REGULAR_MODULE_PATH . PATH_SEPARATOR .
	FW_MODULE_PATH . PATH_SEPARATOR .
	SITE_ROOT . PATH_SEPARATOR .
	FW_ROOT . PATH_SEPARATOR .
	get_include_path();

set_include_path($include_path);
?>