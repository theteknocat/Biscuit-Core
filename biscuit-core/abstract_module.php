<?php
/**
 * An abstract class that handles module-specific permission checks and database installation
 *
 * @package Modules
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: abstract_module.php 14357 2011-10-28 22:23:04Z teknocat $
 */
class AbstractModule extends EventObserver {
	/**
	 * Add this object as an event observer
	 *
	 * @author Peter Epp
	 */
	public function __construct() {
		Event::add_observer($this);
	}
	/**
	 * Handles undefined method calls for permission checks such as user_can_create_photo()
	 *
	 * @param string $method_name 
	 * @param string $args 
	 * @return void
	 * @author Peter Epp
	 */
	public function __call($method_name,$args) {
		if (method_exists($this,$method_name)) {
			// The method exists on the object already, but for some reason PHP decided to defer to the magic caller anyway.
			// This seems to happen in PHP 5.2.11 when you call a protected or private method from an external context. We
			// therefore need a way to catch that, so this is my workaround
			throw new ModuleException("An attempt was made to call ".get_class($this)."::".$method_name.", which exists on the object, but PHP deferred to the magic __call() method. You probably defined the method as private or protected but tried to call it outside the context of the ".get_class($this)." object instance.");
		}
		if (substr($method_name,0,8) == 'user_can') {
			$action_name = substr($method_name,9);
			if (stristr($action_name,"create")) {
				$action_name = str_replace("create","new",$action_name);
			} else if (stristr($action_name,"view")) {
				$action_name = str_replace("view","show",$action_name);
			}
			return $this->user_can($action_name);
		}
		// Throw an exception if we couldn't find the appropriate method to call
		throw new ModuleException("Undefined method: ".get_class($this)."::".$method_name);
	}
	/**
	 * Ask Authenticator if the user has permission to perform a requested action
	 *
	 * @param string $action The name of the action
	 * @return bool
	 * @author Peter Epp
	 */
	public function user_can($action) {
		return Permissions::user_can($this,$action);
	}
}
?>