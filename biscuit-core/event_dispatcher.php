<?php
/**
 * The Event Dispatcher keeps track of all the observers and dispatches them when events are fired.
 *
 * This class should not be used directly.  It's functionality is controlled by Event.
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: event_dispatcher.php 14744 2012-12-01 20:50:43Z teknocat $
 */
class EventDispatcher implements Singleton {
	/**
	 * Instance of self
	 *
	 * @author Peter Epp
	 */
	private static $_instance;
	/**
	 * List of observing classes
	 *
	 * @var array
	 */
	private $_observers = array();
	/**
	 * Prevent public instantiation
	 *
	 * @author Peter Epp
	 */
	private function __construct() {}
	/**
	 * Return a singleton instance
	 *
	 * @return self
	 * @author Peter Epp
	 */
	public static function instance() {
		if (empty(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
	 * Attach an observer
	 *
	 * @param EventObserver|string $observer 
	 * @return void
	 * @author Peter Epp
	 */
	public function attach($observer) {
		$this->_observers[] = $observer;
	}
	/**
	 * Detach an observer
	 *
	 * @param EventObserver|string $observer 
	 * @return void
	 * @author Peter Epp
	 */
	public function detach($observer) {
		if ($index = array_search($observer, $this->_observers, true)) {
			unset($this->_observers[$index]);
		}
	}
	/**
	 * Notify observers of a state change
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function notify(Event $event) {
		foreach ($this->_observers as $observer) {
			if (is_object($observer) && is_callable(array($observer, 'respond_to_event'))) {
				call_user_func_array(array($observer, 'respond_to_event'), array($event, $this));
			} else if (is_callable(array($observer, 'static_act_on_'.$event->name()))) {
				$this->log_event($event, $observer);
				call_user_func_array(array($observer, 'static_act_on_'.$event->name()), $event->arguments());
			}
		}
	}
	/**
	 * Log an event
	 *
	 * @param Event $event 
	 * @param string $observer 
	 * @return void
	 * @author Peter Epp
	 */
	public function log_event(Event $event, $observer) {
		$backtrace = debug_backtrace();
		if (is_object($observer)) {
			$observer_name = get_class($observer);	// Who is acting on the event
			$fired_by = $backtrace[6]['class'].$backtrace[6]['type'].$backtrace[6]['function'];	// Who fired the event
		} else {
			$observer_name = $observer;
			$fired_by = $backtrace[4]['class'].$backtrace[4]['type'].$backtrace[4]['function'];	// Who fired the event
		}
		// Compose log message in CSV format:
		$message = '"'.$event->name().'","'.$observer_name.'","'.$fired_by.'"';
		Console::log_event($message);
		
	}
}
