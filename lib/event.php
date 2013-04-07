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
	 * Name of the fired event
	 *
	 * @var string
	 */
	private $_event_name;
	/**
	 * Array of arguments to pass on to the observer
	 *
	 * @var array
	 */
	private $_arguments = array();
	/**
	 * Constructor that sets the event names and arguments. Declared as private to prevent public instantiation
	 *
	 * @param string $event_name 
	 * @param array $arguments 
	 * @author Peter Epp
	 */
	private function __construct($event_name, $arguments) {
		$this->_event_name = $event_name;
		$this->_arguments = $arguments;
		EventDispatcher::instance()->notify($this);
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
			$args = array();
		}
		// Construct an event object and notify observers of it's occurence
		new self($event_name, $args);
	}
	/**
	 * Add an observer to the dispatcher singleton
	 *
	 * @param EventObserver $observer Reference to the EventObserver object being attached
	 * @return void
	 * @author Peter Epp
	 */
	public static function add_observer(EventObserver $observer) {
		EventDispatcher::instance()->attach($observer);
	}
	/**
	 * Remove an observer from the dispatcher
	 *
	 * @param EventObserver $observer Reference to the EventObserver object being removed
	 * @return void
	 * @author Peter Epp
	 */
	public static function remove_observer(SplObserver $observer) {
		EventDispatcher::instance()->detach($observer);
	}
	/**
	 * Return the name of the fired event
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function name() {
		return $this->_event_name;
	}
	/**
	 * Return the array of additional arguments
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function arguments() {
		return $this->_arguments;
	}
}
?>