<?php
/**
 * HTTP request helpers
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
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
	 * Reference to instance of self
	 *
	 * @var self
	 */
	private static $_instance;
	/**
	 * Put all request vars into local array. Can stuff vars for testing by passing array to constructor
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
		return (Request::is_ajax() && Request::type() == 'ping');
	}
	/**
	 * Whether or not the request is an Ajaxy session refresh
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function is_session_refresh() {
		return (Request::is_ajax() && Request::type() == 'session_refresh');
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
		return (Request::header('X-Biscuit-Request-Type') != null ? Request::header('X-Biscuit-Request-Type') : 'standard');
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
		return strtolower(Request::method()) == 'post';
	}
	/**
	 * Is the current request over ajax?
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public static function is_ajax() {
		return (Request::header('X-Biscuit-Ajax-Request') === 'true');
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
		return Request::header('Host');
	}
	/**
	 * Return the value of the "If-Modified-Since" request header, or null if not present
	 *
	 * @return mixed
	 * @author Peter Epp
	 */
	public static function if_modified_since() {
		return Request::header("If-Modified-Since");
	}
	/**
	 * Return the value of the "If-None-Match" request header, or null if not present
	 *
	 * @return mixed
	 * @author Peter Epp
	 */
	public static function if_none_match() {
		return Request::header('If-None-Match');
	}
	/**
	 * Whether or not the request is a post from a form to an iframe for an ajax-like result
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function is_ajaxy_iframe_post() {
		return (Request::is_post() && Request::form('success_callback') !== null);
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
}
?>