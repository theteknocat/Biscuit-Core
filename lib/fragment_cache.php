<?php
/**
 * Base fragment caching mechanism
 *
 * @package Core
 * @author Peter Epp
 * @author Lee O'Mara
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 1.0
 */
class FragmentCache {
	/**
	 * Type of item being cached
	 *
	 * @var string
	 */
	private $_item_type;
	/**
	 * ID of the item
	 *
	 * @var string
	 */
	private $_id;
	/**
	 * Type of cache store to use. Defaults to file
	 *
	 * @var string
	 */
	private $_cache_type;
	/**
	 * Reference to the cache store object
	 *
	 * @var object
	 */
	private $_cache_store;
	/**
	 * Set the name/type of the item, it's ID and the type of caching to use
	 *
	 * @param string $name 
	 * @param string $id 
	 * @param string $cache_type 
	 * @author Peter Epp
	 */
	public function __construct($item_type, $id, $cache_type = 'file') {
		Console::log("New fragment cache for ".$item_type." with ID ".$id);
		$this->_item_type = $item_type;
		$this->_id = $id;
		$this->_cache_type = $cache_type;
	}
	/**
	 * Return a reference to the cache store object
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function cache_store() {
		if (empty($this->_cache_store)) {
			switch ($this->_cache_type) {
				case 'file':
					$this->_cache_store = new FragmentCacheFileStore($this->_item_type, $this->_id);
					break;
			}
		}
		return $this->_cache_store;
	}
	/**
	 * Whether or not cache exists and is usable
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function hit($fragment_name) {
		return ((!defined('NO_CACHE') || NO_CACHE == false) && $this->cache_store()->hit($fragment_name));
	}
	/**
	 * Output cached content
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	private function output($fragment_name) {
		$this->cache_store()->output($fragment_name);
		return true;
	}
	/**
	 * Store content in the cache
	 *
	 * @param string $content 
	 * @return void
	 * @author Peter Epp
	 */
	public function store($fragment_name, $content) {
		Console::log("Store content to fragment cache...");
		$this->cache_store()->store($fragment_name, $content);
		return $content;
	}
	/**
	 * If cache exists output from cache and stop, otherwise start caching
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public function start($fragment_name) {
		Console::log("Start caching ".$fragment_name);
		if ($this->hit($fragment_name)) {
			Console::log("Cached fragment exists, outputting");
			$this->output($fragment_name);
			return false;
		}
		Console::log("Cached fragment does not exist, start buffering...");
		ob_start();
		return true;
	}
	/**
	 * Stop caching and store content
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function end($fragment_name){
		$content = ob_get_flush();
		Console::log("Fragment captured, storing and outputting...");
		$this->store($fragment_name, $content);
	}
	/**
	 * Invalidate all fragments for the current fragment cache
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function invalidate_all() {
		Console::log("Invalidate all cached fragments");
		$this->cache_store()->invalidate_all();
	}
}
