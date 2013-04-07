<?php
/**
 * undocumented class
 *
 * @package Plugins
 * @author Your Name
 */
class MyModel extends AbstractModel {
	/**
	 * Find one item in the database
	 *
	 * @param mixed $id 
	 * @return object Instance of this model
	 * @author Your Name
	 */
	function find($id) {
		$id = (int)$id;
		return MyModel::item_from_query("SELECT * FROM my_table WHERE id = ".$id);
	}
	/**
	 * Find all items in the database
	 *
	 * @param int $album_id 
	 * @return void
	 * @author Peter Epp
	 */
	function find_all() {
		$query = "SELECT * FROM my_table ORDER BY my_sort_column";
		return MyModel::item_from_query($query);
	}
	// Put your getters here, eg:
	function attribute_name() {		return $this->get_attribute('attribute_name');		}
	/**
	 * Validate user input
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function validate() {
		// Put your validation code here, eg:
		if ($this->attribute_name() == null) {
			$this->set_error("Please provide this attribute");
		}
		return (!$this->errors());
	}
	/**
	 * Resort all items in a database table from a sorting array.
	 *
	 * @param array $sort_list An indexed array with elements in the format $sort_list[$sort_index] = $db_primary_key
	 * @return void
	 * @author Peter Epp
	 */
	function resort($sort_list) {
		foreach ($sort_list as $index => $id) {
			$store_index = $index+1;
			DB::query("UPDATE my_table SET my_sort_column = {$store_index} WHERE my_primary_key = {$id}");
		}
	}
	/**
	 * Build an object from a database query
	 *
	 * @param string $query Database query
	 * @return object
	 * @author Peter Epp
	 */
	function item_from_query($query) {
		return parent::model_from_query("MyModel",$query);
	}
	/**
	 * Build a collection of objects from a database query
	 *
	 * @param string $query Database query
	 * @return array Collection of news/event objects
	 * @author Peter Epp
	 */
	function items_from_query($query) {
		return parent::models_from_query("MyModel",$query);
	}
	function db_tablename() {
		return 'my_table';
	}
	function db_create_query() {
		return 'CREATE TABLE  `my_table` (
		`id` INT( 8 ) NOT NULL AUTO_INCREMENT PRIMARY KEY
		... my other column definitions ...
		) TYPE = MyISAM';
	}
}
?>
