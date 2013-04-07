<?php

/**
 * This is the default configuration file.  It contains all of the global constants needed by the framework.  To create a configuration file for your site,
 * follow these steps:
 *
 * 1) Create a folder in the root of your site called "config"
 * 2) In the config folder, create a sub-folder with the host name of the server it will run from.  Do not include the "www"
 * 3) Copy this file into the host folder and modify it as needed
 */

/*------- REQUIRED constants: -------*/
// If none of these are defined the framework will throw an error on load.

/**
 * Host name of the database server
 */
define('DBHOST', "db_hostname_here");

/**
 * Database username
 */
define('DBUSER', "db_username");

/**
 * Database password
 */
define('DBPASS', "db_password");

/**
 * Name of the database
 */
define('DBNAME', "db_name_here");

/**
 * What level of logging to use.  This affects both the level of PHP error reporting and which console messages get logged.
 *
 * 1 - Only PHP errors of type E_ERROR and framework error messages
 * 2 - All log messages reporting framework operation, framework errors, and all PHP errors except for E_WARNING
 * 3 - All log messages reporting framework operation, framework errors, and all PHP errors
 */
define('LOGGING_LEVEL',3);

/**
 * Debug mode on or off.  Turning it on enables more detailed logging, forces triggered E_USER_ERRORs to email error reporting regardless of what server it's on,
 * and spits out some useful debugging information at the bottom of the page (unless you remove that from your template)
 */
define('DEBUG',true);

/**
 * What type of server is this?  Options are:
 *
 * "LOCAL_DEV" - use this for your local development machine
 * "TESTING" - use this for the staging server
 * "PRODUCTION" - use this for the production server
 *
 * This setting determines whether or not some actions are performed during.  For example, if you have debug mode on for the production server it will prevent display
 * of the debug info at the bottom of the page.
 */
define('SERVER_TYPE','LOCAL_DEV');

/**
 * Whether or not to skip redirecting to HTTPS when a higher than public access page is requested.  This is useful in local development when you don't have an SSL
 * certificate installed.  It also ensures that dynamically generated URL's to pages that are meant to be secure are not prefixed with "https"
 */
define('SSL_DISABLED',false);

/**
 * Prefix for the session cookie name to make it unique to your site
 */
define('SESSION_NAME','Biscuit');

/*------- Optional constants: -------*/
// Any of these constants can be left out. The documentation for each one details the default behaviour when they are not defined.

/**
 * Whether or not you want to include the PEAR library. If not defined it defaults to false.
 */
define("INCLUDE_PEAR",false);

/**
 * Store sessions in database?  If this constant is not defined it defaults to false.
 */
define('USE_DB_SESSIONS',false);

/**
 * Wether or not to use persistent database connection
 */
define("USE_PERSISTENT_DB",false);

/**
 * Add version number to JS and CSS includes. If you don't want to use version numbers remove this constant.
 */
define('JS_AND_CSS_VERSION',1);

/**
 * Email address for the web developer.  This will be used to send detailed error reports.  If this constant is not defined, error reports will not be sent.
 */
define("TECH_EMAIL","yourname@yourdomain.com");

/**
 * Use SMTP for sending mail?  If not defined, it will try to use sendmail instead
 */
define("USE_SMTP_MAIL",false);
/**
 * Host name of the SMTP server.  If this is not defined, SMTP will not be used.
 */
define("SMTP_HOST","");
/**
 * Use SMTP authentication?  If not defined, authentication will not be used.
 */
define("USE_SMTP_AUTH",false);
/**
 * SMTP username.  If not defined, authentication will not be used.
 */
define("SMTP_USERNAME","");
/**
 * SMTP password.  If not defined, authentication will not be used.
 */
define("SMTP_PASSWORD","");

?>