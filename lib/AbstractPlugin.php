<?php
/**
 * An abstract plugin class that handles plugin-specific permission checks and plugin database installation and updates
 *
 * @package Plugins
 * @author Peter Epp
 */
class AbstractPlugin extends AbstractObserver {
	/**
	 * A shortcut to checking user show permission on the current object
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	function user_can_view() {
		return $this->user_can("show");
	}
	/**
	 * A shortcut to checking user edit permission on the current object
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	function user_can_edit() {
		return $this->user_can("edit");
	}
	/**
	 * A shortcut to checking user delete permission on the current object
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	function user_can_delete() {
		return $this->user_can("delete");
	}
	/**
	 * A shortcut to checking user new permission on the current object
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	function user_can_create() {
		return $this->user_can("new");
	}
	/**
	 * Ask Authenticator if the user has permission to perform a requested action
	 *
	 * @param string $action The name of the action
	 * @return bool
	 * @author Peter Epp
	 */
	function user_can($action) {
		return Permissions::user_can($this,$action);
	}
	/**
	 * Return the upload path for the current plugin. Redefine this function in your plugin if you do not want to use the default path of "/uploads/plugin_name"
	 *
	 * @abstract
	 * @return void
	 * @author Peter Epp
	 */
	function upload_path() {
		return "/uploads/".strtolower(get_class($this));
	}
	/**
	 * Check to see if the database for this plugin is installed, and if not call the install_plugin_db function if the plugin can provide the table name.
	 *
	 * @abstract
	 * @return void
	 * @author Peter Epp
	 */
	function check_install() {
		if (method_exists($this,'db_tablename')) {
			$my_tables = $this->db_tablename();
			if (!empty($my_tables)) {
				if (is_array($my_tables)) {
					for ($i=0;$i < count($my_tables);$i++) {
						$this->build_table($my_tables[$i]);
					}
				}
				else if (is_string($my_tables)) {
					$this->build_table($my_tables);
				}
			}
		}
	}
	/**
	 * Calls create_table, populate_table and/or alter_table as needed to build a plugin's database table.
	 *
	 * @param string $table_name Name of the table to construct
	 * @return void
	 * @author Peter Epp
	 */
	function build_table($table_name) {
		// Create and populate:
		if (DB::table_exists($table_name) === false) {
			Console::log("                        Database table '".$table_name."' for ".get_class($this)." not found, creating it now...");
			$this->create_table($table_name);
			// Populate the new table if required by the plugin
			$this->populate_table($table_name);
		}
	}
	/**
	 * Create a plugin's table. This function requires that the plugin has a "db_reate_query()" function that returns the appropriate SQL for creating the table
	 *
	 * This function will automatically be called by the check_install() method. If you redefine the run() function in your plugin and it does not call parent::run(),
	 * you will need to call check_install() from your plugin's run function.
	 *
	 * @abstract
	 * @param string $table_name The name of the table to be created
	 * @return void
	 * @author Peter Epp
	 */
	function create_table($table_name) {
		if (method_exists($this,'db_create_query')) {
			$create_query = $this->db_create_query($table_name);
			if (!empty($create_query)) {
				DB::query($create_query);
			}
		}
	}
	/**
	 * Populate a plugin's table. This function requires that the plugin has a "db_populate_query()" function that returns the appropriate SQL for populating the table
	 *
	 * This function will automatically be called by the check_install() method. If you redefine the run() function in your plugin and it does not call parent::run(),
	 * you will need to call check_install() from your plugin's run function.
	 *
	 * @abstract
	 * @param string $table_name The name of the table to be created
	 * @return void
	 * @author Peter Epp
	 */
	function populate_table($table_name) {
		if (method_exists($this,'db_populate_query')) {
			$populate_query = $this->db_populate_query($table_name);
			if (!empty($populate_query)) {
				DB::query($populate_query);
			}
		}
	}
}
?>