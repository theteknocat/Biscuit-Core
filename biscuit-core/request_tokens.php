<?php
/**
 * Class for dealing with setting and checking request tokens for post requests
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: request_tokens.php 14737 2012-11-30 22:56:56Z teknocat $
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
			self::trash_token_if_used($page_slug);
			return true;
		}
		if (Request::is_post() && (!self::matches($page_slug) || !self::anti_is_empty())) {
			return false;
		}
		self::trash_token_if_used($page_slug);
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
	 * Whether or not the submitted token matches the one in session
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function matches($page_slug) {
		$form_id = Request::user_input('token_form_id');
		if (empty($form_id)) {
			// If no form id provided by user input, FAIL
			return false;
		}
		$tokens = Session::get("request_tokens");
		$token = $tokens[self::token_page_slug($page_slug)][$form_id];
		if (empty($token)) {
			// If there is no token stored in session then we validate it automatically
			return true;
		}
		$token_from_input = Request::user_input('request_token');
		Console::log("        Checking token:\n            User Input: ".$token_from_input."\n            Session:    ".$token);
		return ($token_from_input == $token);
	}
	/**
	 * Whether or not the request anti-spam field is empty. Empty is good. Not empty means it got filled out and that's bad.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function anti_is_empty() {
		$anti = Request::form('request_anti');
		return empty($anti);
	}
	/**
	 * If a token has been used, which is the case on POST requests that are not ajax login or validation type
	 *
	 * @param string $page_slug 
	 * @return void
	 * @author Peter Epp
	 */
	private static function trash_token_if_used($page_slug) {
		if (Request::type() != 'validation' && Request::type() != 'login') {
			$form_id = Request::user_input('token_form_id');
			if (empty($form_id)) {
				// Can't do anyhing if there's no form id from user input
				return;
			}
			$tokens = Session::get("request_tokens");
			unset($tokens[self::token_page_slug($page_slug)][$form_id]);
		}
	}
	/**
	 * Create a request token for a given page slug with a unique form ID and return the token value and form ID
	 *
	 * @param string $page_slug 
	 * @return string
	 * @author Peter Epp
	 */
	public static function get($page_slug = null) {
		$Biscuit = Biscuit::instance();
		if (empty($page_slug)) {
			$page_slug = $Biscuit->Page->slug();
		}
		$form_id = md5(uniqid(rand(), true));
		$token = self::set($page_slug, $form_id);
		return array('token' => $token, 'form_id' => $form_id);
	}
	/**
	 * Generate a new token for a given page slug and form ID, set in session and return the token value
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function set($page_slug, $form_id) {
		$tokens = Session::get("request_tokens");
		$new_token = sha1(Crumbs::random_password(64));
		$token_page_slug = self::token_page_slug($page_slug);
		$tokens[$token_page_slug][$form_id] = $new_token;
		Session::set("request_tokens",$tokens);
		return $new_token;
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
		$token_info = self::get($page_slug);
		return '<input type="hidden" name="request_token" value="'.$token_info['token'].'"><input type="hidden" name="token_form_id" value="'.$token_info['form_id'].'"><input type="text" name="request_anti" value="" style="position:absolute;left:-10000px;top:-10000px">';
	}
}
