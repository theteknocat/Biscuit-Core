<?php
/**
 * Browser response helpers
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: response.php 14559 2012-03-06 19:15:40Z teknocat $
 */
class Response {
	/**
	 * List of all HTTP status codes and messages used by the framework.  Add to this list as needed.
	 *
	 * @author Peter Epp
	 */
	private static $_http_codes = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Request Range Not Satisfiable',
		417 => 'Expectation Failed',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported'
	);
	/**
	 * Associative array of headers to send at render time
	 *
	 * @author Peter Epp
	 */
	private static $_headers = array();
	/**
	 * HTTP status for response
	 *
	 * @author Peter Epp
	 */
	private static $_http_status = 200;
	/**
	 * The content type of the response
	 *
	 * @author Peter Epp
	 */
	private static $_content_type = 'text/html; charset=utf-8';
	/**
	 * This var is used to keep track of whether or not headers have been sent.  The reason for this internal tracking is because the PHP headers_sent() function
	 * returns false if output has not yet started even if headers have in fact been sent to the browser. This way we can be absolutely sure of whether or not
	 * headers have been sent whether output has been started or not, which is important for cases like when an error occurs in the middle of rendering to the
	 * output buffer, in which case it needs to render a script tag to redirect to the error 500 page as opposed to rendering it inline or using a PHP redirect.
	 *
	 * @author Peter Epp
	 */
	private static $_headers_sent = false;
	/**
	 * Cookies that need to be set when headers are sent
	 *
	 * @author Peter Epp
	 */
	private static $_cookies = array();
	/**
	 * Set a response header
	 *
	 * @param string $header_string The full header string to set (eg. "Content-type: application/pdf")
	 * @return void
	 * @author Peter Epp
	 */
	public static function header($header_string) {
		header($header_string);
	}
	/**
	 * Add a custom header to send at render time
	 *
	 * @param string $header The header 
	 * @return void
	 * @author Peter Epp
	 */
	public static function add_header($header_name,$value) {
		self::$_headers[$header_name] = $value;
	}
	/**
	 * Whether or not response headers have been sent
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function headers_sent() {
		return (headers_sent() || self::$_headers_sent);
	}
	/**
	 * Redirect to another page on the site
	 *
	 * @param string $page Name of the page to redirect to.  This must be the full path to the page from the site root.
	 * @return void
	 * @author Peter Epp
	 */
	
	public static function redirect($page, $permanently_moved = false) {
		Console::log("Redirect request: ".$page);
		// Page redirection.  Use this static function to redirect to a page in the current site.
		// If you want to redirect to a page on another site that you have the full URL for, this static function is not
		// needed, and you can just use "header('Location: '.$myurl)" instead
		if (!stristr($page,'http://') && !stristr($page,'https://')) {
			$schema = Request::port() == '443' ? 'https' : 'http';
			$url = $schema.'://'.Request::host().$page;
		}
		else {
			$url = $page;
		}
		if (Request::is_ajax() && Request::type() != 'update') {
			self::content_type("application/json");
			self::add_header("X-JSON","true");
			self::send_headers();
			self::write(Crumbs::to_json(array('redirect_page' => $url)));
		} else if (Request::is_ajax() || Request::is_ajaxy_iframe_post()) {
			self::send_cookies();
			Console::log("    AJAX Redirect to: $url");
			self::write('<script type="text/javascript">top.location.href = "'.$url.'";</script>');
		} else {
			if (self::headers_sent()) {
				Console::log("    Redirect failed because headers have already been sent!");
				return false;
			} else {
				Console::log("    Full redirect URL: $url");
				if ($permanently_moved) {
					self::header("HTTP/1.1 301 ".self::$_http_codes[301]);
				} else {
					self::header("HTTP/1.1 303 ".self::$_http_codes[303]);
				}
				self::header("X-Powered-By: Biscuit MVC Framework 2.0");
				if (Request::is_post()) {
					$last_modified = gmdate(GMT_FORMAT);
					$expires = gmdate(GMT_FORMAT,(time()-((60*60*24)*365)*10));
					self::header('Last-Updated: '.$last_modified);
					self::header("ETag: ".sha1($last_modified));
					self::header('Expires: '.$expires);
					self::header('Pragma: no-cache');
					self::header('Cache-Control: no-cache, no-store, must-revalidate, post-check=0, pre-check=0');
				}
				self::header("Location: ".$url);
				self::send_cookies();
				if (Request::method() != 'HEAD') {
					echo 'Please see: <a href="'.$url.'">'.$url.'</a>';
				}
			}
		}
		Bootstrap::end_program(true, true);
	}
	/**
	 * Set the HTTP headers for the current page based on the HTTP status, access level and server port. Sets HTTP status of the page (200, 403, 404) based on the $http_status property of the web page object, and sets cache control to private for SSL connections and pages with a non-public access level.
	 *
	 * @author Peter Epp
	 */
	public static function send_headers($access_level = 0) {
		Console::log("    Sending response headers.");
		$content_type = "Content-Type: ".self::$_content_type;
		Console::log("        ".$content_type);
		self::header($content_type);
		$headers = self::$_headers;
		if (!empty($headers)) {
			foreach ($headers as $header_name => $value) {
				$header_string = $header_name.": ".$value;
				Console::log("        ".$header_string);
				self::header($header_string);
			}
		}
		if ($access_level > 0 || Request::port() == "443") {
			if (empty($headers['Cache-Control'])) {
				self::header("Cache-Control: private, must-revalidate, proxy-revalidate, max-age=0");
			}
		} else {
			if (empty($headers['Cache-Control'])) {
				self::header("Cache-Control: public, must-revalidate, proxy-revalidate, max-age=0");
			}
		}
		self::header("X-Generator: Biscuit Framework v".Biscuit::version());
		self::header("HTTP/1.1 ".self::$_http_status." ".self::$_http_codes[self::$_http_status]);
		self::send_cookies();
		self::$_headers_sent = true;
	}
	/**
	 * Set a cookie to be sent with other headers. We need to do this because otherwise cookies do not get sent when redirecting
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function set_cookie() {
		$args = func_get_args();
		if (!empty($args)) {
			self::$_cookies[] = $args;
		}
	}
	/**
	 * Send all cookies to browser
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function send_cookies() {
		if (!empty(self::$_cookies)) {
			foreach (self::$_cookies as $cookie_args) {
				call_user_func_array('setcookie', $cookie_args);
			}
		}
	}
	/**
	 * Output some content - same as echo "my content".  A little unnecessary, but here if you want to use it
	 *
	 * @param string $content 
	 * @return void
	 * @author Peter Epp
	 */
	
	public static function write($content) {
		echo $content;
	}
	/**
	 * HTTP status of the request
	 *
	 * @return int
	 * @author Peter Epp
	 */
	public static function http_status($status_code = null) {
		if (self::is_valid_http_status($status_code)) {
			self::$_http_status = $status_code;
			return true;
		}
		return self::$_http_status;
	}
	/**
	 * Get or set the MIME type of the render output
	 *
	 * @param string $type Optional - MIME type of render output. Omit this value when getting the mime type. Provide it when you want to set the value.  It must be
	 * a valid MIME type and can include, but is not limited to:
	 *
	 * text/html	// Default
	 * text/css
	 * text/javascript
	 * application/pdf
	 * application/rss+xml
	 * etc...
	 *
	 * @return mixed Boolean "true" when setting, or the output type string when getting
	 * @author Peter Epp
	 */
	public static function content_type($content_type = null) {
		if (!empty($content_type) && is_string($content_type)) {
			// Enforce utf-8 charset on applicable content-types if not defined
			$valid_text_type = (preg_match('/text/i',$content_type) || preg_match('/xml/i',$content_type) || preg_match('/html/i',$content_type) || preg_match('/rss/i',$content_type));
			if ($valid_text_type && !preg_match("/charset/i",$content_type)) {
				$content_type .= "; charset=utf-8";
			}
			self::$_content_type = $content_type;
			return true;
		}
		else {
			return self::$_content_type;
		}
	}
	/**
	 * Whether or not the response content type is text/html
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public static function is_html() {
		return stristr(self::content_type(),"text/html");
	}
	/**
	 * Whether or not a given status code is valid.  Simple check to see if it's in the list of status cades defined on this object.
	 *
	 * @param string $status_code 
	 * @return void
	 * @author Peter Epp
	 */
	public static function is_valid_http_status($status_code) {
		return (!empty($status_code) && !empty(self::$_http_codes[$status_code]));
	}
}
?>