<?php
/**
 * This class provides the base method for invoking an action on the occurrence of an event. Example usage:
 *
 * class MyClass extends EventObserver {
 * 		public function __construct() {
 * 			[...]
 * 			Event::add_observer($this);
 * 		}
 *
 * 		[...your class's methods...]
 *
 * 		protected function act_on_test_event() {
 * 			Console::log("My object responded to the test event!");
 * 		}
 * }
 *
 * $my_object = new MyClass();
 * Event::fire('test_event');
 *
 * Note that all modules, extensions and models that extend the respective abstract class will automatically extend this class. In addition, they will automatically be added
 * as observers in the abstract constructors. You therefore only need to include a call to Event::add_observer($this) in your constructor if your class is standalone.
 * If you put a custom constructor in your module, extension or model, just include in it a call to parent::__construct().
 *
 * Event::add_observer() adds an object as an observer to the common EventDispatcher object that all observers monitor for change in state.  Event::fire('event_name')
 * causes the state change of EventDispatcher to occur by calling it's setValue() method, passing it the event name as an argument, which then invokes the update method in all the
 * observers.  As you can see below, the update method then checks to see if the class has a method for responding to the event that was fired, and if so calls it.
 *
 * For help with debugging, this class also creates an entry in the event_log file whenever the object responds to an event.
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 */
class EventObserver implements SplObserver {
	/**
	 * Act on a specified event if applicable. Invoked by EventDispatcher->notify()
	 *
	 * @param SplSubject $subject The subject being observed
	 * @return void
	 * @author Peter Epp
	 */
	public function update(SplSubject $event_dispatcher) {
		$event_name = $event_dispatcher->getValue();
		$act_method = "act_on_".$event_name;
		if (!method_exists($this,$act_method)) {
			return;
		}
		$this->log_event($event_name);
		$args = $event_dispatcher->getAdditionalArgs();
		if (empty($args)) {
			$args = array();
		}
		call_user_func_array(array($this,$act_method),$args);
	}
	/**
	 * Log a message about an observer invoked when an event is fired
	 *
	 * @param string $act_method 
	 * @return void
	 * @author Peter Epp
	 */
	private function log_event($event_name) {
		$backtrace = debug_backtrace();
		$fired_by = $backtrace[5]['class'].$backtrace[5]['type'].$backtrace[5]['function'];	// Who fired the event
		$listener = get_class($this);	// Who is acting on the event
		// Compose log message in CSV format:
		$message = '"'.$event_name.'","'.$listener.'","'.$fired_by.'"';
		Console::log_event($message);
	}
}
?>