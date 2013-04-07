<?php 
/**
 * Class for handling simple database variable caching
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: variable_cache.php 14801 2013-03-27 20:14:53Z teknocat $
 */
class VariableCache extends EventObserver {
	/**
	 * Add this object to list of event observers
	 *
	 * @author Peter Epp
	 */
	public function __construct() {
		Event::add_observer($this);
	}
	/**
	 * Put a variable for a given scope into the cache
	 *
	 * @param string $name The variable name
	 * @param string $scope The scope of the variable (eg. use the name of your module, or the name of the object class that uses it)
	 * @param mixed $value The value of your variable. Can be anything
	 * @return void
	 * @author Peter Epp
	 **/
	public function put($name, $scope, $value) {
		if (!defined('NO_CACHE') || !NO_CACHE) {
			DB::query("REPLACE INTO `cache_variable` (`variable_name`, `scope`, `variable_value`) VALUES (?, ?, ?)", array($name, $scope, $value));
		}
	}
	/**
	 * Get the value of a variable for a given scope from the cache
	 *
	 * @param string $name The variable name
	 * @param string $scope The scope of the variable
	 * @return void
	 * @author Peter Epp
	 **/
	public function get($name, $scope) {
		if (!defined('NO_CACHE') || !NO_CACHE) {
			return DB::fetch_one("SELECT `variable_value` FROM `cache_variable` WHERE `variable_name` = ? AND `scope` = ?", array($name, $scope));
		}
		return null;
	}
	/**
	 * Delete a given variable from the cache, optionally within a given scope
	 * @param  string $name The name of the variable
	 * @param string|null $scope Optional - the scope of the variable
	 * @return void
	 */
	public function delete($name, $scope = null) {
		if (!empty($scope)) {
			DB::query("DELETE FROM `cache_variable` WHERE `variable_name` = ? AND `scope` = ?", array($name, $scope));
		} else {
			DB::query("DELETE FROM `cache_variable` WHERE `variable_name` = ?", $name);
		}
	}
	/**
	 * Empty entire fragment cache on cache empty requests
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_empty_cache_request() {
		DB::query("DELETE FROM `cache_variable`");
	}
}
