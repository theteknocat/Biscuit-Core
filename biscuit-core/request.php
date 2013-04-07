<?php
/**
 * HTTP request helpers. Handles all Request related functions including parsing/cleaning user input and providing an internal rewrite engine
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: request.php 14744 2012-12-01 20:50:43Z teknocat $
 */
class Request implements Singleton {
	/**
	 * Place to store request variables (GET, POST, _FILES and SERVER)
	 *
	 * @var array
	 */
	private $_request_vars = array(
		'_GET'    => array(),
		'_POST'   => array(),
		'_FILES'  => array(),
		'_SERVER' => array()
	);
	/**
	 * Cached request headers
	 *
	 * @var array
	 */
	private $_headers = array();
	/**
	 * The parsed query array
	 *
	 * @var array
	 */
	private $_mapped_uri_vars = array();
	/**
	 * Current request slug
	 *
	 * @var string
	 */
	private $_request_slug = 'index';
	/**
	 * Un-sanitized (raw, dirty) user input
	 *
	 * @var array
	 */
	private $_raw_user_input = array();
	/**
	 * Clean/sanitized user input
	 *
	 * @var array
	 */
	private $_user_input = array();
	/**
	 * Reference to instance of self
	 *
	 * @var self
	 */
	private static $_instance;
	/**
	 * Put all request vars into local array. Can stuff vars for testing by calling the appropriate Request::stuff method
	 *
	 * @author Peter Epp
	 */
	private function __construct() {
		if (!empty($_GET)) {
			$this->_request_vars['_GET'] = $_GET;
		}
		if (!empty($_POST)) {
			$this->_request_vars['_POST'] = $_POST;
		}
		if (!empty($_FILES)) {
			$this->_request_vars['_FILES'] = $_FILES;
		}
		if (!empty($_SERVER)) {
			$this->_request_vars['_SERVER'] = $_SERVER;
		}
	}
	/**
	 * Instantiate singleton of self
	 *
	 * @return self
	 * @author Peter Epp
	 */
	public static function instance() {
		if (empty(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
	 * Reset the request by destroying the instance of self so a new request can be instantiated on the next method call, primarily for testing purposes
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function reset() {
		self::$_instance = null;
	}
	/**
	 * Return all Apache request headers in an array
	 *
	 * @return array
	 * @author Peter Epp
	 */
	public static function headers() {
		$me = self::instance();
		if(empty($me->_request_vars['_SERVER'])) {
			trigger_error("NO \$_SERVER GLOBAL FOUND!", E_USER_ERROR);
			return;
		}
		if (empty($me->_headers)) {
			foreach($me->_request_vars['_SERVER'] as $key => $value) {
				if (substr($key,0,5) == "HTTP_") {
					// Turn all-caps underscored variable key into hyphenized-camelized key:
					$my_key = str_replace(" ","-",AKInflector::titleize(strtolower(substr($key,5))));
					if (get_magic_quotes_gpc()) {
						$value = stripslashes($value);
					}
					$me->_headers[$my_key] = $value;		// Set it in the array
				}
			}
		}
		return $me->_headers;
	}
	/**
	 * Return the value of a specific request header. Returns null if the header doesn't exist
	 *
	 * @param string $key The header name to retrieve (eg. "Host", "User-Agent")
	 * @return mixed
	 * @author Peter Epp
	 */
	public static function header($key) {
		$request_headers = self::headers();
		foreach ($request_headers as $hkey => $value) {
			if (strtolower($hkey) == strtolower($key)) {		// Do a case-insensitive search on the key because some browsers send custom headers with all lowercase keys (IE), where others send them with the keys in their original case
				return $value;
			}
		}
		return null;
	}
	/**
	 * Whether or not it's a ping to keep the session alive
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public static function is_ping_keepalive() {
		return (self::is_ajax() && self::type() == 'ping');
	}
	/**
	 * Whether or not the request is an Ajaxy session refresh
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function is_session_refresh() {
		return (self::is_ajax() && self::type() == 'session_refresh');
	}
	/**
	 * Return the value of query string ($_GET) variable
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function query_string($index = false) {
		$me = self::instance();
		if ($index !== false) {
			if (isset($me->_request_vars['_GET'][$index])) {
				return $me->_request_vars['_GET'][$index];
			}
			return null;
		}
		else {
			if (!empty($me->_request_vars['_GET'])) {
				return $me->_request_vars['_GET'];
			}
			return null;
		}
	}
	/**
	 * Return the value of a form post variable
	 *
	 * @param string $index 
	 * @return void
	 * @author Peter Epp
	 */
	public static function form($index = false) {
		$me = self::instance();
		if ($index !== false) {
			if (isset($me->_request_vars['_POST'][$index])) {
				return $me->_request_vars['_POST'][$index];
			}
			return null;
		}
		else {
			if (!empty($me->_request_vars['_POST'])) {
				return $me->_request_vars['_POST'];
			}
			return null;
		}
	}
	/**
	 * Set the value of a form variable
	 *
	 * @param string $key Key to set a value in
	 * @param string $value Value to set
	 * @return void
	 * @author Peter Epp
	 */
	public static function set_form($key,$value) {
		$me = self::instance();
		$me->_request_vars['_POST'][$key] = $value;
	}
	/**
	 * Set the value of a query string variable
	 *
	 * @param string $key Key to set a value in
	 * @param string $value Value to set
	 * @return void
	 * @author Peter Epp
	 */
	public static function set_query($key,$value) {
		$me = self::instance();
		$me->_request_vars['_GET'][$key] = $value;
	}
	/**
	 * Unset all or specified $_GET variables
	 *
	 * @param mixed $keys Optional - either a single key name or an array of key names. If omitted, all keys will be unset.
	 * @return void
	 * @author Peter Epp
	 */
	public static function clear_query($keys = null) {
		$me = self::instance();
		if ($keys === null) {
			foreach ($me->_request_vars['_GET'] as $key => $value) {
				unset($me->_request_vars['_GET'][$key]);
			}
		}
		else if (is_array($keys)) {
			foreach ($keys as $key) {
				unset($me->_request_vars['_GET'][$key]);
			}
		}
		else if (is_string($keys)) {
			unset($me->_request_vars['_GET'][$keys]);
		}
		else {
			return false;
		}
	}
	/**
	 * Unset all or specified $_POST variables
	 *
	 * @param mixed $keys Optional - either a single key name or an array of key names. If omitted, all keys will be unset.
	 * @return void
	 * @author Peter Epp
	 */
	public static function clear_form($keys = null) {
		$me = self::instance();
		if ($keys === null) {
			foreach ($me->_request_vars['_POST'] as $key => $value) {
				unset($me->_request_vars['_POST'][$key]);
			}
		}
		else if (is_array($keys)) {
			foreach ($keys as $key) {
				unset($me->_request_vars['_POST'][$key]);
			}
		}
		else if (is_string($keys)) {
			unset($me->_request_vars['_POST'][$keys]);
		}
		else {
			return false;
		}
	}
	/**
	 * Return either the entire $_FILES array or just one key in the $_FILES array
	 *
	 * @param string $key Optional - the key to retrieve
	 * @return mixed
	 * @author Peter Epp
	 */
	public static function files($key = null) {
		$me = self::instance();
		if ($key === null) {
			return $me->_request_vars['_FILES'];
		}
		else if (!empty($me->_request_vars['_FILES'][$key])) {
			return $me->_request_vars['_FILES'][$key];
		}
		return null;
	}
	/**
	 * Is there a set of query string vars?
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public static function has_query_string() {
		$me = self::instance();
		return (!empty($me->_request_vars['_GET']) && count($me->_request_vars['_GET']) > 0);
	}
	/**
	 * Is there any data posted from a form?
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public static function has_form_data() {
		$me = self::instance();
		return (!empty($me->_request_vars['_POST']) && count($me->_request_vars['_POST']) > 0);
	}
	/**
	 * Return the request type. Request types in Biscuit are primarily for determining whether or not it should render output. The two types that it checks when asked
	 * to render are:
	 *
	 * standard - normal page request, noting special
	 * update - an Ajax update request
	 *
	 * You can submit any other value you want in the request_type parameter with any page request and use it however you want with plugins that perform actions that do
	 * not need to render any output.
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public static function type() {
		return (self::header('X-Biscuit-Request-Type') != null ? self::header('X-Biscuit-Request-Type') : 'standard');
	}
	/**
	 * Return the request method (ie. "get" or "post")
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function method() {
		$me = self::instance();
		return $me->_request_vars['_SERVER']['REQUEST_METHOD'];
	}
	/**
	 * Is the current request over POST?
	 *
	 * @return bool
	 **/
	public static function is_post() {
		return strtolower(self::method()) == 'post';
	}
	/**
	 * Is the current request over ajax?
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public static function is_ajax() {
		return (self::header('X-Biscuit-Ajax-Request') === 'true');
	}
	/**
	 * Whether or not the current request is for JSON data
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function is_json() {
		return (self::type() == 'json');
	}
	/**
	 * Whether or not the current request is a fire and forget ("server_action")
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function is_fire_and_forget() {
		return (self::type() == 'server_action');
	}
	/**
	 * Return the requested URL relative to the domain
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function uri() {
		$me = self::instance();
		return $me->_request_vars['_SERVER']['REQUEST_URI'];
	}
	/**
	 * Return the HTTP referrer, if available
	 *
	 * @return mixed Referrer value if not empty or null
	 * @author Peter Epp
	 */
	public static function referer() {
		$me = self::instance();
		if (!empty($me->_request_vars['_SERVER']['HTTP_REFERER'])) {
			return $me->_request_vars['_SERVER']['HTTP_REFERER'];
		}
		return null;
	}
	/**
	 * Return the port on which the request came in
	 *
	 * @return void
	 * @author Peter Epp
	 */
	
	public static function port() {
		$me = self::instance();
		return $me->_request_vars['_SERVER']['SERVER_PORT'];
	}
	/**
	 * Return host name
	 *
	 * @return string Host name
	 * @author Peter Epp
	 */
	public static function host() {
		return self::header('Host');
	}
	/**
	 * Return the value of the "If-Modified-Since" request header, or null if not present
	 *
	 * @return mixed
	 * @author Peter Epp
	 */
	public static function if_modified_since() {
		return self::header("If-Modified-Since");
	}
	/**
	 * Return the value of the "If-None-Match" request header, or null if not present
	 *
	 * @return mixed
	 * @author Peter Epp
	 */
	public static function if_none_match() {
		return self::header('If-None-Match');
	}
	/**
	 * Whether or not the request is a post from a form to an iframe for an ajax-like result
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function is_ajaxy_iframe_post() {
		return (self::is_post() && self::form('success_callback') !== null);
	}
	/**
	 * Return the value of any given $_SERVER variable
	 *
	 * @param string $key 
	 * @return void
	 * @author Peter Epp
	 */
	public static function server($key) {
		$me = self::instance();
		if (!empty($me->_request_vars['_SERVER'][$key])) {
			return $me->_request_vars['_SERVER'][$key];
		}
		return null;
	}
	/**
	 * Stuff the GET array with values
	 *
	 * @param array $vars 
	 * @return void
	 * @author Peter Epp
	 */
	public static function stuff_get($vars) {
		self::stuff(array('_GET' => $vars));
	}
	/**
	 * Stuff the POST array with values
	 *
	 * @param array $vars 
	 * @return void
	 * @author Peter Epp
	 */
	public static function stuff_post($vars) {
		self::stuff(array('_POST' => $vars));
	}
	/**
	 * Stuff a specific request header with a value
	 *
	 * @param string $name 
	 * @param string $value 
	 * @return void
	 * @author Peter Epp
	 */
	public static function stuff_header($name,$value) {
		$me = self::instance();
		$var_name = 'HTTP_'.str_replace('-','_',strtoupper($name));
		// Stuff it into the SERVER vars in case headers haven't been called for yet:
		$me->_request_vars['_SERVER'][$var_name] = $value;
		// Also stuff directly into the headers property in case headers method has been called once, which means the headers won't be re-populated
		// from the server vars when called for:
		$me->_headers[$name] = $value;
	}
	/**
	 * Stuff the request URI with a specified value
	 *
	 * @param string $uri 
	 * @return void
	 * @author Peter Epp
	 */
	public static function stuff_uri($uri) {
		$me = self::instance();
		$me->_request_vars['_SERVER']['REQUEST_URI'] = $uri;
	}
	/**
	 * Stuff the request with any desired variables (_GET, _POST or _SERVER)
	 *
	 * @param array $vars 
	 * @return void
	 * @author Peter Epp
	 */
	public static function stuff($vars) {
		$me = self::instance();
		if (!empty($vars)) {
			foreach ($vars as $key => $values) {
				if ($key == '_GET' || $key == '_POST') {
					foreach ($values as $sub_key => $value) {
						$me->_request_vars[$key][$sub_key] = $value;
					}
				}
			}
		}
	}
	/**
	 * Parse the request and set request params/arguments
	 *
	 * @param array $module_rewrite_rules Array of rewrite rules for the modules
	 * @return void
	 * @author Peter Epp
	 */
	public static function map_uri($module_mapping_rules = array()) {
		Console::log("Mapping request URI to variables");
		$me = self::instance();
		$request_uri = trim(I18n::instance()->request_uri_without_locale(),'/');
		if (substr($request_uri,0,10) == 'index.php/') {
			// Servers that don't have Apache rewrite may include /index.php at the start of the URI, so if that's the case strip it out
			$request_uri = substr($request_uri,10);
		}
		if (preg_match('/\?/',$request_uri)) {
			// Remove the query string, if present
			$request_uri = substr($request_uri,0,strpos($request_uri,'?'));
		}
		$request_uri = trim($request_uri,'/');
		if (!empty($request_uri)) {
			$mapping_rules = array_merge($module_mapping_rules, self::base_mapping_rules());
			foreach ($mapping_rules as $rule_key => $rule_pattern) {
				if (preg_match($rule_pattern, $request_uri, $matches)) {
					// Keep only the values with string keys from the matches:
					foreach ($matches as $match_key => $value) {
						if (is_string($match_key)) {
							$me->_mapped_uri_vars[$match_key] = $value;
						}
					}
					if (is_string($rule_key)) {
						// If the rule key is a string, parse it as a query string. This is to allow additional special variables to be set that may
						// not map in the request URI
						parse_str($rule_key, $extra_vars);
						$me->_mapped_uri_vars = array_merge($me->_mapped_uri_vars, $extra_vars);
					}
					break;
				}
			}
			if (!empty($me->_mapped_uri_vars['page_slug'])) {
				$me->_request_slug = $me->_mapped_uri_vars['page_slug'];
				unset($me->_mapped_uri_vars['page_slug']);
			} else if (!empty($request_uri)) {
				// If the URI failed to map to anything and the request URI is not empty, show 404 not found
				$me->_request_slug = 'error404';
			}
		}
		self::set_user_input();
	}
	/**
	 * Return the current request slug
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public static function slug() {
		$me = self::instance();
		return $me->_request_slug;
	}
	/**
	 * Process all user provided GET and POST variables that were left after processing the page-related variables. Store the raw data in one array, and the un-escaped data in another.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function set_user_input() {
		$me = self::instance();
		$raw_user_input = array();
		// Set the page slug from form input or query string if provided. These get to stomp over whatever may have been set when parsing the request URI
		// for cases where you want to override.
		if (self::form('page_slug')) {
			$me->_request_slug = self::form('page_slug');
		} else if (self::query_string('page_slug')) {
			$me->_request_slug = self::query_string('page_slug');
		}
		if (empty($me->_request_slug)) {
			Console::log("        No page provided in post or query string, using \"index\"");
			$me->_request_slug = 'index';
		}

		// Ensure that 'page' and the odd underscore param added bp Prototype Ajax.Updater() are cleared
		self::clear_query(array('page_slug','_'));
		self::clear_form(array('page_slug','_'));

		if (Session::var_exists('user_input')) {
			$raw_user_input = array_merge(Session::get('user_input'),$raw_user_input);
			Session::unset_var('user_input');
		}
		if (!empty($me->_mapped_uri_vars)) {
			$raw_user_input = array_merge($me->_mapped_uri_vars, $raw_user_input);
		}
		if (self::has_query_string()) {
			$raw_user_input = array_merge(self::query_string(), $raw_user_input);
		}
		if (self::has_form_data()) {
			$raw_user_input = array_merge(self::form(), $raw_user_input);
		}

		$me->_raw_user_input = $raw_user_input;
		$me->_user_input = Crumbs::clean_input($raw_user_input);
	}
	/**
	 * Return the raw/unsanitized user input
	 *
	 * @return array|null
	 * @param string|null $key Optional. Name of the input key to return the value of. If omitted, entire input array is returned
	 * @author Peter Epp
	 */
	public static function dirty_user_input($key = null) {
		$me = self::instance();
		if (!empty($me->_raw_user_input)) {
			if (empty($key)) {
				return $me->_raw_user_input;
			} else if (!empty($key) && isset($me->_raw_user_input[$key])) {
				return $me->_raw_user_input[$key];
			}
		}
		return null;
	}
	/**
	 * Return the clean/sanitized user input, either the whole array or a specified key
	 *
	 * @return array|null
	 * @param string|null $key Optional. Name of the input key to return the value of. If omitted, entire input array is returned
	 * @author Peter Epp
	 */
	public static function user_input($key = null) {
		$me = self::instance();
		if (!empty($me->_user_input)) {
			if (empty($key)) {
				return $me->_user_input;
			} else if (!empty($key) && isset($me->_user_input[$key])) {
				return $me->_user_input[$key];
			}
		}
		return null;
	}
	/**
	 * Return an array of the base rewrite rules
	 *
	 * @return array
	 * @author Peter Epp
	 */
	private static function base_mapping_rules() {
		return array(
			'page_slug=index&js_translations_request=1' => '/var\/cache\/js\/Messages(_([a-z]{2,3})(_([A-Z]{2,3}))?)?\.properties/',
			'/^ping\/(?P<ping_time>[0-9]+)$/',
			'/^(?P<page_slug>captcha)\/[0-9]+$/',
			'/^(?P<page_slug>[^\.]+)\/(?P<action>show_[a-z_]+)\/(?P<id>[0-9]+)\/.+$/',
			'/^(?P<page_slug>[^\.]+)\/(?P<action>show)\/(?P<id>[0-9]+)\/.+$/',
			'/^(?P<page_slug>[^\.]+)\/(?P<action>(show|new|edit|delete)_[a-z_]+)\/(?P<id>[0-9]+)$/',
			'/^(?P<page_slug>[^\.]+)\/(?P<action>(new|index|resort)_[a-z_]+)$/',
			'/^(?P<page_slug>[^\.]+)\/(?P<action>show|new|edit|delete)\/(?P<id>[0-9]+)$/',
			'/^(?P<page_slug>[^\.]+)\/(?P<action>new|resort)$/',
			'/^(?P<page_slug>[^\.]+)$/'
		);
	}
}
