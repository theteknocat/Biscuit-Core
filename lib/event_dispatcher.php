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
class EventDispatcher implements SplSubject {
	/**
	 * List of observing classes
	 *
	 * @var array
	 */
	private $_observers = array();
	/**
	 * Name of the fired event
	 *
	 * @var string
	 */
	private $_event_name;
	/**
	 * Any additional arguments for use by the observer
	 *
	 * @var array
	 */
	private $_additional_args = null;
	/**
	 * Attach an observer
	 *
	 * @param SplObserver $observer 
	 * @return void
	 * @author Peter Epp
	 */
	public function attach(SplObserver $observer) {
		$this->_observers[] = $observer;
	}
	/**
	 * Detach an observer
	 *
	 * @param SplObserver $observer 
	 * @return void
	 * @author Peter Epp
	 */
	public function detach(SplObserver $observer) {
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
	public function notify() {
		foreach ($this->_observers as $observer) {
			$observer->update($this);
		}
	}
	/**
	 * Set the event name and notify all observers
	 *
	 * @param string $event_name 
	 * @return void
	 * @author Peter Epp
	 */
	public function setValue($event_name) {
		$this->_event_name = $event_name;
		$this->notify();
	}
	/**
	 * Set additional arguments for use by the observer if desired
	 *
	 * @param array $args 
	 * @return void
	 * @author Peter Epp
	 */
	public function setAdditionalArgs($args) {
		$this->_additional_args = $args;
	}
	/**
	 * Get any additional arguments set when an event was fired
	 *
	 * @return array|null
	 * @author Peter Epp
	 */
	public function getAdditionalArgs() {
		return $this->_additional_args;
	}
	/**
	 * Get the name of the fired event
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function getValue() {
		return $this->_event_name;
	}
}
?>