<?php
/**
 * A set of static functions for doing permission checks
 *
 * @package Core
 * @author Peter Epp
 */
class Permissions {
	/**
	 * Can the current page be accessed by the current user?
	 *
	 * @static
	 * @param int $page_access_level 
	 * @return void
	 * @author Peter Epp
	 */
	function can_access($page_access_level) {
		if ($page_access_level == PUBLIC_USER) {
			return true;
		}
		else {
			if (Authenticator::user_is_logged_in()) {
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
	* @param string $action The action to check permissions, for example: plugins:pluginname:actionname, webpagemanager:editpage, pluginmanager:disable
	**/
	function user_can(&$plugin_ref,$action) {
		$permission_string = Permissions::permission_string_for_action($plugin_ref,$action);
		if (Permissions::action_requires_permission($permission_string)) {
			return Permissions::user_has_permission_to($permission_string);
		}
		Console::log("                        Not checking permissions for action: ".$action);
		return true;
	}
	/**
	 * Check if the user has the right level to perform an action using the permission string.  This function exists for cases where something needs to check permissions
	 * on an action without having a reference to the plugin controller object, but does know the permission string
	 *
	 * @param string $permission_string 
	 * @return void
	 * @author Peter Epp
	 */
	function user_has_permission_to($permission_string) {
		Console::log(sprintf("                        Checking permission to %s", $permission_string));
		$required_level = Permissions::level($permission_string);
		$user_level = PUBLIC_USER;		// Public by default. Overidden if user logged in
		if (Authenticator::user_is_logged_in()) {
			$logged_user_data = Session::get('auth_data');
			// Get an instance of the user object:
			$user = User::find($logged_user_data['id']);
			$user_level = $user->user_level();
			unset($user);
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
	function level($permission_string) {
		$access = DB::fetch_one("SELECT `access_level` FROM `permissions` WHERE `permission_string` = '{$permission_string}' LIMIT 1");
		if ($access == false) {
			return PUBLIC_USER;
		}
		return $access;
	}
	/**
	 * Determines if the plugin can be executed by checking permissions and querying the plugin's before_filter method, if one exists.
	 *
	 * @abstract
	 * @return boolean
	 * @author Lee O'Mara
	 * @author Peter Epp
	 */
	function can_execute(&$plugin_ref,$action) {
		// We need to run around in a little circle here in order to have an extension point that allows plugins to define their own user_can() function for special cases.
		// We therefore defer to the plugin to find out if the user can perform the action, which in normal cases will call the abstract user_can() method which defers
		// to the Permissions::user_can() method.  In special cases the plugin can perform whatever special logic it needs, as well as deferring to Permissions::user_can() if
		// applicable.
		$custom_permission_method = 'user_can_'.$action;
		if (method_exists($plugin_ref, $custom_permission_method)) {
			$can_execute = $plugin_ref->$custom_permission_method();
		} else {
			$can_execute = $plugin_ref->user_can($action);
		}
		if ($can_execute) {
			if (method_exists($plugin_ref, 'before_filter')) {
				Console::log("                        Calling before_filter");
				$can_execute = $plugin_ref->before_filter();
				if (!$can_execute) {
					Session::flash("user_message","Missing data needed for the ".$action." action");
					Console::log("                        Permissions: before_filter for ".get_class($plugin_ref)." returned false");
				}
			}
		} else {
			if (!Authenticator::user_is_logged_in()) {
				Session::flash("user_message","Please login to access the requested page");
			} else {
				Session::flash("user_message","You do not have sufficient permission to access the requested page");
				Console::log('                        Permissions: Insufficient permission for '. $action);
			}
		}
		if (!$can_execute) {
			if (Authenticator::user_is_logged_in()) {
				$user = Authenticator::return_current_user();
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
	function action_requires_permission($permission_string) {
		$access = DB::fetch_one("SELECT `access_level` FROM `permissions` WHERE `permission_string` = '{$permission_string}' LIMIT 1");
		return (!empty($access));
	}
	/**
	 * Construct a permission string for an action
	 *
	 * @abstract
	 * @param string $action_name Name of the action (eg. "show", "edit")
	 * @return string Permission string in the format "plugins:plugin_name:action_name"
	 * @author Peter Epp
	 */
	function permission_string_for_action(&$plugin_ref,$action_name) {
		return "plugins:".strtolower(get_class($plugin_ref)).":".$action_name;
	}
}
?>