<?php
/**
 * A common Fragment Cache Store class
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: fragment_cache_store.php 14744 2012-12-01 20:50:43Z teknocat $
 */
abstract class FragmentCacheStore {
	/**
	 * Type of item being cached (eg. "User")
	 *
	 * @var string
	 */
	protected $_item_type;
	/**
	 * ID of the item
	 *
	 * @var string
	 */
	protected $_id;
	/**
	 * Stash the name and ID of the item to cache
	 *
	 * @param string $name 
	 * @param mixed $id 
	 * @author Peter Epp
	 */
	public function __construct($item_type, $id) {
		$this->_item_type = $item_type;
		$this->_id = $id;
	}
	/**
	 * Require a "store" method that takes a fragment name and some content. It must store the cached content.
	 *
	 * @param $fragment_name string
	 * @param $content string
	 * @return void
	 **/
	abstract public function store($fragment_name, $content);
	/**
	 * Require an "output" method that takes a fragment name. It must output the fragment content.
	 *
	 * @param $fragment_name string
	 * @return string
	 **/
	abstract public function output($fragment_name);
	/**
	 * Require a "hit" method that takes a fragment name. It must return a boolean value that indicates whether or not the fragment exists in the cache
	 *
	 * @param $fragment_name string
	 * @return bool
	 **/
	abstract public function hit($fragment_name);
	/**
	 * Require an "invalidate_all" method that will delete all cached fragments
	 *
	 * @return void
	 **/
	abstract public function invalidate_all();
}
