<?php
require_once("plugins/PageContent.php");
/**
 * Controller for the PageContent plugin which allows arbitrary editing of static page content
 * 
 * @author Peter Epp
 * @version 
 * @copyright 
 * @package Plugins
 **/
class PageContentManager extends AbstractPluginController {
	/**
	 * List of other plugins this one is dependent on
	 *
	 * @var array
	 */
	var $dependencies = array("Authenticator","PageManager");
	/**
	 * Run the plugin
	 * You only need this function if there are JS or CSS files to register, and/or you want to do some custom setup before dispatching to an action.  Otherwise toss it.
	 *
	 * @author Peter Epp
	 */
	function run($params) {
		if ($this->dependencies_met() && PluginCore::static_plugin_exists("H","is_installed")) {
			$this->Biscuit->register_js("content_editor.js");
			parent::run($params); // dispatch to action_...
		}
		else {
			Console::log("                        PageContentManager died because it can't live without Authenticator and HTML Purifier");
		}
	}
	/**
	 * Index action - by default find all items in the database and render
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_index() {
		// Rely on the ID of the current page for retrieving the page content
		$this->page_data = PageContent::find($this->Biscuit->page_id);
		$this->render();
	}
	/**
	 * Secondary role is to just grab the page data from the database but don't render, and only when action is "index" and we're not on the "page-manager" page
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_secondary() {
		if ($this->params['action'] == "index" && $this->Biscuit->page_name != "page-manager") {
			$this->page_data = PageContent::find($this->Biscuit->page_id);
		}
	}
	/**
	 * Call validation and return a response for use by the Biscuit JS Ajax validation
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_ajax_validate() {
		Console::log('                        Performing AJAX validation for '.get_class($this));
		if (!empty($this->params['content_data'])) {
			// grab from user input
			$content = new PageContent($this->params['content_data']);
			if ($content->validate()) {
				$output = '+OK';
			}
			else {
				Session::flash_unset('user_message');
				$output = "Please make the following corrections:\n\n-".implode("\n-",$content->errors());
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
		$page_data = BiscuitModel::find_by_id($this->params['id']);
		$this->title("Edit &ldquo;".$page_data['title']."&rdquo; Page");
		// Grab existing:
		$content = PageContent::find($this->params['id']);
		if (!empty($this->params['content_data'])) {
			// Replace attributes with user input:
			$content->set_attributes($this->params['content_data']);
		}
		if (Request::is_post()) {
			if ($content->save()) {
				$this->success_save_response($this->url('index',$content->id()));
			}
			else {
				$this->failed_save_response($content,"content");
			}
		}
		else {
			$this->content = &$content;
			$this->render();
		}
	}
	/**
	 * Enforce the presence of some data(notably ID) for certain actions. This function
	 * is called before the action by AbstractPluginController#run
	 *
	 * @return boolean
	 **/
	function before_filter() {
		if (in_array($this->params['action'], array('edit'))) {
			// require ID
			return (!empty($this->params['id']));
		}
		return true;
	}
	/**
	 * Return the URL based on the current action. Redefine this function in your plugin if you have special cases
     * 
     * The default URL is the index action.
	 *
	 * @static
	 * @param string $action (optional)
	 * @param integer $id (optional)
	 * @return string root relative URL
	 **/
	function url($action=null, $id=null) {
		switch ($action) {
			case 'edit':
			case 'delete':
				if (!$id) {
					Console::log("                        URL for '".$action."' action requires an id");
				}
				return "/content_editor/".$action."/".$id;
				break;
			case 'index':
			default:
				if (!empty($this->params['id']) && !$id) {
					$id = $this->params['id'];
				}
				if (!$id) {
					Console::log("                        URL for '".$action."' action requires an id");
				}
				$page_data = BiscuitModel::find_by_id($id);
				return "/".$page_data['shortname'];
				break;
		}
	}
	/**
	 * Supply the name (or names) of the database table(s) used by this plugin
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function db_tablename() {
		return PageContent::db_tablename();
	}
	/**
	 * Return the query to create a new table for this plugin
	 *
	 * @param mixed $table_name Either an array or string containing the names of the tables used by the plugin
	 * @return void
	 * @author Peter Epp
	 */
	function db_create_query($table_name) {
		return PageContent::db_create_query($table_name);
	}
}
?>