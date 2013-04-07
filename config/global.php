<?php
/**
 * Site global configuration file.  Copy this file into your site's config folder
 */

/**
 * Prefix for the session cookie name to make it unique to your site
 */
define('SESSION_NAME','Biscuit');

/*------- Optional constants: -------*/
// Any of these constants can be left out. The documentation for each one details the default behaviour when they are not defined.

/**
 * Whether or not you want to include the PEAR library. If not defined it defaults to false. Note that if the PHP libs path is not in your PHP include
 * path by default you may need to add it to the include path, which you can do at the bottom of this file. In addition, if PEAR is not in the root
 * of the php libs path you'll also need to add the PEAR path to your include path as well.  Some servers may also require some additional configuration
 * to include PEAR (MediaTemple's DV servers, for example). Please see your server's documentation for details on how to make it available.
 */
define("INCLUDE_PEAR",false);

/**
 * Store sessions in database?  If this constant is not defined it defaults to false.
 */
define('USE_DB_SESSIONS',false);

/**
 * Add version number to JS and CSS includes. If you don't want to use version numbers remove this constant.
 */
define('JS_AND_CSS_VERSION',1);

/**
 * Email address for the web developer.  This will be used to send detailed error reports.  If this constant is not defined, error reports will not be sent.
 */
define("TECH_EMAIL","yourname@yourdomain.com");

?>