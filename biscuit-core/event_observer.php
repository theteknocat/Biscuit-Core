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
 * @version 2.0 $Id: event_observer.php 14737 2012-11-30 22:56:56Z teknocat $
 */
class EventObserver {
	/**
	 * Act on a specified event if applicable. Invoked by EventDispatcher->notify()
	 *
	 * @param Event $event The event object that was fired
	 * @return void
	 * @author Peter Epp
	 */
	public function respond_to_event(Event $event, EventDispatcher $dispatcher) {
		$event_name = $event->name();
		$act_method = "act_on_".$event_name;
		if (!method_exists($this,$act_method)) {
			return;
		}
		$dispatcher->log_event($event, $this);
		call_user_func_array(array($this,$act_method),$event->arguments());
	}
}
