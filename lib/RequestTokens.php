<?php
/**
 * Class for dealing with setting and checking request tokens for post requests
 *
 * @package default
 * @author Peter Epp
 */
class RequestTokens {
	static $_ignore_list = array();
	/**
	 * Check if the token matches on post requests, or set it on get requests
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function check($page_name) {
		if (Request::is_post() && !RequestTokens::matches($page_name) && !RequestTokens::is_ignored($page_name)) {
			return false;
		}
		// Only set a token if there is no token already set for the current request or the request is not Ajax. If we set a new token on every Ajax request,
		// then an Ajaxy validation request will reset the token and prevent the form from being submitted again after the user has made their corrections.
		if (!Request::is_post() || !RequestTokens::is_set($page_name)) {
			RequestTokens::set($page_name);
			if (DEBUG) {
				$token_msg = "        Setting new request token for ".$page_name;
				if (!RequestTokens::is_set($page_name)) {
					$token_msg .= " (first time)";
				}
				else {
					$token_msg .= " (time for a new one)";
				}
				Console::log($token_msg);
				$tokens = Session::get("request_tokens");
				Console::log("        New token is: ".$tokens[RequestTokens::token_page_name($page_name)]);
			}
		}
		return true;
	}
	/**
	 * Set a specified page to ignore request tokens
	 *
	 * @param string $page_name 
	 * @return void
	 * @author Peter Epp
	 */
	function set_ignore($page_name) {
		self::$_ignore_list[] = $page_name;
	}
	/**
	 * Whether or not token for a given page is set to be ignored
	 *
	 * @param string $page_name 
	 * @return void
	 * @author Peter Epp
	 */
	function is_ignored($page_name) {
		return in_array($page_name, self::$_ignore_list);
	}
	/**
	 * Whether or not a token is set for the requested page
	 *
	 * @param string $page_name 
	 * @return void
	 * @author Peter Epp
	 */
	function is_set($page_name) {
		$tokens = Session::get("request_tokens");
		return (empty($tokens) || isset($tokens[RequestTokens::token_page_name($page_name)]));
	}
	/**
	 * Whether or not the submitted token matches the one in session
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function matches($page_name) {
		$tokens = Session::get("request_tokens");
		Console::log("        Checking token match for ".$page_name.":\n            User Input: ".Request::form("request_token")."\n            Session:    ".$tokens[RequestTokens::token_page_name($page_name)]);
		return (Request::form("request_token") == $tokens[RequestTokens::token_page_name($page_name)]);
	}
	/**
	 * Set a token in session
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function set($page_name) {
		$tokens = Session::get("request_tokens");
		$new_token = Crumbs::random_password(64);
		$token_page_name = RequestTokens::token_page_name($page_name);
		if (!$tokens) {
			$tokens = array($token_page_name => $new_token);
		}
		else {
			$tokens[$token_page_name] = $new_token;
		}
		Session::set("request_tokens",$tokens);
	}
	/**
	 * Translate forward slashes into under-scores in page name for use as key in session tokens array
	 *
	 * @param string $page_name 
	 * @return void
	 * @author Peter Epp
	 */
	function token_page_name($page_name) {
		return implode("_",explode("/",$page_name));
	}
	/**
	 * Render a hidden form field containing the token
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function render_token_field($page_name = null) {
		global $Biscuit;
		if ($page_name != null) {
			$page = $page_name;
		}
		else {
			$page = $Biscuit->full_page_name;
		}
		$tokens = Session::get("request_tokens");
		if (empty($tokens) || empty($tokens[RequestTokens::token_page_name($page)])) {
			RequestTokens::set($page);
			$tokens = Session::get("request_tokens");
		}
		$my_token = $tokens[RequestTokens::token_page_name($page)];
		return '<input type="hidden" name="request_token" value="'.$my_token.'">';
	}
}
?>