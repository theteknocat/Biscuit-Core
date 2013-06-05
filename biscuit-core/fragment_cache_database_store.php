<?php 
/**
 * Databes fragment cache store
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 1.0 $Id: fragment_cache_database_store.php 14802 2013-05-07 20:03:38Z teknocat $
 */
class FragmentCacheDatabaseStore extends FragmentCacheStore {
	/**
	 * Return the array of parameters that uniquely identify a cache entry
	 *
	 * @param $fragment_name string
	 * @return array
	 * @author Peter Epp
	 **/
	private function _cache_entry_parameters($fragment_name) {
		return array(
			':type'          => $this->_item_type,
			':id'            => $this->_id,
			':fragment_name' => $fragment_name
		);
	}
	/**
	 * Whether or not the fragment exists in cache
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function hit($fragment_name) {
		$row_count = DB::fetch_one("SELECT COUNT(`content`) FROM `cache_fragment` WHERE `type` = :type AND `id` = :id AND `fragment_name` = :fragment_name", $this->_cache_entry_parameters($fragment_name));
		return ($row_count > 0);
	}
	/**
	 * Cache content to fragment table
	 *
	 * @param string $content 
	 * @return void
	 * @author Peter Epp
	 */
	public function store($fragment_name, $content) {
		$parameters = $this->_cache_entry_parameters($fragment_name);
		$parameters['content'] = $content;
		DB::query("REPLACE INTO `cache_fragment` (`type`, `id`, `fragment_name`, `content`) VALUES (:type, :id, :fragment_name, :content)", $parameters);
	}
	/**
	 * Output the fragment content from the database
	 *
	 * @param string $fragment_name 
	 * @return void
	 * @author Peter Epp
	 */
	public function output($fragment_name) {
		$parameters = $this->_cache_entry_parameters($fragment_name);
		echo DB::fetch_one("SELECT `content` FROM `cache_fragment` WHERE `type` = :type AND `id` = :id AND `fragment_name` = :fragment_name", $parameters);
		return true;
	}
	/**
	 * Invalidate all cached fragments for the current item by emptying the database table
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function invalidate_all() {
		DB::query("DELETE FROM `cache_fragment` WHERE `type` = ? AND `id` = ?", array($this->_item_type, $this->_id));
	}
	/**
	 * Empty the entire contents of the fragment cache
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function empty_all() {
		DB::query("DELETE FROM `cache_fragment`");
	}
	/**
	 * Whether or not the database fragment cache can be used - in other words, whether or not the cache table exists
	 *
	 * @return bool
	 * @author Peter Epp
	 **/
	public static function can_use() {
		$all_tables = DB::fetch("SHOW TABLES");
		return in_array('cache_fragment', $all_tables);
	}
}
