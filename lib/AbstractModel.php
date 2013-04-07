<?php
/**
 * An abstract model for use with plugin models
 *
 * @package Plugins
 * @author Peter Epp
 */
class AbstractModel extends AbstractObserver {
	/**
	 * Primary key for this object
	 *
	 * @var int
	 */
	var $id;
	/**
	 * A list of any error messages resulting from save or validation
	 *
	 * @var string
	 */
	var $_error_messages = array();
	/**
	* Constructor for the AbstractModel class
	*
	* @param $attributes (array)  an associative array of attributes
	* @return AbstractModel object
	**/
	function AbstractModel($attributes = array()){
		Console::log("                        New model created: ".get_class($this));
		$this->validated = false;		// Mark as un-validated by default
		$this->set_attributes($attributes);
		$this->init_listeners();
	}
	/**
	 * Whether or not the model is new or an existing one in the database
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	function is_new() {
		return (!$this->id());
	}
	/**
	 * Set or get the validation status
	 *
	 * @param string $bool 
	 * @return void
	 * @author Peter Epp
	 */
	function has_been_validated($bool = null) {
		if ($bool !== null) {
			$this->validated = $bool;
		}
		else {
			return $this->validated;
		}
	}
	/**
	 * Return a specified attribute of the object
	 *
	 * @param string $name Name of the attribute
	 * @return mixed
	 * @author Lee O'Mara
	 * @author Peter Epp
	 */
	function get_attribute($name) {
		if (isset($this->$name)) {
			return $this->$name;
		} else {
			return null;
		}
	}
	/**
	 * Return all the attributes (except the DB table) as an associative array
	 *
	 * @return array
	 * @author Peter Epp
	 */
	function get_attributes() {
		// Grab all the default class vars:
		$class_vars = get_class_vars(get_class($this));
		if (!empty($class_vars)) {
			$my_vars = array();
			foreach ($class_vars as $key => $value) {
				if (substr($key,0,1) != "_") {		// Skip private vars
					$my_vars[$key] = $this->get_attribute($key);	// Store the attribute's value in the new array with the same key
				}
			}
			return $my_vars;
		}
		else {
			return null;
		}
	}
	/**
	 * Set a single model attribute
	 *
	 * @param string $name Name of the attribute
	 * @param string $value Desired value
	 * @return void
	 * @author Lee O'Mara
	 * @author Peter Epp
	 */
	function set_attribute($name,$value) {
		$this->$name = $value;
	}
	/**
	* Set model attributes
	*
	* @param array $attributes
	* @author Lee O'Mara
	*/
	function set_attributes($attributes) {
		$class_vars = get_class_vars(get_class($this));
		foreach ($class_vars as $key => $value) {
			if(!isset($attributes[$key])){
				continue;
			}
			$set_method = 'set_'.$key;
			if (is_callable(array($this, $set_method))) {
				$this->$set_method($attributes[$key]);
			} else {
				$this->set_attribute($key, $attributes[$key]);
			}
		}
	}
	/**
	 * Purify all attributes on the model using HTML purifier.  This relies on HTML purifier, but does not check for it's existence. You must check for HTML Purifier
	 * in the run function of your plugin whose model(s) need to purify their attributes.
	 *
	 * @param string $mode "text" or "html"
	 * @return void
	 * @author Peter Epp
	 */
	function purify_attributes($mode = "text",$filters = array()) {
		// Prevent nasty errors by forcing mode to "text" if an invalid argument value was provided:
		$mode = strtolower($mode);
		if ($mode != "text" && $mode != "html") {
			$mode = "text";
		}
		$dirty_attributes = $this->get_attributes();
		$purify_method = "purify_array_".$mode;
		if ($mode == "text") {
			$args = array($dirty_attributes);
		}
		else {
			$args = array($dirty_attributes,$filters);
		}
		$purified_attributes = call_user_func_array(array("H",$purify_method),$args);
		$this->set_attributes($purified_attributes);
	}
	/**
	 * Return the id of the model instance
	 *
	 * @return mixed
	 * @author Lee O'Mara
	 * @author Peter Epp
	 */
	function id() {			return $this->get_attribute('id'); }
	/**
	 * Build an instance of a model from a database query
	 *
	 * @param string $model Name of the model to create an instance of
	 * @param string $query Query to build the instance from
	 * @return object
	 * @author Lee O'Mara
	 * @author Peter Epp
	 */
	function model_from_query($model,$query) {
		$db_record = DB::fetch_one($query);
		if ($db_record !== false) {
			return new $model($db_record);
		}
		return false;
	}
	/**
	 * Build a collection of instances of models from a database query
	 *
	 * @param string $model The name of the model to create instances of
	 * @param string $query Query to build the instances from
	 * @return array Collection of objects
	 * @author Lee O'Mara
	 * @author Peter Epp
	 */
	function models_from_query($model,$query) {
		$models = array();
		$rows = DB::fetch($query);
		if ($rows !== false) {
			foreach ($rows as $db_record) {
				$models[] = new $model($db_record);
			}
			return $models;
		}
		return false;
	}
	/**
	 * Save data to the object's defined database table
	 *
	 * @param array $data Associative array of data with keys matching the database columns to store
	 * @return void
	 * @author Peter Epp
	 */
	function save() {
		if (!$this->has_been_validated() && !$this->validate()) {
			return false;
		}
		$data = $this->get_attributes();
		if (method_exists($this,'save_filter')) {
			$data = $this->save_filter($data);
		}
		if (!empty($data['id'])) {
			// If id is included in array, update the existing row
			Console::log("                        Updating existing data...");
			$id = (int)$data['id'];
			unset($data['id']);
			$query = "UPDATE `".$this->db_tablename()."` SET ";
		}
		else {
			// Otherwise insert a new row
			Console::log("                        Inserting new data...");
			$query = "INSERT INTO `".$this->db_tablename()."` SET ";
		}
		//  Add the data to the query string:
		$query .= DB::safe_query_from_data($data);
		if (!empty($id)) {
			$query .= " WHERE `id` = ".$id;
			Console::log("                        Query: ".$query);
			$result = DB::query($query);		// True or false
		}
		else {
			Console::log("                        Query: ".$query);
			$result = DB::insert($query);		// False or new row id
			if ($result) {
				$this->set_attribute('id',$result);
			}
		}
		if (!$result) {
			$this->set_error('Database update query failed: '.DB::error());
		}
		return $result;
	}
	/**
	 * Delete a row from the DB table for the current model
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	function delete() {
		$db_table = $this->db_tablename();
		$id = $this->id();
		return DB::query("DELETE FROM {$db_table} WHERE id = {$id}");
	}
	/**
	 * Add an error message to the array
	 *
	 * @param string $message 
	 * @return void
	 * @author Peter Epp
	 */
	function set_error($message) {
		$this->_error_messages[] = $message;
	}
	/**
	 * Return the _error_messages property
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function errors() {
		if (!empty($this->_error_messages)) {
			return $this->_error_messages;
		}
		return false;
	}
}
?>