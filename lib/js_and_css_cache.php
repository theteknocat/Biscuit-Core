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
class JsAndCssCache {
	public static function run() {
		$Biscuit = Biscuit::instance();
		try {
			$action = Biscuit::instance()->user_input('action');
			if (empty($action)) {
				$action = 'index';
			}
			$prefix = $Biscuit->Page->theme_name().'-'.$Biscuit->Page->hyphenized_slug().'-'.$action;
			$header_js_cache   = SITE_ROOT.'/js/cache/'.$prefix.'_header_scripts.js';
			$footer_js_cache   = SITE_ROOT.'/js/cache/'.$prefix.'_footer_scripts.js';

			self::cache_fileset('js',$Biscuit->js_files('header'),$header_js_cache,"\n;\n");
			self::cache_fileset('js',$Biscuit->js_files('footer'),$footer_js_cache,"\n;\n");

			$screen_css_cache  = SITE_ROOT.'/css/cache/'.$prefix.'_screen_styles.css';
			$print_css_cache   = SITE_ROOT.'/css/cache/'.$prefix.'_print_styles.css';
			$ie_css_cache      = SITE_ROOT.'/css/cache/'.$prefix.'_ie.css';
			$ie6_css_cache     = SITE_ROOT.'/css/cache/'.$prefix.'_ie6.css';

			$all_css_files    = $Biscuit->css_files();
			$screen_css_files = array();
			$print_css_files  = array();
			foreach ($all_css_files as $index => $file) {
				if ($file['media'] == "print") {
					$print_css_files[]  = $file['filename'];
				} else {
					$screen_css_files[] = $file['filename'];
				}
			}

			self::cache_fileset('css',$screen_css_files,$screen_css_cache);
			self::cache_fileset('css',$print_css_files,$print_css_cache);

			$ie_css_files = $Biscuit->ie_css_files();
			if (!empty($ie_css_files)) {
				foreach($ie_css_files as $index => $file) {
					$cache_ie_css_files[] = $file['filename'];
				}
				self::cache_fileset('css',$cache_ie_css_files,$ie_css_cache);
			}

			$ie6_css_files = $Biscuit->ie6_css_files();
			if (!empty($ie6_css_files)) {
				foreach($ie6_css_files as $index => $file) {
					$cache_ie6_css_files[] = $file['filename'];
				}
				self::cache_fileset('css',$cache_ie6_css_files,$ie6_css_cache);
			}

		} catch (JsAndCssCacheException $e) {
			trigger_error("JS and CSS Caching failed: ".$e->getMessage(), E_USER_ERROR);
		}
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
	private static function cache_fileset($type,$source_files,$cache_file_path,$glue = "\n") {
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
		if (!empty($source_file_paths) && (!file_exists($cache_file_path) || $cache_file_updated < $latest_timestamp || SERVER_TYPE == "LOCAL_DEV")) {
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
	public static function empty_caches() {
		$css_cache_dir = '/css/cache';
		$js_cache_dir = '/js/cache';
		$css_cache_files = FindFiles::ls($css_cache_dir,array('types' => 'css'));
		$js_cache_files = FindFiles::ls($js_cache_dir,array('types' => 'js'));
		if (!empty($css_cache_files)) {
			foreach ($css_cache_files as $css_cache_file) {
				@unlink(SITE_ROOT.$css_cache_dir.'/'.$css_cache_file);
			}
		}
		if (!empty($js_cache_files)) {
			foreach ($js_cache_files as $js_cache_file) {
				@unlink(SITE_ROOT.$js_cache_dir.'/'.$js_cache_file);
			}
		}
	}
}
?>