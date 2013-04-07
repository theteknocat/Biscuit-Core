<?php
require_once("plugins/PageIndex.php");
/**
 * Controller for managing adding and removing publicly accessible, content-managed pages
 *
 * @package Plugins
 * @author Peter Epp
 */
class PageManager extends AbstractPluginController {
	/**
	 * List of other plugins this one is dependent on
	 *
	 * @var array
	 */
	var $dependencies = array("Authenticator","PageContentManager");
	/**
	 * Run the plugin
	 * You only need this function if there are JS or CSS files to register, and/or you want to do some custom setup before dispatching to an action.  Otherwise toss it.
	 *
	 * @author Peter Epp
	 */
	function run($params) {
		if ($this->dependencies_met()) {
			// Register a JS file:
			$this->Biscuit->register_js("page_manager.js");
			parent::run($params); // dispatch to action_...
		}
		else {
			Console::log("                        PageManager died because it can't live without Authenticator");
		}
	}
	/**
	 * Index action - by default find all items in the database and render
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_index() {
		$this->set_view_var('pages',PageIndex::find_all());
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
			$item_data = $this->params['page_data'];
			if (!empty($this->params['id'])) {
				$item_data['id'] = $this->params['id'];
			}
			$item = new PageIndex($item_data);
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
	 * Create a new item
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_new() {
		$this->title("New Page");
		if (empty($this->params['page_data'])) {
			// create default, empty item
			$item_data = array();
		} else {
			// grab from user input
			$item_data = $this->params['page_data'];
		}
		// Create a new item from the user input:
		$page = new PageIndex($item_data);
		if (Request::is_post()) {
			if ($page->save()) {
				$this->success_save_response($this->url());
			}
			else {
				$this->failed_save_response($page,"page");
			}
		}
		else {
			$this->set_view_var("page",$page);
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
		$page = PageIndex::find($id);
		return $page->delete();
	}
	/**
	 * Render option tags for all publicly accessible content managed pages to go in a "select" element
	 *
	 * @param string $id 
	 * @return void
	 * @author Peter Epp
	 */
	function render_parent_section_options($parent_id = null,$exclude_path = null,$separate_menu_name = 'Separate Menu') {
		$pages = PageIndex::find_all($exclude_path);
		$parent_options = '<option value="9999999"'.(($parent_id == 999999) ? ' selected="selected"' : '').'>None (Exclude From Menus)</option>
<option value="999999"'.(($parent_id == 999999) ? ' selected="selected"' : '').'>'.$separate_menu_name.'</option>
<option value="0"'.(($parent_id == 0) ? ' selected="selected"' : '').'>Main</option>';
		if (!empty($pages)) {
			foreach ($pages as $index => $page) {
				$shortname_bits = explode("/",$page->shortname());
				$indent = count($shortname_bits);
				$indent_str = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;',$indent);
				$parent_options .= '<option value="'.$page->id().'"'.(($page->id() == $parent_id) ? ' selected="selected"' : '').'>'.$indent_str.$page->title().'</option>';
			}
		}
		return $parent_options;
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
			case 'delete':
			case 'move':
				if (!$id) {
					Console::log("                        URL for '".$action."' action requires an id");
				}
				return "/page-manager/".$action."/".$id;
				break;
			case 'index':
				return "/page-manager";
				break;
			default:
				return parent::url($action,$id);
				break;
		}
	}
	/**
	 * Enforce the presence of some data(notably ID) for certain actions. This function
	 * is called before the action by AbstractPluginController#run
	 *
	 * @return boolean
	 **/
	function before_filter() {
		if (in_array($this->params['action'], array('edit', 'delete', 'move'))) {
			// require ID
			return (!empty($this->params['id']));
		}
		return true;
	}
	/**
	 * Supply the name (or names) of the database table(s) used by this plugin
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function db_tablename() {
		return PageIndex::db_tablename();
	}
}
?>