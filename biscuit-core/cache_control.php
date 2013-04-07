<?php
/**
 * Class for handling caching functions, both for browser cache control and static page cache
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: cache_control.php 14776 2013-01-08 20:46:51Z teknocat $
 */
class CacheControl extends EventObserver {
	/**
	 * Timestamp for when the page being requested was last updated
	 *
	 * @var int
	 */
	private $_last_updated_timestamp;
	/**
	 * Whether or not the page was modified since the last request by the browser
	 *
	 * @var bool
	 */
	private $_modified_since_last_request = true;
	/**
	 * Whether or not the page cache is valid
	 *
	 * @var bool
	 */
	private $_page_cache_is_valid = false;
	/**
	 * Whether or not to never cache the current request
	 *
	 * @var bool
	 */
	private $_never_cache_page = false;
	/**
	 * Check if any page content has been modified since the last request
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function check_modified() {
		if (!$this->browser_cache_allowed()) {
			$this->_modified_since_last_request = true;
		}
		if (!$this->page_cache_allowed()) {
			$this->_page_cache_is_valid = false;
		}

		$this->_last_updated_timestamp = time();

		if (!$this->browser_cache_allowed() && !$this->page_cache_allowed()) {
			Console::log("    No page or browser caching allowed, skip update check");
			$this->_modified_since_last_request = true;
			return;
		}

		if ($this->page_cache_allowed()) {
			$cache_modified_time = $this->_cache_last_modified();
			if ($cache_modified_time !== null) {
				$this->_last_updated_timestamp = $cache_modified_time;
				$this->_page_cache_is_valid = true;
			}
		}

		if ($this->browser_cache_allowed()) {
			$browser_cache_control = strtolower(Request::header('Cache-Control'));
			$browser_pragma        = strtolower(Request::header('Pragma'));
			if (stristr($browser_cache_control, 'no-cache') || stristr($browser_pragma, 'no-cache')) {
				Console::log("        Browser requested un-cached content");
			} else {
				$browser_modified_since = Request::if_modified_since();
				if (!empty($browser_modified_since)) {
					Console::log("        Browser reports the following cache date: ".$browser_modified_since);
					$browser_modified_since = strtotime($browser_modified_since);
				} else {
					Console::log("        Browser has not provided it's modified since date");
					$browser_modified_since = -999999999;
				}
				$this->_modified_since_last_request = ($browser_modified_since < $this->_last_updated_timestamp);
			}
		}
	}
	/**
	 * Whether or not using cached page is allowed
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	protected function page_cache_allowed() {
		if ((defined("NO_CACHE") && NO_CACHE === true) || Request::user_input('no_cache')) {
			return false;
		}
		return (!Request::is_post() && !$this->_never_cache_page && !Session::has_flash_vars());
	}
	/**
	 * Set the page request to never cache
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function set_never_cache() {
		$this->_never_cache_page = true;
		$this->_modified_since_last_request = true;
	}
	/**
	 * Whether or not using browser cache is allowed
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	protected function browser_cache_allowed() {
		return (($this->page_cache_allowed() || Request::is_ajax()) && !Session::has_flash_vars() && !Session::flash_vars_just_cleared());
	}
	/**
	 * Add a timestamp to the list of other timestamps used to check if the page has been updated. This method should be called by any modules responding to
	 * the "checking_for_content_updates" event so that they may add a timestamp for cases where the normal last updated check doesn't suffice.
	 *
	 * @param string $value 
	 * @return void
	 * @author Peter Epp
	 */
	public function add_updated_timestamp($value) {
		if (!empty($value)) {
			$gmdate = gmdate(GMT_FORMAT, $value);
			$new_tstamp = strtotime($gmdate);
			if ($new_tstamp == $value) {
				if (DEBUG) {
					Console::log("Adding last update time: ".$gmdate);
				}
				$this->_other_timestamps[] = $value;
			}
		}
	}
	/**
	 * Return the latest update timestamp
	 *
	 * @return int
	 * @author Peter Epp
	 */
	protected function latest_update_timestamp() {
		return $this->_last_updated_timestamp;
	}
	/**
	 * Whether or not the page was modified since the last request by the browser
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	protected function modified_since_last_request() {
		return $this->_modified_since_last_request;
	}
	/**
	 * Whether or not the page cache is valid
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public function page_cache_is_valid() {
		return $this->_page_cache_is_valid;
	}
	/**
	 * Delete everything from the cache_page table
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function empty_cache() {
		Console::log("Empty page cache");
		DB::query("DELETE FROM `cache_page`");
		DB::query("ALTER TABLE `cache_page` auto_increment = 1");
	}
	/**
	 * Determine all the parameters that identify the cache store for the current request
	 *
	 * @return array
	 * @author Peter Epp
	 **/
	private function _cache_parameters() {
		$full_uri = Request::uri();
		$locale = I18n::instance()->get_locale_from_uri($full_uri);
		if (empty($locale)) {
			$locale = I18n::instance()->locale();
		}
		if ($locale != I18n::instance()->locale()) {
			// If the current locale does not match the URL's locale, find the locale for this URL
			$locale_id = ModelFactory::instance('BiscuitLocale')->find_by_code($locale)->id();
		} else {
			// Otherwise get the current locale's ID
			$locale_id = I18n::instance()->locale_id();
		}
		if ($this->ModuleAuthenticator()->user_is_logged_in()) {
			$user_level = $this->ModuleAuthenticator()->active_user()->user_level();
			$user_id = $this->ModuleAuthenticator()->active_user()->id();
		} else {
			$user_level = 0;
			$user_id = null;
		}
		$request_type = Request::type();
		if (Request::is_ajax()) {
			$request_type .= '-ajax';
		}
		$session_timeout = 0;
		if ($this->render_with_template()) {
			$session_timeout = $this->ModuleAuthenticator()->session_timeout();
		}
		return array(
			':full_url'        => $full_uri,
			':page_id'         => $this->Page->id(),
			':theme'           => $this->Theme->theme_name(),
			':template'        => $this->Theme->template_name(),
			':locale_id'       => $locale_id,
			':request_type'    => $request_type,
			':user_level'      => $user_level,
			':user_id'         => $user_id,
			':session_timeout' => $session_timeout
		);
	}
	/**
	 * Write page content to the page cache file
	 *
	 * @param string $page_content 
	 * @return void
	 * @author Peter Epp
	 */
	protected function cache_write($page_content) {
		$parameters = $this->_cache_parameters();
		$existing_id = $this->_find_existing_id($parameters);
		$parameters[':content'] = $page_content;
		if ($existing_id) {
			$parameters[':id'] = $existing_id;
			$query = "REPLACE INTO `cache_page` (`id`, `full_url`, `page_id`, `theme`, `template`, `locale_id`, `request_type`, `user_level`, `user_id`, `session_timeout`, `content`) VALUES (:id, :full_url, :page_id, :theme, :template, :locale_id, :request_type, :user_level, :user_id, :session_timeout, :content)";
		} else {
			$query = "INSERT INTO `cache_page` (`full_url`, `page_id`, `theme`, `template`, `locale_id`, `request_type`, `user_level`, `user_id`, `session_timeout`, `content`) VALUES (:full_url, :page_id, :theme, :template, :locale_id, :request_type, :user_level, :user_id, :session_timeout, :content)";
		}
		DB::query($query, $parameters);
	}
	/**
	 * Return the ID of a cache item matching provided parameters
	 *
	 * @return int|null
	 * @author Peter Epp
	 **/
	protected function _find_existing_id($parameters) {
		return DB::fetch_one("SELECT `id` FROM `cache_page` WHERE `full_url` = :full_url AND `page_id` = :page_id AND `theme` = :theme AND `template` = :template AND `locale_id` = :locale_id AND `request_type` = :request_type AND `user_level` = :user_level AND `user_id` = :user_id AND `session_timeout` = :session_timeout", $parameters);
	}
	/**
	 * Return the timestamp when the cache file was last modified, or a large negative number if not found
	 *
	 * @return int Unix timestamp
	 * @author Peter Epp
	 */
	private function _cache_last_modified() {
		$parameters = $this->_cache_parameters();
		$last_modified_time = DB::fetch_one("SELECT `last_modified` FROM `cache_page` WHERE `full_url` = :full_url AND `page_id` = :page_id AND `theme` = :theme AND `template` = :template AND `locale_id` = :locale_id AND `request_type` = :request_type AND `user_level` = :user_level AND `user_id` = :user_id AND `session_timeout` = :session_timeout", $parameters);
		if (empty($last_modified_time)) {
			Console::log("        Page cache not found");
			return null;
		}
		$last_modified_time = (int)$last_modified_time;
		Console::log("        Page cache last modified on: ".gmdate(GMT_FORMAT, $last_modified_time));
		return $last_modified_time;
	}
	/**
	 * Return cached page content
	 *
	 * @return string
	 * @author Peter Epp
	 */
	protected function cached_content() {
		$parameters = $this->_cache_parameters();
		return DB::fetch_one("SELECT `content` FROM `cache_page` WHERE `full_url` = :full_url AND `page_id` = :page_id AND `theme` = :theme AND `template` = :template AND `locale_id` = :locale_id AND `request_type` = :request_type AND `user_level` = :user_level AND `user_id` = :user_id AND `session_timeout` = :session_timeout", $parameters);
	}
	/**
	 * On Biscuit initialization, fire an event if there's a query string requesting cache emptying and then redirect
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_biscuit_initialization() {
		if (Request::query_string('empty_caches') == 1) {
			Console::log("Empty cache request, firing event to trigger cache emptying...");
			Event::fire('empty_cache_request');
			$uri = Crumbs::strip_query_var_from_uri(Request::uri(),'empty_caches');
			Response::redirect($uri);
		}
	}
	/**
	 * Upon saving a model, invalidate the page cache for all pages the associated module is installed on
	 *
	 * @param string $model 
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_successful_save($model) {
		$this->invalidate_page_cache($model);
	}
	/**
	 * Upon deleting a model, invalidate the page cache for all pages the associated module is installed on
	 *
	 * @param string $model 
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_successful_delete($model) {
		$this->invalidate_page_cache($model);
	}
	/**
	 * Invalidate page cache when models are resorted
	 *
	 * @param string $model_name 
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_resorted_items($model_name) {
		$this->invalidate_page_cache($model_name);
	}
	/**
	 * Invalidate the cache for a specific URL
	 *
	 * @param string $full_url
	 * @return void
	 **/
	public function invalidate_page_cache_by_url($full_url) {
		DB::query("DELETE FROM `cache_page` WHERE `full_url` = ?", $full_url);
	}
	/**
	 * Invalidate page caches for all pages applicable to the model that was just updated
	 *
	 * @param string $module 
	 * @return void
	 * @author Peter Epp
	 */
	public function invalidate_page_cache($model) {
		if (is_object($model)) {
			$model_name = Crumbs::normalized_model_name($model);
		} else {
			$model_name = $model;
		}
		if ($model_name == 'Page' || $model_name == 'CustomPage') {
			// When the page model has been updated, only invalidate the cache that match the page ID as they will be the only ones that should have changed
			DB::query("DELETE FROM `cache_page` WHERE `page_id` = ?", $model->id());
			return;
		} else {
			// Otherwise, do logic to determine which pages need to be invalidated based on where modules using the model are installed
			$installed_modules = DB::fetch("SELECT `name` FROM `modules` WHERE `installed` = 1");
			if (empty($installed_modules)) {
				Console::log("No modules installed! Unpossible!");
				return;
			}
			$modules_to_invalidate = array();
			foreach ($installed_modules as $module_name) {
				$module_classname = Crumbs::module_classname($module_name);
				if (call_user_func_array(array($module_classname, 'uses_model'), array($module_classname, $model_name))) {
					$modules_to_invalidate[] = $module_name;
				}
			}
			if (empty($modules_to_invalidate)) {
				Console::log("No modules using the specified model! Unpossible!");
				return;
			}
			$query = "SELECT `mp`.`page_name`, `mp`.`is_primary`, `m`.`name` FROM `module_pages` `mp` LEFT JOIN `modules` `m` ON (`m`.`id` = `mp`.`module_id`) WHERE `m`.`name` IN ('".implode("','",$modules_to_invalidate)."') ORDER BY `mp`.`page_name` ASC";
			$module_install_info = DB::fetch($query);
			if (empty($module_install_info)) {
				Console::log("No info about installed modules! Unpossible!");
				return;
			}
			// Make a list of all page IDs keyed by slug so we don't have to do a query for each module install info row
			$pages = DB::fetch("SELECT `id`, `slug` FROM `page_index`");
			$page_ids_by_slug = array();
			foreach ($pages as $page_data) {
				$page_ids_by_slug[$page_data['slug']] = $page_data['id'];
			}
			$pages_to_invalidate = array();
			foreach ($module_install_info as $install_info) {
				// We will invalidate the page cache for any and all pages the module is installed on to be on the safe side as it's not possible to definitively
				// determine whether or not a module actually renders something on the pages on which it's installed
				if ($install_info['page_name'] == '*') {
					// As soon as one module using the model is installed on all pages, set to invalidate all pages and stop here. This should always kick in before
					// any others are checked since data is sorted by page name in ascending order
					$pages_to_invalidate = DB::fetch("SELECT `id` FROM `page_index`");
					break;
				} else {
					$pages_to_invalidate[] = $page_ids_by_slug[$install_info['page_name']];
				}
			}
		}
		if (empty($pages_to_invalidate)) {
			Console::log("No pages to invalidate, chill");
			return;
		}
		$pages_to_invalidate_sql = implode(', ', $pages_to_invalidate);
		DB::query("DELETE FROM `cache_page` WHERE `page_id` IN (".$pages_to_invalidate_sql.")");
	}
	/**
	 * Empty page cache on request
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_empty_cache_request() {
		$this->empty_cache();
	}
}
