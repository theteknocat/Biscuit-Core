<?php 
/**
 * Class for handling simple database variable caching
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: variable_cache.php 14737 2012-11-30 22:56:56Z teknocat $
 */
class VariableCache {
	/**
	 * Put a variable for a given scope into the cache
	 *
	 * @param string $name The variable name
	 * @param string $scope The scope of the variable (eg. use the name of your module, or the name of the object class that uses it)
	 * @param mixed $value The value of your variable. Can be anything
	 * @return void
	 * @author Peter Epp
	 **/
	public static function put($name, $scope, $value) {
		DB::query("REPLACE INTO `variable_cache` (`variable_name`, `scope`, `variable_value`) VALUES (?, ?, ?)", array($name, $scope, $value));
	}
	/**
	 * Get the value of a variable for a given scope from the cache
	 *
	 * @param string $name The variable name
	 * @param string $scope The scope of the variable
	 * @return void
	 * @author Peter Epp
	 **/
	public static function get($name, $scope) {
		return DB::fetch_one("SELECT `variable_value` FROM `variable_cache` WHERE `variable_name` = ? AND `scope` = ?", array($name, $scope));
	}
}
