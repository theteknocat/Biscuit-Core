<?php
/**
 * File fragment cache store
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 1.0 $Id: fragment_cache_file_store.php 14778 2013-01-15 05:32:34Z teknocat $
 */
class FragmentCacheFileStore extends FragmentCacheStore {
	/**
	 * Return the path to the cache directory
	 *
	 * @return string
	 * @author Peter Epp
	 */
	private function store_path(){
		$path = '/var/cache/fragments/'.$this->_item_type.'/'.$this->_id;
		if (Crumbs::ensure_directory(SITE_ROOT.$path)) {
			return $path;
		}
		return false;
	}
	/**
	 * Path to the fragment file
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function fragment_file($fragment_name) {
		$locale = I18n::instance()->locale();
		return $this->store_path().'/'.$fragment_name.'-'.$locale.'.cache';
	}
	/**
	 * Whether or not the fragment exists in cache
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function hit($fragment_name) {
		$fragment_file = SITE_ROOT.$this->fragment_file($fragment_name);
		return file_exists($fragment_file);
	}
	/**
	 * Cache content to fragment file
	 *
	 * @param string $content 
	 * @return void
	 * @author Peter Epp
	 */
	public function store($fragment_name, $content) {
		$full_path = SITE_ROOT.$this->store_path();
		Console::log("Storing content in file cache directory: ".var_export($full_path,true));
		if (is_dir($full_path) && is_writable($full_path)) {
			$full_fragment_path = SITE_ROOT.$this->fragment_file($fragment_name);
			file_put_contents($full_fragment_path,$content);
		}
	}
	/**
	 * Include the fragment cache file
	 *
	 * @param string $fragment_name 
	 * @return void
	 * @author Peter Epp
	 */
	public function output($fragment_name) {
		include(SITE_ROOT.$this->fragment_file($fragment_name));
		return true;
	}
	/**
	 * Invalidate all cached fragments for the current item by deleting all the fragment files in the item's cache path
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function invalidate_all() {
		$store_path = $this->store_path();
		if (file_exists(SITE_ROOT.$store_path)) {
			$files = FindFiles::ls($store_path);
			if (!empty($files)) {
				foreach ($files as $filename) {
					@unlink(SITE_ROOT.$store_path.'/'.$filename);
				}
				@rmdir(SITE_ROOT.$store_path);
			}
		}
	}
	/**
	 * Empty the entire contents of the fragment cache
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function empty_all() {
		$files = FindFiles::ls('/var/cache/fragments',array('types' => 'cache'),true,true);
		if (!empty($files)) {
			foreach ($files as $file) {
				@unlink($file);
			}
			$dirs = FindFiles::ls('/var/cache/fragments',array('include_files' => false, 'include_directories' => true),true,true);
			if (!empty($dirs)) {
				rsort($dirs);
				foreach ($dirs as $dir) {
					@rmdir($dir);
				}
			}
		}
	}
}
