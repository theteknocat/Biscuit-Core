<?php
$event_listeners = array();
class EventManager {
	/**
	 * Notify the event manager of an event occurrence
	 *
	 * @param string $event_name Name of the event that has occurred
	 * @return void
	 * @author Peter Epp
	 */
	function notify($event_name) {
		global $event_listeners;
		$arguments = func_get_args();
		if (empty($event_listeners[$event_name])) {
			return;
		}
		for ($i=0;$i < count($event_listeners[$event_name]);$i++) {
			call_user_func_array(array($event_listeners[$event_name][$i],"invoke"),$arguments);
		}
	}
	/**
	 * Add an object to the list of listeners along with the name of the event it is listening for
	 *
	 * @param string $object_name Name of the object that's listening
	 * @param string $event_name Name of the event it is listening for
	 * @return void
	 * @author Peter Epp
	 */
	function listen(&$listener_object,$event_name) {
		global $event_listeners;
		if (!method_exists($listener_object,"invoke")) {
			trigger_error('No invoke method found for '.get_class($listener_object).'!',E_USER_ERROR);
		}
		else {
			$event_listeners[$event_name][] = &$listener_object;
		}
	}
}
?>