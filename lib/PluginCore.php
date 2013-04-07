<?php
/**
 * Provides core plugin functionality to the Biscuit class
 *
 * @package Core
 * @author Peter Epp
 */
class PluginCore {

	var $plugin_has_rendered = false;
	/**
	 * Include installed plugins
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function load_plugins() {
		Console::log("    Load plugins");
		$plugin_data = BiscuitModel::find_installed_plugins();
		if ($plugin_data !== false) {
			for ($i=0;$i < count($plugin_data);$i++) {
				$return = include_once "plugins/".$plugin_data[$i]['script_file'];
				// Check the status and act accordingly:
				if (!$return) {
					Console::log("        Plugin not loaded, file not found: ".$plugin_data[$i]['script_file']);
				}
			}
		}
	}
	/**
	 * Instantiate all plugins associated with the current page
	 *
	 * @author Peter Epp
	 */
	function init_page_plugins() {
		$page_plugins = BiscuitModel::find_page_plugins($this->full_page_name);
		if (!empty($page_plugins)) {
			foreach ($page_plugins as $plugin) {
				$plugin_name = substr($plugin['script_file'],0,-4);
				Console::log("        ".$plugin_name);
				if (!empty($plugin['init_script'])) {
					$this->plugins[$plugin_name] = eval($plugin['init_script']);
					if ($this->plugins[$plugin_name] === NULL) {
						Console::log("                        Initialization script did not return! (".$plugin['init_script'].")");
					}
				}
				else {
					$this->plugins[$plugin_name] = new $plugin_name();
				}
				if (method_exists($this->plugins[$plugin_name],"register_page")) {
					$this->plugins[$plugin_name]->register_page(&$this);
				}
				if (method_exists($this->plugins[$plugin_name],"init_listeners")) {
					$this->plugins[$plugin_name]->init_listeners();
				}
				if (method_exists($this->plugins[$plugin_name],"is_primary")) {
					$is_primary = ((int)$plugin['is_primary'] == 1);
					// Set whether or not the plugin is primary
					$this->plugins[$plugin_name]->is_primary($is_primary);
				}
			}
		}
		else {
			Console::log("        No plugins found, moving on");
		}
	}
	/**
	 * Run all plugins associated with the current page
	 *
	 * @author Peter Epp
	 */
	function run_page_plugins() {
		Console::log("Execute plugins:");
		$count = 0;	// For detailed logging
		foreach ($this->plugins as $plugin_name => $plugin_obj) {
			if (method_exists($this->plugins[$plugin_name],"run")) {
				Console::log("    Executing:          ".$plugin_name);
				$count++;
				$this->plugins[$plugin_name]->run($this->user_input);
			}
		}
		if ($count == 0) {
			Console::log("    No plugins need to run");
		}
	}
	/**
	 * Sets the view file based on a plugin and calls the page render function
	 * 
	 * eg. from a plugin, say ProgramManager,
	 *     $page->render_plugin($this, 'list');
	 *     sets $page->viewfile to 'plugins/programmanager/list.php'
	 *
	 * Note that due to PHP4 behaviour, ClassNames loose their case.
	 * 
	 * @param object|string $plugin the plugin name
	 * @param string $name the name of the 'page' to show (defaults to 'index')
	 * @return void
	 **/
	function render_plugin(&$plugin, $name = 'index',$render_now = false) {
		if (is_object($plugin)) {
			$plugin_name = strtolower(get_class($plugin));
		} elseif (is_string($plugin)) {
			$plugin_name = $plugin;
		}
		Console::log("Render plugin: ".$plugin_name);
		if (!$this->plugin_rendered()) {	// To prevent conflicts with other plugins that do not render until the main render call
			$this->viewfile = implode('/', array('views', 'plugins', $plugin_name, $name.'.php'));
			$this->plugin_rendered(true);
			if ($render_now) {
				Console::log("    Rendering immediately");
				$this->render();
			}
			else {
				Console::log("    Queued to render after any remaining plugins have run");
			}
		}
		else {
			Console::log("    Another plugin already rendered, moving on...");
		}
	}
	/**
	 * Setter and getter for whether or not a plugin has called render.
	 *
	 * @param bool $set_value Optional. Leave empty to get, or provide it to set.
	 * @return void
	 * @author Peter Epp
	 */
	function plugin_rendered($set_value = null) {
		if (is_bool($set_value)) {
			$this->plugin_has_rendered = $set_value;
		}
		return $this->plugin_has_rendered;
	}
	/**
	 * Where or or not a plugin is installed on the current page
	 *
	 * @param string $name Name of the plugin (camelized)
	 * @return bool
	 * @author Peter Epp
	 */
	function plugin_exists($name) {
		return (!empty($this->plugins[$name]) && is_object($this->plugins[$name]));
	}
	/**
	 * Check to see if a static plugin is installed/available.
	 *
	 * @param string $class_name The name of the object class to look for
	 * @param string $installed_check_function Optional - If the static plugin has a function for checking if it's properly installed, put its name in here
	 * @return bool
	 * @author Peter Epp
	 */
	function static_plugin_exists($class_name,$installed_check_function = null) {
		if (class_exists($class_name)) {
			if (is_string($installed_check_function) && !empty($installed_check_function)) {
				return call_user_func(array($class_name,$installed_check_function));
			}
			return true;
		}
		Console::log("Class not found!");
		return false;
	}
}
?>