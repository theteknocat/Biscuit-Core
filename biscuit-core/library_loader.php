<?php
/**
 * An object class for handling registration and inclusion of 3rd party Javascript libraries on demand. Extensions or modules that provide the library can
 * register them, anyone else can require them. If a required library is not present an exception is thrown
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: library_loader.php 14744 2012-12-01 20:50:43Z teknocat $
 */
class LibraryLoader {
	/**
	 * Place to track libraries that have been loaded so we don't load the same ones more than once
	 *
	 * @var array
	 */
	private static $_libraries_loaded = array();
	/**
	 * Whether or not a library is available
	 *
	 * @param string $lib_name 
	 * @return bool
	 * @author Peter Epp
	 */
	public static function is_available($lib_name) {
		$lib_path = AkInflector::underscore($lib_name);
		$full_lib_path = Crumbs::file_exists_in_load_path('libraries/'.$lib_path.'/library.php');
		return !empty($full_lib_path);
	}
	/**
	 * Require a specified library to register it's files for inclusion at render time
	 *
	 * @param string $lib_name 
	 * @return void
	 * @author Peter Epp
	 */
	public static function load($lib_name) {
		if (in_array($lib_name, self::$_libraries_loaded)) {
			// Already loaded, chill
			return;
		}
		$args = func_get_args();
		array_shift($args);
		$lib_path = AkInflector::underscore($lib_name);
		$full_lib_path = Crumbs::file_exists_in_load_path('libraries/'.$lib_path.'/library.php');
		if (!empty($full_lib_path)) {
			require_once($full_lib_path);
			call_user_func_array(array($lib_name, 'register'), $args);
			self::$_libraries_loaded[] = $lib_name;
		} else {
			throw new CoreException("Required library not found: ".$lib_name.". Please add this library to your site's libraries folder and try again.");
		}
	}
}
