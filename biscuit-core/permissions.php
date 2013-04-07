<?php
/**
 * Handle permission checks
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: permissions.php 14744 2012-12-01 20:50:43Z teknocat $
 */
class Permissions {
	/**
	 * Associative array of access levels by permission string
	 *
	 * @author Peter Epp
	 */
	private static $_access_levels = array();

	private function __construct() {
		// Prevent instantiation
	}
	/**
	 * Can the current page be accessed by the current user?
	 *
	 * @static
	 * @param int $page_access_level 
	 * @return void
	 * @author Peter Epp
	 */
	public static function can_access($page_access_level) {
		if ($page_access_level == PUBLIC_USER) {
			return true;
		}
		else {
			if (Biscuit::instance()->ModuleAuthenticator()->user_is_logged_in()) {
				$auth_data = Session::get('auth_data');
				return ($auth_data['user_level'] >= $page_access_level);
			}
		}
		return false;
	}
	/**
	* Can the current user perform a certain action?
	*
	* @static
	* @return boolean
	* @author Lee O'Mara
	* @author Peter Epp
	* @param object $module A reference to the module object
	* @param string $action The action to check permissions
	**/
	public static function user_can($module,$action,$user_level = null) {
		$permission_string = self::permission_string_for_action($module,$action);
		if (self::action_requires_permission($permission_string)) {
			return self::user_has_permission_to($permission_string,$user_level);
		}
		Console::log("                        Not checking permissions for action: ".$action);
		return true;
	}
	/**
	 * Check if the user has the right level to perform an action based on the permission string.
	 *
	 * @param string $permission_string 
	 * @return void
	 * @author Peter Epp
	 */
	private static function user_has_permission_to($permission_string,$user_level = null) {
		Console::log(sprintf("                        Checking permission to %s", $permission_string));
		$required_level = self::level($permission_string);
		if (!$user_level) {
			// If no user level explicitly provided for checking the permission, figure out what level to check against:
			$user_level = PUBLIC_USER;		// Public by default. Overidden if user logged in
			if (Biscuit::instance()->ModuleAuthenticator()->user_is_logged_in()) {
				$user = Biscuit::instance()->ModuleAuthenticator()->active_user();
				$user_level = $user->user_level();
				unset($user);
			}
		}
		Console::log("                        Required level: ".$required_level.", User level: ".$user_level);
		return ($user_level >= $required_level);
	}
	/**
	* Return the access level for a specific action
	*
	* @return int Access level
	* @author Peter Epp
	* @static
	*/
	private static function level($permission_string) {
		$access = self::get_access_level($permission_string);
		if ($access === false) {
			return PUBLIC_USER;
		}
		return $access;
	}
	/**
	 * Return the access level required for a specific module and action
	 *
	 * @param string $module 
	 * @param string $action 
	 * @return int
	 * @author Peter Epp
	 */
	public static function access_level_for($module,$action) {
		$permission_string = self::permission_string_for_action($module,$action);
		return self::level($permission_string);
	}
	/**
	 * Determines if the module can be executed by checking permissions and querying the module's before_filter method, if one exists.
	 *
	 * @abstract
	 * @return boolean
	 * @author Lee O'Mara
	 * @author Peter Epp
	 */
	public static function can_execute($module,$action) {
		// We need to run around in a little circle here in order to have an extension point that allows modules to define their own user_can() function for special cases.
		// We therefore defer to the module to find out if the user can perform the action, which in normal cases will call the abstract user_can() method which defers
		// to the self::user_can() method.  In special cases the module can perform whatever special logic it needs, as well as deferring to self::user_can() if
		// applicable.
		$permission_method = "user_can_".$action;
		$can_execute = $module->$permission_method();
		if ($can_execute) {
			if (method_exists($module, 'before_filter')) {
				Console::log("                        Calling before_filter");
				$can_execute = $module->before_filter();
				if (!$can_execute) {
					Session::flash("user_error",sprintf(__("Missing data needed for the %s action"),$action));
					Console::log("                        Permissions: before_filter for ".get_class($module)." returned false");
				}
			}
		}
		else {
			Console::log('                        Permissions: Insufficient permission for '. $action);
		}
		if (!$can_execute) {
			if (Biscuit::instance()->ModuleAuthenticator()->user_is_logged_in()) {
				$user = Biscuit::instance()->ModuleAuthenticator()->active_user();
				Console::log("Current user (".$user->full_name().") has access level: ".$user->user_level());
			}
			else {
				Console::log("No user is logged in right now");
			}
			Console::log(Session::contents());
		}
		return $can_execute;
	}
	/**
	 * Does an action require a permissions check?
	 *
	 * @abstract
	 * @param string the name of an action
	 * @return boolean
	 */
	private static function action_requires_permission($permission_string) {
		return (self::get_access_level($permission_string) !== false);
	}
	/**
	 * Return the access level for a permission string, caching it to a static property on the first request
	 *
	 * @param string $permission_string 
	 * @return int
	 * @author Peter Epp
	 */
	private static function get_access_level($permission_string) {
		if (empty(self::$_access_levels)) {
			$all_permissions = DB::fetch("SELECT * FROM `permissions`");
			foreach ($all_permissions as $permission) {
				self::$_access_levels[$permission['permission_string']] = (int)$permission['access_level'];
			}
		}
		if (empty(self::$_access_levels[$permission_string])) {
			return false;
		}
		return self::$_access_levels[$permission_string];
	}
	/**
	 * Construct a permission string for an action
	 *
	 * @abstract
	 * @param string $action_name Name of the action (eg. "show", "edit")
	 * @return string Permission string in the format "module_name:action_name"
	 * @author Peter Epp
	 */
	private static function permission_string_for_action($module,$action_name) {
		$permission_string = AkInflector::underscore(get_class($module)).":".$action_name;
		if (substr($permission_string,0,7) == 'custom_') {
			$permission_string = substr($permission_string,7);
		}
		return $permission_string;
	}
	/**
	 * Add a set of permissions for a given module
	 *
	 * @param object $module Reference to the module object
	 * @param array $permissions Associative array of actions and access levels, eg. array('new' => 99, 'edit' => 99)
	 * @param bool $replace_existing Whether or not to replace all existing permissions for the module before insert the ones defined
	 * @return void
	 * @author Peter Epp
	 */
	public static function add($module_classname,$permissions,$replace_existing = false) {
		if ($replace_existing) {
			self::remove($module_classname);
		}
		$module_classname = AkInflector::underscore($module_classname);
		Console::log("Permissions: adding permission set for ".$module_classname);
		foreach ($permissions as $action => $access_level) {
			$permission_sqls[] = "('{$module_classname}:{$action}', {$access_level})";
		}
		$permission_sql = "INSERT INTO `permissions` (`permission_string`, `access_level`) VALUES ".implode(', ',$permission_sqls);
		DB::query($permission_sql);
	}
	/**
	 * Remove all permissions for a given module.
	 *
	 * @param object $module Reference to the module object
	 * @return void
	 * @author Peter Epp
	 */
	public static function remove($module_classname) {
		$module_classname = AkInflector::underscore($module_classname);
		DB::query("DELETE FROM `permissions` WHERE `permission_string` LIKE '{$module_classname}:%'");
	}
}
?>