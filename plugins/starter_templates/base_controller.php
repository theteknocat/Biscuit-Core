<?php
require_once("plugins/MyModel.php");
/**
 * undocumented class
 * 
 * @author Peter Epp
 * @version 
 * @copyright 
 * @package Plugins
 **/
class MyModelManager extends AbstractPluginController {
	/**
	 * List of other plugins this one is dependent on
	 *
	 * @var array
	 */
	var $dependencies = array("Authenticator");
	/**
	 * Run the plugin
	 * You only need this function if there are JS or CSS files to register, and/or you want to do some custom setup before dispatching to an action.  Otherwise toss it.
	 *
	 * @author Peter Epp
	 */
	function run($params) {
		if ($this->dependencies_met()) {
			// Register a JS file:
			$this->Biscuit->register_js("js_file.js");
			// Register a CSS file:
			$this->Biscuit->register_css(array('filename' => 'MyCSSFile.css','media' => 'screen, projection, print'));
			parent::run($params); // dispatch to action_...
		}
		else {
			Console::log("                        MyModelManager died because it can't live without [dependency names]");
		}
	}
	/**
	 * Index action - by default find all items in the database and render
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_index() {
		$this->items = MyModel::find_all();
		$this->render();
	}
	/**
	 * Show action - retrieve one item from the database and render
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_show() {
		$this->item = MyModel::find($this->params['id']);
		$this->render();
	}
	/**
	 * Call validation and return a response for use by the Biscuit JS Ajax validation
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_ajax_validate() {
		Console::log('                        Performing AJAX validation for '.get_class($this));
		if (!empty($this->params)) {
			// grab from user input
			$item_data = array(
				'param_name'     => $this->params['my_param_set']['param_name']
			);
			if (!empty($this->params['id'])) {
				$item_data['id'] = $this->params['id'];
			}
			$item = new MyModel($item_data);
			if ($item->validate()) {
				$output = '+OK';
			}
			else {
				Session::flash_unset('user_message');
				$output = "Please make the following corrections:\n\n-".implode("\n-",$item->errors());
			}
		}
		else {
			$output = 'No data submitted!';
		}
		Console::log('                        Validation result: '.$output);
		$this->Biscuit->render($output);
	}
	/**
	 * Edit an item
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_edit() {
		$this->title("Edit item");
		// Grab existing:
		$item = MyModel::find($this->params['id']);
		if (!empty($this->params['my_param_set'])) {
			// grab from user input
			$item_data = array(
				'id'                => $this->params['id'],
				'param_name'        => $this->params['my_param_set']['param_name']
			);
			// Replace attributes with user input:
			$item->set_attributes($item_data);
		}
		if (Request::is_post()) {
			if ($item->save($this->upload_path())) {
				$this->success_save_response($this->url());
			}
			else {
				$this->failed_save_response($item,"item");
			}
		}
		else {
			$this->item = &$item;
			$this->render();
		}
	}
	/**
	 * Create a new item
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_new() {
		$this->title("New item");
		if (empty($this->params['my_param_set'])) {
			// create default, empty item
			$item_data = array(
				'param_name'     => ''
			);
		} else {
			// grab from user input
			$item_data = array(
				'param_name'     => $this->params['my_param_set']['param_name']
			);
		}
		// Create a new item from the user input:
		$item = new MyModel($item_data);
		if (Request::is_post()) {
			if ($item->save($this->upload_path())) {
				$this->success_save_response($this->url());
			}
			else {
				$this->failed_save_response($item,"item");
			}
		}
		else {
			$this->item = &$item;
			$this->render();
		}
	}
	/**
	 * Tell the model to resort it's database table according to a sorting array.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_resort() {
		MyModel::resort($this->params['my_sort_array']);
	}
	/**
	 * Delete an item and return a response based on the success or failure of the delete operation
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_delete() {
		if (!$this->item_delete($this->params['id'])) {
			Session::flash('user_message', "Failed to remove item");
		}
		if (!Request::is_ajax()) {
			Response::redirect($this->url());
		}
		else {
			$this->params['action'] = 'index';
			$this->action_index();
		}
	}
	/**
	 * Delete an item
	 *
	 * @param integer ID
	 * @return boolean success?
	 **/
	function item_delete($id) {
		$item = MyModel::find($id);
		if ($item->delete()) {
			// Use this if there's a file attachment to remove, otherwise toss it
			if ($item->attachment_attribute_name() != null && is_file($this->item_full_file_path($item)) && @!unlink($this->item_full_file_path($item))){
				Console::log("                        Failed to remove file ". $item->attachment_attribute_name());
			}
			return true;
		}
		return false;
	}
	/**
	 * Enforce the presence of some data(notably ID) for certain actions. This function
	 * is called before the action by AbstractPluginController#run
	 *
	 * @return boolean
	 **/
	function before_filter() {
		if (in_array($this->params['action'], array('edit', 'delete', 'download'))) {
			// require ID
			return (!empty($this->params['id']));
		}
		return true;
	}
	/**
	 * Return the file path for an attachment.  Toss this function if your plugin doesn't use attachments.
	 *
	 * @param string $item 
	 * @return void
	 * @author Peter Epp
	 */
	function item_file_path(&$item){
		if ($item->attachment_attribute_name()) {
			return $this->upload_path(). "/" . $item->attachment_attribute_name();
		}
		return false;
	}
	/**
	 * Return the full path on the local file system.  Toss this function if your plugin doesn't use attachments.
	 *
	 * @param object A reference to the model
	 * @return string
	 **/
	function item_full_file_path(&$item) {
		return SITE_ROOT.$this->item_file_path($item);
	}
	/**
	 * Function to grab date and size of a file attachment.  Toss this function if your plugin doesn't use attachments.
	 * @param object $item A reference to the model
	 * @return mixed Array of data if file exists, false if file does not exist
	**/
	function file_info(&$item) {
		Console::log("                        Checking for file attachment: ".$this->item_full_file_path($item));
		if ($item->attachment_attribute_name() != null && file_exists($this->item_full_file_path($item))) {
			Console::log("                        File found: ".$this->item_full_file_path($item));
			$size = intval(filesize($this->item_full_file_path($item))/ 1024);
			if ($size < 1) {
				$file_size = 'Less than 1kb';
			} else {
				$file_size = $size . 'kb';
			}
			$file_date = date('M j Y', filemtime($this->item_full_file_path($item)));
			return array("file_size" => $file_size,"file_date" => $file_date);
		}
		else {
			Console::log("                        File not found, moving on...");
			return false;
		}
	}
	/**
	 * Supply the name (or names) of the database table(s) used by this plugin
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function db_tablename() {
		return MyModel::db_tablename();
	}
	/**
	 * Return the query to create a new table for this plugin
	 *
	 * @param mixed $table_name Either an array or string containing the names of the tables used by the plugin
	 * @return void
	 * @author Peter Epp
	 */
	function db_create_query($table_name) {
		return MyModel::db_create_query($table_name);
	}
}
?>