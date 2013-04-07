<?php
/**
 * Browser response helpers
 *
 * @package Core
 * @author Peter Epp
 */
class Response {
	/**
	 * Set a response header
	 *
	 * @param string $header_string The full header string to set (eg. "Content-type: application/pdf")
	 * @return void
	 * @author Peter Epp
	 */
	function header($header_string) {
		header($header_string);
	}
	/**
	 * Whether or not response headers have been sent
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function headers_sent() {
		return headers_sent();
	}
	/**
	 * Sends the browser ping response headers and exits
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function ping() {
		Response::header("Expires: 0");
		Response::header("Last-Modified: " . date("r"));
		Response::header("Cache-control: private, no-store, no-cache, must-revalidate, max-age=0");
		Response::header("Pragma: no-cache");
		if (DEBUG) {
			print_r(Session::contents());
		}
		Biscuit::end_program();
	}
	/**
	 * Redirect to another page on the site
	 *
	 * @param string $page Name of the page to redirect to.  This must be the full path to the page from the site root.
	 * @return void
	 * @author Peter Epp
	 */
	
	function redirect($page) {
		Console::log("Redirect request: ".$page);
		// Page redirection.  Use this static function to redirect to a page in the current site.
		// If you want to redirect to a page on another site that you have the full URL for, this static function is not
		// needed, and you can just use "header('Location: '.$myurl)" instead
		$schema = Request::port() == '443' ? 'https' : 'http';
		$host = strlen($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
		if (!stristr($page,STANDARD_URL) && !stristr($page,SECURE_URL)) {
			$url = $base_url.$page;
		}
		else {
			$url = $page;
		}
		if (Request::is_ajax() || Request::is_ajaxy_iframe_post()) {
			Console::log("    AJAX Redirect to: $url");
			Response::write('<script language="javascript" type="text/javascript" charset="utf-8">top.location.href = "'.$page.'";</script>');
			Biscuit::end_program(true);
		}
		else {
			if (Response::headers_sent()) {
				Console::log("    Redirect failed because headers have already been sent!");
				return false;
			}
			else
			{
				Console::log("    Full redirect URL: $url");
				Session::close();
				Response::header("Location: $url");
				// Put an ending marker in the log
				Console::log("\n============= Done! Can I have a biscuit now? ===============\n");
				exit;
			}
		}
	}
	/**
	 * Set the HTTP headers for the current page based on the HTTP status, access level and server port. Sets HTTP status of the page (200, 403, 404) based on the $http_status property of the web page object, and sets cache control to private for SSL connections and pages with a non-public access level.
	 *
	 * @author Peter Epp
	 */
	function standard_headers($http_status,$access_level) {
		$http_codes = array(
			200 => 'OK',
			301 => 'Moved Permanently',
			401 => 'Unauthorized',
			404 => 'Not Found',
			403 => 'Forbidden',
			500 => 'Internal Server Error');
		if ($http_status != 403 || ($http_status == 403 && !Request::is_ajax())) {
			Response::header("Cache-Control: public, must-revalidate, proxy-revalidate, max-age=0");
			Response::header("HTTP/1.1 ".$http_status." ".$http_codes[$http_status]);
		}
		if ($access_level > 0 || Request::port() != "443") {
			Response::header("Cache-Control: private, must-revalidate, proxy-revalidate, max-age=0");
		}
	}
	/**
	 * Output some content - same as echo "my content".  A little unnecessary, but here if you want to use it
	 *
	 * @param string $content 
	 * @return void
	 * @author Peter Epp
	 */
	
	function write($content) {
		echo $content;
	}
	/**
	 * Whether or not to minify header includes (JS and CSS files)
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	function minify_header_includes() {
		return (defined("USE_MINIFY") && USE_MINIFY == 1 && version_compare(PHP_VERSION,"5.0.0",">="));
	}
	/**
	 * Set a cookie
	 *
	 * @param string $key Name of the cookie
	 * @param string $value Value of the cookie
	 * @param string $days_to_live Optional - haw many days the cookie should live. Defaults to 0, which is end of session
	 * @param string $path Optional - defaults to /
	 * @param string $domain Optional - defaults to the current hostname without www, including a preceding . for max browser compatibility
	 * @return void
	 * @author Peter Epp
	 */
	function set_cookie($key,$value,$days_to_live = 0,$path = '/',$domain = null) {
		if (!$domain) {
			$domain = '.'.Configuration::host_name();
		}
		if ($days_to_live != 0) {
			$expire = time()+(60*60*24*$days_to_live);
		} else {
			$expire = $days_to_live;
		}
		setcookie($key,$value,$expire,$path,$domain);
	}
}
?>
