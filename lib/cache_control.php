<?php
/**
 * Class for handling caching functions, both for browser cache control and static page cache
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 */
class CacheControl extends EventObserver {
	/**
	 * Timestamp for when the page being requested was last updated
	 *
	 * @var int
	 */
	private $_latest_update_timestamp;
	/**
	 * An array of timestamps additional to the ones retrieved by the regular last updated checks
	 *
	 * @var array
	 */
	private $_other_timestamps = array();
	/**
	 * Whether or not the page was modified since the last request by the browser
	 *
	 * @var bool
	 */
	private $_modified_since_last_request;
	/**
	 * Whether or not the page cache is valid
	 *
	 * @var bool
	 */
	private $_page_cache_is_valid;
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
		if (!$this->browser_cache_allowed() && !$this->page_cache_allowed()) {
			Console::log("    No page or browser caching allowed, skip update check");
			$this->_latest_update_timestamp = time();
			$this->_modified_since_last_request = true;
			return;
		}
		Console::log("    Checking when content was last modified...");
		$timestamps = array();

		$latest_module_update = $this->latest_module_update();
		if (!empty($latest_module_update)) {
			$timestamps[] = $latest_module_update;
		}

		// Fire an event to invoke modules to provide their timestamps
		Event::fire('check_for_content_updates');

		$timestamps = array_merge($timestamps, $this->_other_timestamps);

		rsort($timestamps);

		$latest_timestamp = reset($timestamps);

		Console::log("        Content was last modified on: ".gmdate(GMT_FORMAT, $latest_timestamp));

		$this->_latest_update_timestamp = $latest_timestamp;

		if ($this->browser_cache_allowed()) {
			$browser_cache_control = strtolower(Request::header('Cache-Control'));
			$browser_pragma        = strtolower(Request::header('Pragma'));
			if (stristr($browser_cache_control, 'no-cache') || stristr($browser_pragma, 'no-cache')) {
				Console::log("        Browser requested un-cached content");
				$this->_modified_since_last_request = true;
			} else {
				$browser_modified_since = Request::if_modified_since();
				if (!empty($browser_modified_since)) {
					Console::log("        Browser reports the following cache file date: ".$browser_modified_since);
					$browser_modified_since = strtotime($browser_modified_since);
				} else {
					Console::log("        Browser has not provided it's modified since date");
					$browser_modified_since = -999999999;
				}
				$this->_modified_since_last_request = ($browser_modified_since < $latest_timestamp);
			}
		}

		if ($this->page_cache_allowed()) {
			$cache_file_time = $this->Page->cache_file_time();
			if ($cache_file_time == -999999999) {
				$this->_modified_since_last_request = true;
				$this->_latest_updated_timestamp = time();
			}
			$this->_page_cache_is_valid = ($latest_timestamp <= $cache_file_time);
		}
	}
	/**
	 * Whether or not using cached page is allowed
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	protected function page_cache_allowed() {
		if (!$this->render_with_template() || (defined("NO_CACHE") && NO_CACHE === true)) {
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
		return ($this->page_cache_allowed() && !Request::is_ajax() && !Session::has_flash_vars() && !Session::flash_vars_just_cleared());
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
		return $this->_latest_update_timestamp;
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
	 * Delete all files in the page_cache directory
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function empty_cache() {
		Console::log("Empty page cache");
		$cache_files = FindFiles::ls('/page_cache',array('types' => 'cache'));
		if (!empty($cache_files)) {
			foreach ($cache_files as $cache_file) {
				@unlink(SITE_ROOT.'/page_cache/'.$cache_file);
			}
		}
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
	 * Empty page cache on request
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_empty_cache_request() {
		$this->empty_cache();
	}
}
