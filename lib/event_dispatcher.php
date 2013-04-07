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
 * @version 2.0
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
	 * @param EventObserver $observer 
	 * @return void
	 * @author Peter Epp
	 */
	public function attach(EventObserver $observer) {
		$this->_observers[] = $observer;
	}
	/**
	 * Detach an observer
	 *
	 * @param EventObserver $observer 
	 * @return void
	 * @author Peter Epp
	 */
	public function detach(EventObserver $observer) {
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
			$observer->respond_to_event($event);
		}
	}
}
?>