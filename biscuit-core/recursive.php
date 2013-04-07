<?php
/**
 * Collection of recursive functions, such as recursive sorting and directory deletion.
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: recursive.php 14304 2011-09-23 21:51:39Z teknocat $
 */
class Recursive {
	private function __construct() {
		// Prevent instantiation
	}
	/**
	 * PHP sort() recursively
	 *
	 * @param array $array Reference to the array to sort
	 * @return void
	 * @author Peter Epp
	 */
	public static function sort(&$array) {
		sort($array);
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				self::sort($array[$k]);
			}
		}
	}
	/**
	 * PHP rsort() recursively
	 *
	 * @param array $array Reference to the array to sort
	 * @return void
	 * @author Peter Epp
	 */
	public static function rsort(&$array) {
		rsort($array);
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				self::rsort($array[$k]);
			}
		}
	}
	/**
	 * PHP asort() recursively
	 *
	 * @param array $array Reference to the array to sort
	 * @return void
	 * @author Peter Epp
	 */
	public static function asort(&$array) {
		asort($array);
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				self::asort($array[$k]);
			}
		}
	}
	/**
	 * PHP arsort() recursively
	 *
	 * @param array $array Reference to the array to sort
	 * @return void
	 * @author Peter Epp
	 */
	public static function arsort(&$array) {
		arsort($array);
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				self::arsort($array[$k]);
			}
		}
	}
	/**
	 * PHP ksort() recursively
	 *
	 * @param array $array Reference to the array to sort
	 * @return void
	 * @author Peter Epp
	 */
	public static function ksort(&$array) {
		ksort($array);
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				self::ksort($array[$k]);
			}
		}
	}
	/**
	 * PHP ksort() recursively
	 *
	 * @param array $array Reference to the array to sort
	 * @return void
	 * @author Peter Epp
	 */
	public static function krsort(&$array) {
		krsort($array);
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				self::krsort($array[$k]);
			}
		}
	}
	/**
	 * PHP natsort() recursively
	 *
	 * @param array $array Reference to the array to sort
	 * @return void
	 * @author Peter Epp
	 */
	public static function natsort(&$array) {
		natsort($array);
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				self::natsort($array[$k]);
			}
		}
	}
	/**
	 * PHP natcasesort() recursively
	 *
	 * @param array $array Reference to the array to sort
	 * @return void
	 * @author Peter Epp
	 */
	public static function natcasesort(&$array) {
		natcasesort($array);
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				self::natcasesort($array[$k]);
			}
		}
	}
	/**
	 * PHP uasort() recursively
	 *
	 * @param array $array Reference to the array to sort
	 * @return void
	 * @author Peter Epp
	 */
	public static function uasort(&$array) {
		uasort($array);
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				self::uasort($array[$k]);
			}
		}
	}
	/**
	 * PHP uksort() recursively
	 *
	 * @param array $array Reference to the array to sort
	 * @return void
	 * @author Peter Epp
	 */
	public static function uksort(&$array) {
		uksort($array);
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				self::uksort($array[$k]);
			}
		}
	}
	/**
	 * PHP usort() recursively
	 *
	 * @param array $array Reference to the array to sort
	 * @return void
	 * @author Peter Epp
	 */
	public static function usort(&$array) {
		ksort($array);
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				self::usort($array[$k]);
			}
		}
	}
	/**
	 * Recursively remove all contents of a given directory. It will prevent deletion of main site folders.
	 * USE THIS FUNCTION WITH GREAT CARE!!!!
	 *
	 * @param string $directory The directory to delete
	 * @param bool $keep_original_folder Whether or not to keep the directory itself once it's contents have been deleted
	 * @return void
	 * @author Peter Epp
	 */
	public static function rmdir($directory, $keep_original_folder=false) {
		// List of directories that cannot be deleted:
		$skip_list = array('',SITE_ROOT,FW_ROOT,SITE_ROOT."/css",FW_ROOT."/css",SITE_ROOT."/images",FW_ROOT."/images",SITE_ROOT."/plugins",FW_ROOT."/plugins",SITE_ROOT."/scripts",FW_ROOT."/scripts",SITE_ROOT."/templates",FW_ROOT."/templates",SITE_ROOT."/views",FW_ROOT."/views",FW_ROOT."/lib",FW_ROOT."/core.php",FW_ROOT."/index.php");
		if (!in_array($directory,$skip_list)) {
			if(substr($directory,-1) == '/') {
				$directory = substr($directory,0,-1);
			}
			if(!file_exists($directory) || !is_dir($directory) || !is_readable($directory)) {
				return false;
			}
			else {
				$handle = opendir($directory);
				while (false !== ($item = readdir($handle))) {
					if($item != '.' && $item != '..') {
						$path = $directory.'/'.$item;
						if(is_dir($path))  {
							self::rmdir($path);
						}
						else {
							unlink($path);
						}
					}
				}
				closedir($handle);
				if(!$keep_original_folder) {
					if(!rmdir($directory)) {
						return false;
					}
				}
				return true;
			}
		}
		return false;
	}
	/**
	 * Recursive copy to copy directories as well as files
	 *
	 * @param string $source 
	 * @param string $destination 
	 * @return void
	 * @author Peter Epp
	 */
	public static function copy($source, $destination) {
		if(is_dir($source)) {
			@mkdir($destination);
			$objects = scandir($source);
			if (count($objects) > 0) {
				foreach($objects as $file) {
					if( $file == "." || $file == ".." ) {
						continue;
					}
					if (is_dir($source.'/'.$file)) {
						self::copy($source.'/'.$file, $destination.'/'.$file);
					}
					else {
						copy($source.'/'.$file, $destination.'/'.$file );
					}
				}
			}
			return true;
		} else if (is_file($source)) {
			return copy($source, $destination);
		}
		return false;
	}
	/**
	 * Recursive array_map for multi-dimensional arrays.  As with the built-in array_map function, it can take an arbitrary number of additional arguments containing
	 * additional parameters for the callback function.
	 *
	 * @param string $callback Name of the callback function to run all the elements through
	 * @param string $array Multi-dimensional input array
	 * @return void
	 * @author Peter Epp
	 */
	public static function array_map($callback,$array) {
		$arguments = func_get_args();
		array_shift($arguments);
		array_shift($arguments);
		$new_array = array();
		foreach($array as $key => $value) {
			if (is_array($value)) {
				$new_array[$key] = self::array_map($value);
			}
			else {
				$callback_args = array($value);
				foreach($arguments as $arg_key => $argument) {
					if (is_array($argument)) {
						$callback_args[] = $argument[$key];
					}
					else {
						$callback_args[] = $argument;
					}
				}
				$new_array[$key] = call_user_func_array($callback,$callback_args);
			}
		}
		return $new_array;
	}
}
?>