<?php
/**
 * Handle concatenating and caching JS and CSS into single files for inclusion in the page
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 */
class JsAndCssCache extends EventObserver {
	public function cache_js_and_css() {
		try {

			$header_js_cache   = SITE_ROOT.'/js/cache/'.$this->header_js_cache_path();

			$footer_js_cache   = SITE_ROOT.'/js/cache/'.$this->footer_js_cache_path();

			$this->cache_fileset('js',$this->_js_files['header'],$header_js_cache,"\n;\n");
			$this->cache_fileset('js',$this->_js_files['footer'],$footer_js_cache,"\n;\n");

			$screen_css_files = array();
			$print_css_files  = array();
			foreach ($this->_css_files as $index => $file) {
				if ($file['media'] == "print") {
					$print_css_files[]  = $file['filename'];
				} else {
					$screen_css_files[] = $file['filename'];
				}
			}

			$screen_css_cache  = SITE_ROOT.'/css/cache/'.$this->screen_css_cache_path();
			$print_css_cache   = SITE_ROOT.'/css/cache/'.$this->print_css_cache_path();

			$this->cache_fileset('css',$screen_css_files,$screen_css_cache);
			$this->cache_fileset('css',$print_css_files,$print_css_cache);

			if (!empty($this->_ie_css_files)) {
				foreach($this->_ie_css_files as $index => $file) {
					$cache_ie_css_files[] = $file['filename'];
				}
				$ie_css_cache = SITE_ROOT.'/css/cache/'.$this->ie_css_cache_path();
				$this->cache_fileset('css',$cache_ie_css_files,$ie_css_cache);
			}

			if (!empty($this->_ie6_css_files)) {
				foreach($this->_ie6_css_files as $index => $file) {
					$cache_ie6_css_files[] = $file['filename'];
				}
				$ie6_css_cache = SITE_ROOT.'/css/cache/'.$this->ie6_css_cache_path();
				$this->cache_fileset('css',$cache_ie6_css_files,$ie6_css_cache);
			}

		} catch (JsAndCssCacheException $e) {
			trigger_error("JS and CSS Caching failed: ".$e->getMessage(), E_USER_ERROR);
		}
	}
	/**
	 * Return the full path to the header JS cache file relative to the JS cache folder
	 *
	 * @return string
	 * @author Peter Epp
	 */
	protected function header_js_cache_path() {
		return $this->hashed_filename($this->_js_files['header']).'-header-scripts.js';
	}
	/**
	 * Return the full path to the footer JS cache file relative to the JS cache folder
	 *
	 * @return string
	 * @author Peter Epp
	 */
	protected function footer_js_cache_path() {
		return $this->hashed_filename($this->_js_files['footer']).'-footer-scripts.js';
	}
	/**
	 * Return the full path to the screen CSS cache file relative to the CSS cache folder
	 *
	 * @return string
	 * @author Peter Epp
	 */
	protected function screen_css_cache_path() {
		foreach($this->_css_files as $index => $file) {
			if ($file['media'] == "screen") {
				$screen_css_files[] = $file['filename'];
			}
		}
		return $this->hashed_filename($screen_css_files).'-screen-styles.css';
	}
	/**
	 * Return the full path to the print CSS cache file relative to the CSS cache folder
	 *
	 * @return string
	 * @author Peter Epp
	 */
	protected function print_css_cache_path() {
		foreach($this->_css_files as $index => $file) {
			if ($file['media'] == "print") {
				$print_css_files[]  = $file['filename'];
			}
		}
		return $this->hashed_filename($print_css_files).'-print-styles.css';
	}
	/**
	 * Return the full path to the IE CSS cache file relative to the CSS cache folder 
	 *
	 * @return string
	 * @author Peter Epp
	 */
	protected function ie_css_cache_path() {
		foreach($this->_ie_css_files as $index => $file) {
			$cache_ie_css_files[] = $file['filename'];
		}
		return $this->hashed_filename($cache_ie_css_files).'-ie-styles.css';
	}
	/**
	 * Return the full path to the IE6 CSS cache file relative to the CSS cache folder
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function ie6_css_cache_path() {
		if (!empty($this->_ie6_css_files)) {
			foreach($this->_ie6_css_files as $index => $file) {
				$cache_ie6_css_files[] = $file['filename'];
			}
			return $this->hashed_filename($cache_ie6_css_files).'-ie6-styles.css';
		}
		return false;
	}
	/**
	 * Return a hash of the concatenated filenames to use as a unique cache file name
	 *
	 * @param array $filename_list 
	 * @return void
	 * @author Peter Epp
	 */
	private function hashed_filename($filename_list) {
		return sha1(implode('-',$filename_list));
	}
	/**
	 * Cache a set of files by concatenating them into one. On local dev server the files are re-cached on every request.
	 *
	 * @param string $source_files 
	 * @param string $cache_file_path 
	 * @param string $glue What to glue the contents of each file together with. Defaults to "\n". When concatenating Javascript, it might be prudent to use ";\n"
	 * @return void
	 * @author Peter Epp
	 */
	private function cache_fileset($type,$source_files,$cache_file_path,$glue = "\n") {
		$timestamps = array();
		$source_file_paths = array();
		foreach($source_files as $filename) {
			if ($full_filename = Crumbs::file_exists_in_load_path($filename)) {
				$timestamps[] = filemtime($full_filename);
				$source_file_paths[] = $full_filename;
			} else {
				throw new JsAndCssCacheException("Unable to find file in load path: ".$filename);
			}
		}
		rsort($timestamps);
		$latest_timestamp = reset($timestamps);
		$cache_file_updated = (file_exists($cache_file_path) ? filemtime($cache_file_path) : 0);
		// if (!empty($source_file_paths) && (!file_exists($cache_file_path) || $cache_file_updated < $latest_timestamp || SERVER_TYPE == "LOCAL_DEV")) {
		if (!empty($source_file_paths) && (!file_exists($cache_file_path) || $cache_file_updated < $latest_timestamp)) {
			$file_contents = array();
			$ext = '';
			foreach ($source_file_paths as $filename) {
				if (empty($ext)) {
					$filename_bits = explode(".",$filename);
					$ext = array_pop($filename_bits);
				}
				$file_contents[] = file_get_contents($filename);
			}
			if (empty($file_contents)) {
				throw new JsAndCssCacheException("Files could not be read or did not return any content:\n".print_r($source_files,true));
			}
			$concatenated_content = implode($glue,$file_contents);
			if ($type == 'css') {
				/* remove comments */
				$concatenated_content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $concatenated_content);
				/* remove tabs, spaces, newlines, etc. */
				$concatenated_content = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $concatenated_content);
			}
			if (!empty($concatenated_content)) {
				if (!file_put_contents($cache_file_path,$concatenated_content)) {
					throw new JsAndCssCacheException("Failed to write cache file: ".$cache_file_path);
				}
			}
		}
	}
	/**
	 * Trash all CSS and JS cache files
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function empty_caches() {
		Console::log("Empty JS and CSS caches");
		$css_cache_dir = '/css/cache';
		$cache_files = FindFiles::ls($css_cache_dir,array('types' => 'css'));
		if (!empty($cache_files)) {
			foreach ($cache_files as $file) {
				@unlink(SITE_ROOT.$css_cache_dir.'/'.$file);
			}
		}
		$js_cache_dir = '/js/cache';
		$cache_files = FindFiles::ls($js_cache_dir,array('types' => 'js'));
		if (!empty($cache_files)) {
			foreach ($cache_files as $file) {
				@unlink(SITE_ROOT.$js_cache_dir.'/'.$file);
			}
		}
	}
	/**
	 * Empty caches on request
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_empty_cache_request() {
		$this->empty_caches();
	}
}
