<?php
/**
 * Encapsulate functionality that goes with cron tasks
 *
 * @package default
 * @author Peter Epp
 */
class Cron implements Singleton {
	/**
	 * Place to store single instance of self
	 *
	 * @var self
	 */
	private static $_instance;
	/**
	 * Place to store cron task messages
	 *
	 * @var array
	 */
	private $_messages = array();
	/**
	 * Prevent public instantiation
	 *
	 * @author Peter Epp
	 */
	private function __construct() {}
	/**
	 * Return instance of self
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
	 * Add a message about a cron task
	 *
	 * @param string $message 
	 * @return void
	 * @author Peter Epp
	 */
	public static function add_message($calling_object, $message) {
		self::instance()->_messages[] = date("Y-m-d H:i:s")." ".get_class($calling_object)." - ".$message;
	}
	/**
	 * Return all cron task messages as a concatenated string with line breaks
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function messages() {
		return implode("\n",self::instance()->_messages);
	}
	/**
	 * Return the number of messages recorded
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function message_count() {
		return count(self::instance()->_messages);
	}
}
