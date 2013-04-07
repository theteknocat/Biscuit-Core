<?php
/**
 * Class for dealing with setting and checking request tokens for post requests
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 */
class RequestTokens {
	/**
	 * List of requests that ignore tokens
	 *
	 * @author Peter Epp
	 */
	private static $_requests_ignore_tokens = array();
	/**
	 * Prevent public instantiation
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function __construct() {
		// Prevent instantiation
	}
	/**
	 * Check if the token matches on post requests, or set it on get requests
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function check($page_slug) {
		Event::fire('request_token_check');
		if ((defined('IGNORE_REQUEST_TOKEN') && IGNORE_REQUEST_TOKEN == true) || (!empty(self::$_requests_ignore_tokens) && !empty(self::$_requests_ignore_tokens[$page_slug]))) {
			Console::log("Ignoring request token, either per global setting or for the specified request");
			return true;
		}
		if (Request::is_post() && !RequestTokens::matches($page_slug)) {
			return false;
		}
		// Only set a token if there is no token already set for the current request or the request is not Ajax. If we set a new token on every Ajax request,
		// then an Ajaxy validation request will reset the token and prevent the form from being submitted again after the user has made their corrections.
		if (!Request::is_post() || !RequestTokens::is_set($page_slug)) {
			$new_token = RequestTokens::set($page_slug);
			if (DEBUG) {
				$token_msg = "        Setting new request token for ".$page_slug;
				if (!RequestTokens::is_set($page_slug)) {
					$token_msg .= " (first time)";
				}
				else {
					$token_msg .= " (time for a new one)";
				}
				Console::log($token_msg);
				$tokens = Session::get("request_tokens");
				Console::log("        New token is: ".$tokens[RequestTokens::token_page_slug($page_slug)]);
			}
		} else {
			if (RequestTokens::is_set($page_slug)) {
				Console::log("        Token for ".$page_slug." is already set!");
			} else if (Request::is_post()) {
				Console::log("        Request is post, not setting token");
			}
		}
		return true;
	}
	/**
	 * Set to ignore tokens for a specified page slug
	 *
	 * @param string $page_slug 
	 * @return void
	 * @author Peter Epp
	 */
	public static function set_ignore($page_slug) {
		self::$_requests_ignore_tokens[$page_slug] = true;
	}
	/**
	 * Whether or not a token is set for the requested page
	 *
	 * @param string $page_slug 
	 * @return void
	 * @author Peter Epp
	 */
	protected static function is_set($page_slug) {
		$tokens = Session::get("request_tokens");
		return (empty($tokens) || isset($tokens[RequestTokens::token_page_slug($page_slug)]));
	}
	/**
	 * Whether or not the submitted token matches the one in session
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function matches($page_slug) {
		$tokens = Session::get("request_tokens");
		$hashed_token = sha1($tokens[RequestTokens::token_page_slug($page_slug)]);
		Console::log("        Checking token match for ".$page_slug.":\n            User Input: ".Request::form("request_token")."\n            Session:    ".$hashed_token);
		$token_to_match = null;
		if (Request::form('request_token')) {
			$token_to_match = Request::form('request_token');
		} else if (Request::query_string('request_token')) {
			$token_to_match = Request::query_string('request_token');
		}
		return ($token_to_match == $hashed_token);
	}
	/**
	 * Set a token in session
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function set($page_slug) {
		$tokens = Session::get("request_tokens");
		$new_token = Crumbs::random_password(64);
		$token_page_slug = RequestTokens::token_page_slug($page_slug);
		if (!$tokens) {
			$tokens = array($token_page_slug => $new_token);
		}
		else {
			$tokens[$token_page_slug] = $new_token;
		}
		Session::set("request_tokens",$tokens);
		return $new_token;
	}
	/**
	 * Return the request token for a given page slug, setting it first if it's not present
	 *
	 * @param string $page_slug 
	 * @return string
	 * @author Peter Epp
	 */
	public static function get($page_slug = null) {
		$Biscuit = Biscuit::instance();
		if ($page_slug == null) {
			$page_slug = $Biscuit->Page->slug();
		}
		$token_slug = RequestTokens::token_page_slug($page_slug);
		$tokens = Session::get("request_tokens");
		return sha1($tokens[$token_slug]);
	}
	/**
	 * Translate forward slashes into under-scores in page slug for use as key in session tokens array
	 *
	 * @param string $page_slug 
	 * @return void
	 * @author Peter Epp
	 */
	public static function token_page_slug($page_slug) {
		return implode("-",explode("/",$page_slug));
	}
	/**
	 * Render a hidden form field containing the token
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function render_token_field($page_slug = null) {
		return '<input type="hidden" name="request_token" value="'.self::get($page_slug).'">';
	}
}
?>