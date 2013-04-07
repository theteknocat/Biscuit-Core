<?php
/**
 * News/Events plugin
 *
 * @author Peter Epp, Lee O'Mara
 * @version $Id: NewsAndEvents.php 1074 2008-03-04 21:18:09Z lee $
 * @copyright Kellett Communications,  3 December, 2007
 * @package Plugins
 **/
// TODO incorporate time as well as date in edit forms and output
class NewsAndEventsManager extends AbstractPluginController {
	/**
	 * List of other plugins this one is dependent on
	 *
	 * @var array
	 */
	var $dependencies = array("Authenticator","Prototype");
	/**
	 * The type of data - news or events
	 * @var Data Type
	**/
	var $data_type;
	/**
	 * Set model name and include the model class, allowing an extension-point for the model.
	 *
	 * @param string $model_name 
	 * @return void
	 * @author Peter Epp
	 */
	function NewsAndEventsManager($model_name = "NewsAndEvents") {
		require_once("plugins/".$model_name.".php");
		$this->model_name = $model_name;
	}
	function run($params) {
		if ($this->dependencies_met()) {
			$this->Biscuit->register_js("news_and_events.js");
			if ($this->Biscuit->page_name == "news" || $this->Biscuit->page_name == "events") {
				// Set data type based on page name:
				$this->data_type = $this->Biscuit->page_name;
			}
			elseif (!empty($params['data_type'])) {
				// This allows overriding the default
				$this->data_type = $params['data_type'];
			}
			else {
				// Default to "news" if data type cannot be determined:
				$this->data_type = "news";
			}
			$this->params['data_type'] = $this->data_type;
			parent::run($params); // dispatch to action_...
		}
		else {
			Console::log("                        NewsAndEvents died because it can't live without Authenticator");
		}
	}

	function action_index() {
		$include_expired = ($this->user_can_edit());
		$this->items = $this->Model("find_all",array($this->data_type,$include_expired));
		$this->render();
	}

	function action_show() {
		// View a single news article
		$this->item = $this->Model("find",array($this->params['id']));
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
			$item_data = $this->params['news_event'];
			$item_data['data_type'] = $this->data_type;
			if (!empty($this->params['id'])) {
				$item_data['id'] = $this->params['id'];
			}
			$model_name = $this->model_name;
			$item = new $model_name($item_data);
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
	function action_edit() {
		$this->title("Edit ".(($this->data_type == "events") ? 'Event' : 'News Item'));
		// require ID
		// Grab existing:
		$news_event = $this->Model("find",array($this->params['id']));
		if (empty($this->params['news_event'])) {
			// Reformat the date:
			$news_event->set_attribute('date',Crumbs::date_format($news_event->date(),"d-M-Y"));
			if ($news_event->expiry() == "0000-00-00") {
				$news_event->set_attribute('expiry','');
			}
			else {
				// Reformat the expiry date:
				Console::log("Reformatting expiry date: ".$news_event->expiry());
				$news_event->set_attribute('expiry',Crumbs::date_format($news_event->expiry(),"d-M-Y"));
			}
		} else {
			// grab from user input
			$item_data = $this->params['news_event'];
			$item_data['id'] = $this->params['id'];
			// Replace attributes with user input:
			$news_event->set_attributes($item_data);
		}
		$news_event->set_attribute('data_type',$this->data_type);
		if (Request::is_post()) {
			if ($news_event->save($this->upload_path())) {
				$this->success_save_response($this->url());
			}
			else {
				$this->failed_save_response($news_event,"item");
			}
		}
		else {
			$this->item = &$news_event;
			$this->render();
		}
	}

	function action_new() {
		$this->title("New ".(($this->data_type == "events") ? 'Event' : 'News Item'));
		$item_data = array();
		if (!empty($this->params['news_event'])) {
			// grab from user input
			$item_data = $this->params['news_event'];
			$item_data['data_type'] = $this->data_type;
		}
		// Create a new news/event from the user input:
		$model_name = $this->model_name;
		$news_event = new $model_name($item_data);
		if (Request::is_post()) {
			if ($news_event->save($this->upload_path())) {
				$this->success_save_response($this->url());
			}
			else {
				$this->failed_save_response($news_event,"item");
			}
		}
		else {
			$this->item = &$news_event;
			$this->render();
		}
	}
	/**
	 * Delete an item and return a response based on the success or failure of the delete operation
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_delete() {
		if (!$this->item_delete($this->params['id'])) {
			Session::flash('user_message', "Failed to remove ".$this->data_type." item");
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
	 * Delete a news/event item
	 *
	 * @param integer news/event ID
	 * @return boolean success?
	 **/
	function item_delete($id) {
		$news_event = $this->Model("find",array($id));
		if ($news_event->delete()) {
			if ($news_event->attachment() != null && is_file($this->item_full_file_path($news_event)) && @!unlink($this->item_full_file_path($news_event))){
				Console::log("                        Failed to remove file ". $news_event->attachment());
			}
			return true;
		}
		return false;
	}
	/**
	 * Set the view file based on the current data_type (news or events)
	 *
	 * @return void
	 **/
	function render() {
		$action_name = $this->params['action'];
		if ($action_name == 'new') {
			$action_name = 'edit';
		}
		if (!empty($this->data_type)) {
			$action_name .= '_'.$this->data_type;
		}
		parent::render($action_name);
	}
	/**
	 * Enforce the presence of some data(notably ID) for certain actions. This function
     * is called before the action by AbstractPluginController#run
	 *
	 * @return boolean
	 **/
	function before_filter() {
		if (in_array($this->params['action'], array('show', 'edit', 'delete'))) {
			// require ID
			return (!empty($this->params['id']));
		}
		return true;
	}

	function item_file_path(&$news_event){
		if ($news_event->attachment()) {
			return $this->upload_path(). "/" . $news_event->attachment();
		}
		return false;
	}
	
	/**
	 * Return the full path on the local file system
	 *
	 * @param array Publication
	 * @return string
	 **/
	function item_full_file_path(&$news_event) {
		return SITE_ROOT.$this->item_file_path($news_event);
	}
	/**
	 * Function to grab date and size of a file attachment
	 * @param object $item The news or event item
	 * @return array Or boolean - array of data if file exists, false if file does not exist
	**/
	function file_info(&$news_event) {
		Console::log("                        Checking for news/event file attachment: ".$this->item_full_file_path($news_event));
		if ($news_event->attachment() != null && file_exists($this->item_full_file_path($news_event))) {
			Console::log("                        File found: ".$this->item_full_file_path($news_event));
			$size = intval(filesize($this->item_full_file_path($news_event))/ 1024);
			if ($size < 1) {
				$file_size = 'Less than 1kb';
			} else {
				$file_size = $size . 'kb';
			}
			$file_date = date('M j Y', filemtime($this->item_full_file_path($news_event)));
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
		return $this->Model("db_tablename",array());
	}
	/**
	 * Return the query to create a new table for this plugin
	 *
	 * @param mixed $table_name Either an array or string containing the names of the tables used by the plugin
	 * @return void
	 * @author Peter Epp
	 */
	function db_create_query($table_name) {
		return $this->Model("db_create_query",array($table_name));
	}
}
?>