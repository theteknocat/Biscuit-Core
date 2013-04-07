<?php
/**
 * API for observing and dispatching events
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 */
class Event {
	/**
	 * EventDispatcher instance
	 *
	 * @author Peter Epp
	 */
	private static $_dispatcher;

	private function __construct() {
		// Prevent instantiation
	}
	/**
	 * Fire an event, triggering any listeners to respond
	 *
	 * @param string $event_name Name of the event that has occurred
	 * @return void
	 * @author Peter Epp
	 */
	public static function fire($event_name) {
		$args = func_get_args();
		if (count($args) > 1) {
			array_shift($args);
		} else {
			$args = null;
		}
		self::EventDispatcher()->setAdditionalArgs($args);
		self::EventDispatcher()->setValue($event_name);
	}
	/**
	 * Add an observer to the dispatcher singleton
	 *
	 * @param string $object_name Name of the object that's listening
	 * @return void
	 * @author Peter Epp
	 */
	public static function add_observer(SplObserver $observer) {
		self::EventDispatcher()->attach($observer);
	}
	/**
	 * Remove an observer from the dispatcher
	 *
	 * @param SplObserver $observer 
	 * @return void
	 * @author Peter Epp
	 */
	public static function remove_observer(SplObserver $observer) {
		self::EventDispatcher()->detach($observer);
	}
	/**
	 * Get an instance of the dispatcher singleton
	 *
	 * @return EventDispatcher Object instance
	 * @author Peter Epp
	 */
	private static function EventDispatcher() {
		if (empty(self::$_dispatcher)) {
			self::$_dispatcher = new EventDispatcher();
		}
		return self::$_dispatcher;
	}
}
?>