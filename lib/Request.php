<?php
/**
 * HTTP request helpers
 *
 * @package Core
 * @author Peter Epp
 */
class Request {
	/**
	 * Return all Apache request headers in an array
	 *
	 * @return array
	 * @author Peter Epp
	 */
	function headers() {
		if (function_exists("getallheaders")) {
			// This only works on Apache with PECL extensions
			return getallheaders();
		}
		else if(!empty($_SERVER)) {
			// Fallback to parsing request headers manually for non Linux/Apache/PECL configurations
			$http_headers = array();
			foreach($_SERVER as $key => $value) {
				if (substr($key,0,5) == "HTTP_") {
					$my_key = strtolower(substr($key,5));		// Grab everything after "HTTP_" in lowercase
					$my_key = preg_replace("'_'","-",$my_key);		// Convert underscores to dashes
					$http_headers[$my_key] = $value;		// Set it in the array
				}
			}
			return $http_headers;
		}
		else {
			// We should never get here.  If we do there's something wrong with the server
			die("No way to read request headers!!!. getallheaders() function is not available and the \$_SERVER array is empty!");
		}
	}
	/**
	 * Return the value of a specific request header. Returns null if the header doesn't exist
	 *
	 * @param string $key The header name to retrieve (eg. "Host", "User-Agent")
	 * @return mixed
	 * @author Peter Epp
	 */
	function header($key) {
		$request_headers = Request::headers();
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
	function is_ping_keepalive() {
		return (Request::is_ajax() && Request::type() == 'ping');
	}
	/**
	 * Return the value of query string ($_GET) variable
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function query_string($index = false) {
		if ($index !== false) {
			if (isset($_GET[$index])) {
				return $_GET[$index];
			}
			return null;
		}
		else {
			if (!empty($_GET)) {
				return $_GET;
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
	function form($index = false) {
		if ($index !== false) {
			if (isset($_POST[$index])) {
				return $_POST[$index];
			}
			return null;
		}
		else {
			if (!empty($_POST)) {
				return $_POST;
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
	function set_form($key,$value) {
		$_POST[$key] = $value;
	}
	/**
	 * Set the value of a query string variable
	 *
	 * @param string $key Key to set a value in
	 * @param string $value Value to set
	 * @return void
	 * @author Peter Epp
	 */
	function set_query($key,$value) {
		$_GET[$key] = $value;
	}
	/**
	 * Unset all or specified $_GET variables
	 *
	 * @param mixed $keys Optional - either a single key name or an array of key names. If omitted, all keys will be unset.
	 * @return void
	 * @author Peter Epp
	 */
	function clear_query($keys = null) {
		if ($keys === null) {
			foreach ($_GET as $key => $value) {
				unset($_GET[$key]);
			}
		}
		else if (is_array($keys)) {
			foreach ($keys as $key) {
				unset($_GET[$key]);
			}
		}
		else if (is_string($keys)) {
			unset($_GET[$keys]);
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
	function clear_form($keys = null) {
		if ($keys === null) {
			foreach ($_POST as $key => $value) {
				unset($_POST[$key]);
			}
		}
		else if (is_array($keys)) {
			foreach ($keys as $key) {
				unset($_POST[$key]);
			}
		}
		else if (is_string($keys)) {
			unset($_POST[$keys]);
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
	function files($key = null) {
		if ($key === null) {
			return $_FILES;
		}
		else if (!empty($_FILES[$key])) {
			return $_FILES[$key];
		}
		return null;
	}
	/**
	 * Is there a set of query string vars?
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	function has_query_string() {
		return (!empty($_GET) && count($_GET) > 0);
	}
	/**
	 * Is there any data posted from a form?
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	function has_form_data() {
		return (!empty($_POST) && count($_POST) > 0);
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
	function type() {
		return (Request::header('X-Biscuit-Request-Type') != null ? Request::header('X-Biscuit-Request-Type') : 'standard');
	}
	/**
	 * Return the request method (ie. "get" or "post")
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function method() {
		return $_SERVER['REQUEST_METHOD'];
	}
	/**
	 * Is the current request over POST?
	 *
	 * @return bool
	 **/
	function is_post() {
		return Request::method() == 'POST';
	}
	/**
	 * Is the current request over ajax?
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	function is_ajax() {
		return (Request::header('X-Biscuit-Ajax-Request') === 'true');
	}
	/**
	 * Return the requested URL relative to the domain
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function uri() {
		return $_SERVER['REQUEST_URI'];
	}
	/**
	 * Return the HTTP referrer, if available
	 *
	 * @return mixed Referrer value if not empty or null
	 * @author Peter Epp
	 */
	function referer() {
		if (!empty($_SERVER['HTTP_REFERER'])) {
			return $_SERVER['HTTP_REFERER'];
		}
		return null;
	}
	/**
	 * Return the port on which the request came in
	 *
	 * @return void
	 * @author Peter Epp
	 */
	
	function port() {
		return $_SERVER['SERVER_PORT'];
	}
	/**
	 * Return host name
	 *
	 * @return string Host name
	 * @author Peter Epp
	 */
	function host() {
		return Request::server('HOST');
	}
	/**
	 * Whether or not the request is a post from a form to an iframe for an ajax-like result
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function is_ajaxy_iframe_post() {
		return (Request::is_post() && Request::form('success_callback') !== null);
	}
	/**
	 * Return the value of any given $_SERVER variable
	 *
	 * @param string $key 
	 * @return void
	 * @author Peter Epp
	 */
	function server($key) {
		if (!empty($_SERVER[$key])) {
			return $_SERVER[$key];
		}
		return null;
	}
	/**
	 * Fetch the value of a cookie
	 *
	 * @param string $key 
	 * @return mixed
	 * @author Peter Epp
	 */
	function cookie($key) {
		if (isset($_COOKIE[$key])) {
			return $_COOKIE[$key];
		}
		return null;
	}
}
?>