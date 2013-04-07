<?php
/**
 * Factory for instantiating models in different ways. This class is used by AbstractModuleController to automatically instantiate factories for each
 * model used by the module's controller.  See the documentation on AbstractModuleController::load_models() for more detail.
 *
 * Here is some example usage if you were using the PhotoGallery module, which uses the Photo model.
 *
 * From within action methods of the controller:
 *
 * $my_photo = $this->Photo->find($this->params['id']);
 * $my_photos = $this->Photo->find_all();
 * $this->set_view_var("photos",$this->Photo->find_all(array("sort_order" => "ASC")));
 *
 * From anywhere else:
 *
 * $photo_factory = ModelFactory::instance("Photo");
 * $new_photo = $photo_factory->create($attributes);
 * $photos = $photo_factory->models_from_query("SELECT ph.*, pc.subject, pc.comments, pc.author FROM custom_photos ph LEFT JOIN photo_comments pc ON (pc.photo_id = ph.id) ORDER BY ph.sort_order ASC");
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: model_factory.php 14357 2011-10-28 22:23:04Z teknocat $
 */
class ModelFactory {
	/**
	 * Flag to pass to instance method to override existing model factory if desired
	 */
	const OVERRIDE_EXISTING = true;
	/**
	 * Name of the model that this factory instance is for
	 *
	 * @var string
	 */
	protected $_model;
	/**
	 * Descriptions of all the attributes in the database
	 *
	 * @var string
	 */
	protected $_attr_descriptors;
	/**
	 * Place to cache table descriptions
	 *
	 * @author Peter Epp
	 */
	private static $_table_descriptors;
	/**
	 * Array of factor instances
	 *
	 * @author Peter Epp
	 */
	private static $_instances = array();
	/**
	 * Constructor. Stores the model name
	 *
	 * @param string $model_name 
	 * @author Peter Epp
	 */
	protected function __construct($model_name = null) {
		if (!$model_name) {
			$my_name = get_class($this);
			if ($my_name != 'ModelFactory') {
				$model_name = substr($my_name,0,-7);
			} else {
				trigger_error("Model Factory: Model name not provided!",E_USER_ERROR);
			}
		}
		$this->_model = $model_name;
	}
	/**
	 * Return a singleton instance of a factory for a given model
	 *
	 * @param string $model_name 
	 * @return self
	 * @author Peter Epp
	 */
	public static function instance($model_name, $override_existing = false) {
		if (empty(self::$_instances[$model_name]) || $override_existing) {
			$custom_factory = $model_name.'Factory';
			if (class_exists($custom_factory)) {
				self::$_instances[$model_name] = new $custom_factory();
			} else {
				self::$_instances[$model_name] = new self($model_name);
			}
		}
		return self::$_instances[$model_name];
	}
	/**
	 * Read the table meta data to get the column descriptors
	 *
	 * @param string $table_name 
	 * @return array
	 * @author Peter Epp
	 */
	private static function _get_table_descriptors($table_name) {
		if (empty(self::$_table_descriptors[$table_name])) {
			self::$_table_descriptors[$table_name] = DB::fetch("DESCRIBE `".$table_name."`");
		}
		return self::$_table_descriptors[$table_name];
	}
	/**
	 * Cache descriptive information about the model attributes using the DB table meta data
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function _attribute_descriptors() {
		if (empty($this->_attr_descriptors)) {
			$column_descriptors = self::_get_table_descriptors($this->db_table());
			foreach ($column_descriptors as $index => $descriptor) {
				$this->_attr_descriptors[$descriptor['Field']] = array(
					'type'        => $descriptor['Type'],
					'is_required' => (strtolower($descriptor['Null']) != 'yes'),
					'default'     => $descriptor['Default'],
					'value'       => null
				);
			}
		}
		return $this->_attr_descriptors;
	}
	/**
	 * Instantiate a new model, with or without attributes
	 *
	 * @param array $attributes Optional default attributes, such as data from a db query
	 * @return void
	 * @author Peter Epp
	 */
	public function create($attributes = array()) {
		$model_name = $this->_model;
		if (class_exists($model_name)) {
			$model = new $model_name();
		} else {
			$model = new GenericModel($model_name);
		}
		$model->define_attributes($this->_attribute_descriptors());
		$this->define_associated_model_attributes($model);
		$model->set_attributes($attributes);
		return $model;
	}
	/**
	 * Define attributes on the model for the other models it is associated with
	 *
	 * @param string $model 
	 * @return void
	 * @author Peter Epp
	 */
	private function define_associated_model_attributes($model) {
		$associated_models = $model->associated_models();
		if (!empty($associated_models)) {
			$other_attributes = array();
			foreach ($associated_models as $model_name) {
				if (method_exists($model_name,'db_tablename')) {
					$db_table = call_user_func(array($model_name,'db_tablename'));
				} else {
					$db_table = AkInflector::tableize($model_name);
				}
				$assoc_col_descriptors = self::_get_table_descriptors($db_table);
				$associated_attributes = $model->associated_attributes($model_name);
				foreach ($assoc_col_descriptors as $index => $descriptor) {
					if (!$associated_attributes || in_array($descriptor['Field'],$associated_attributes)) {
						$other_attributes[$descriptor['Field']] = array(
							'type'        => $descriptor['Type'],
							'is_required' => ($descriptor['Null'] != 'YES'),
							'default'     => $descriptor['Default'],
							'value'       => null
						);
					}
				}
			}
			if (!empty($other_attributes)) {
				$model->define_other_attributes($other_attributes);
			}
		}
	}
	/**
	 * Instantiate a model for one database record
	 *
	 * @param string $model_name 
	 * @param string $id 
	 * @return object
	 * @author Peter Epp
	 */
	public function find($id) {
		$db_table = $this->db_table();
		$query = "SELECT * FROM `{$db_table}` WHERE `id` = ?";
		return $this->model_from_query($query, $id);
	}
	/**
	 * Instantiate models for multiple database records
	 *
	 * @param string $sort_col 
	 * @param string $sort_dir 
	 * @return array An array of object instances
	 * @author Peter Epp
	 */
	public function find_all($sort_cols = array(),$extra_conditions = '',$limit = '') {
		$db_table = $this->db_table();
		$sort_params = $this->compile_sort_params($sort_cols);
		if (!empty($extra_conditions)) {
			$extra_conditions = " WHERE ".$extra_conditions;
		}
		if (!empty($limit)) {
			$limit = " LIMIT ".$limit;
		}
		$query = "SELECT * FROM `{$db_table}`".$extra_conditions.$sort_params.$limit;
		return $this->models_from_query($query);
	}
	/**
	 * Instantiate one model for a value match on any given column. ONLY use this when matching unique columns.
	 *
	 * @param string $column_name 
	 * @param string $column_value 
	 * @return object
	 * @author Peter Epp
	 */
	public function find_by($column_name,$column_value,$extra_conditions = '') {
		$db_table = $this->db_table();
		$query = "SELECT * FROM `{$db_table}` WHERE {$column_name} = ?";
		if (!empty($extra_conditions)) {
			$query .= " AND ".$extra_conditions." LIMIT 1";
		}
		return $this->model_from_query($query,$column_value);
	}
	/**
	 * Instantiate multiple models for a value match on any give column.
	 *
	 * @param string $column_name 
	 * @param string $column_value 
	 * @param array $sort_cols An associative array with column names as the keys and the sort directions as the values, for example: array("first_name" => "ASC","last_name" => "DESC"). Leaving the value blank will cause default sort direction to be used (usually ASC)
	 * @return array Array of model objects
	 * @author Peter Epp
	 */
	public function find_all_by($column_name,$column_value,$sort_options = array(),$extra_conditions = '', $limit = '') {
		$db_table = $this->db_table();
		$sort_params = $this->compile_sort_params($sort_options);
		$params = array(':column_value' => $column_value);
		if (!empty($extra_conditions)) {
			$extra_conditions = " AND ".$extra_conditions;
		}
		if (!empty($limit)) {
			$limit = " LIMIT ".$limit;
		}
		$query = "SELECT * FROM `{$db_table}` WHERE {$column_name} = :column_value".$extra_conditions.$sort_params.$limit;
		return $this->models_from_query($query,$params);
	}
	/**
	 * Return all models for has one and belongs to many relationship - finds all the ones that the primary one has
	 *
	 * @param string $table_name 
	 * @param string $primary_model_id_attribute 
	 * @param string $primary_model_id 
	 * @param string $other_model_name 
	 * @param string $other_model_id_attribute 
	 * @return void
	 * @author Peter Epp
	 */
	public static function find_all_related($table_name, $primary_model_id_attribute, $primary_model_id, $other_model_name, $other_model_id_attribute, $sort_options = array(), $extra_conditions = '', $limit = '') {
		$other_model_ids_query = "SELECT `{$other_model_id_attribute}` FROM {$table_name} WHERE `{$primary_model_id_attribute}` = {$primary_model_id}";
		$conditions = "`id` IN ({$other_model_ids_query})";
		if (!empty($extra_conditions)) {
			$conditions .= " AND ".$extra_conditions;
		}
		return self::instance($other_model_name)->find_all($sort_options,$conditions,$limit);
	}
	/**
	 * Resort all rows in the database according to a sort array.
	 *
	 * @param string $sort_list An array of row ID's in the new order, as produced by a prototype JS sortable. The indices of this array are what will be used for the new sort order.
	 * @param string $sort_column Name of the database column containing the sort order. Defaults to "sort_order".
	 * @return void
	 * @author Peter Epp
	 */
	public function resort($sort_list, $sort_column = "sort_order") {
		$db_table = $this->db_table();
		foreach ($sort_list as $index => $id) {
			$sort_index = $index+1;
			DB::query("UPDATE `{$db_table}` SET `{$sort_column}` = ? WHERE `id` = ?", array($sort_index, $id));
		}
		$last_update = date("Y-m-d H:i:s");
		$model_name = $this->_model;
		DB::query("REPLACE INTO `model_last_updated` SET `model_name` = '{$model_name}', `date` = '{$last_update}'");
	}
	/**
	 * Return the row count for the current model's DB table
	 *
	 * @return int
	 * @author Peter Epp
	 */
	public function record_count($extra_conditions = '') {
		$db_table = $this->db_table();
		$query = "SELECT COUNT(*) FROM `{$db_table}`";
		if (!empty($extra_conditions)) {
			$query .= ' WHERE '.$extra_conditions;
		}
		return (int)DB::fetch_one($query);
	}
	/**
	 * Return the next X highest value of a given column
	 *
	 * @param string $column_name 
	 * @param int $increment How much the next highest should increment by
	 * @return void
	 * @author Peter Epp
	 */
	public function next_highest($column_name,$increment = 1,$extra_conditions = '') {
		$db_table = $this->db_table();
		$query = "SELECT MAX(`{$column_name}`)+{$increment} FROM `{$db_table}`";
		if (!empty($extra_conditions)) {
			$query .= " WHERE ".$extra_conditions;
		}
		$next_value = (int)DB::fetch_one($query);
		if (empty($next_value)) {
			$next_value = $increment;
		}
		return $next_value;
	}
	/**
	 * Trash the entire table contents and reset the auto-increment value to 1
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public function trash($extra_conditions = '') {
		$db_table = $this->db_table();
		$query = "DELETE FROM `{$db_table}`";
		if (!empty($extra_conditions)) {
			$query .= ' WHERE '.$extra_conditions;
		}
		if (DB::query($query) && empty($extra_conditions)) {
			DB::query("ALTER TABLE `{$db_table}` AUTO_INCREMENT=1");
		}
	}
	/**
	 * Compile the sort options into string that can be appended to a query
	 *
	 * @param array $sort_cols List of column names
	 * @param array $sort_dirs List of sort directions (ie. ASC, DESC), one for each sort column respectively
	 * @return string
	 * @author Peter Epp
	 */
	protected function compile_sort_params($sort_cols) {
		if ($sort_cols == 'random') {
			return " ORDER BY RAND()";
		}
		if (empty($sort_cols)) {
			return '';
		}
		$db_table = $this->db_table();
		$sort_params = '';
		$index = 0;
		foreach ($sort_cols as $column_name => $direction) {
			if (empty($column_name)) {
				trigger_error("Column name for sort columns is empty!", E_USER_ERROR);
			}
			if (!empty($direction) && strtoupper($direction) != "ASC" && strtoupper($direction) != "DESC") {
				trigger_error("Invalid db sort direction: ".$direction, E_USER_NOTICE);
				$direction = '';
			}
			$sort_params[] = "`{$column_name}` {$direction}";
			$index++;
		}
		return " ORDER BY ".implode(", ",$sort_params);
	}
	/**
	 * Build an instance of a model from a database query
	 *
	 * @param string $model Name of the model to create an instance of
	 * @param string $query Query to build the instance from
	 * @return object
	 * @author Lee O'Mara
	 * @author Peter Epp
	 */
	public function model_from_query($query, $params = array()) {
		$model_name = $this->_model;
		$db_record = DB::fetch_one($query, $params);
		if (!$db_record) {
			return false;
		}
		return $this->create($db_record);
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
	public function models_from_query($query, $params = array()) {
		$model_name = $this->_model;
		$db_records = DB::fetch($query, $params);
		if (empty($db_records)) {
			return false;
		}
		$models = array();
		foreach ($db_records as $db_record) {
			$models[] = $this->create($db_record);
		}
		return $models;
	}
	/**
	 * Determine the table name for this factory's model. If the model contains a static method called "db_tablename" it will return the value of that,
	 * otherwise it will return the tableized model name using AKInflector.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function db_table() {
		if (class_exists($this->_model) && method_exists($this->_model,"db_tablename")) {
			return call_user_func(array($this->_model,"db_tablename"));
		} else {
			return AkInflector::tableize(Crumbs::normalized_model_name($this->_model));
		}
	}
}
?>
