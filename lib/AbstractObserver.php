<?php
class AbstractObserver {
	/**
	 * Act on a specified event if applicable
	 *
	 * @param string $event_name Name of the event to invoke a response on
	 * @return void
	 * @author Peter Epp
	 */
	function invoke($event_name) {
		$arguments = func_get_args();
		array_shift($arguments); // Remove the event name since it won't be needed by the method that acts on the event
		Console::log(get_class($this)." requested to invoke an action on ".$event_name);
		$act_method = "act_on_".$event_name;
		if (!method_exists($this,$act_method)) {
			Console::log("Cannot find method: ".get_class($this)."::".$act_method."()");
			return;
		}
		Console::log("Found method for acting on the event, calling it now...");
		call_user_func_array(array($this,$act_method),$arguments);
	}
	/**
	 * Look for any "act_on" methods in the current plugin and setup listeners for them
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function init_listeners() {
		$methods = get_class_methods($this);
		foreach ($methods as $method) {
			if (substr($method,0,7) == "act_on_") {
				$event_name = substr($method,7);
				EventManager::listen($this,$event_name);
			}
		}
	}
}
?>