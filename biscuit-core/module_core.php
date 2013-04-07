<?php
/**
 * Provides core module functionality to the Biscuit class
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: module_core.php 14737 2012-11-30 22:56:56Z teknocat $
 */
class ModuleCore extends ExtensionCore {
	/**
	 * Whether or not the module has rendered
	 *
	 * @var bool
	 */
	private $_module_has_rendered = false;
	/**
	 * List of module view files to render
	 *
	 * @var string
	 */
	protected $_module_viewfiles = array();
	/**
	 * Module rewrite rules, stored when the module is first loaded
	 *
	 * @var array
	 */
	private $_module_rewrite_rules = array();
	/**
	 * Install a module when requested and user has sufficient privileges
	 *
	 * @param string $module_name Camel-cased name of the module
	 * @return void
	 * @author Peter Epp
	 */
	protected function install_module($module_name) {
		error_reporting(E_ALL);
		$this->install_or_uninstall_module('install',$module_name);
	}
	/**
	 * Un-install a module when requested and user has sufficient privileges
	 *
	 * @param string $module_name Camel-cased name of the module
	 * @return void
	 * @author Peter Epp
	 */
	protected function uninstall_module($module_name) {
		$this->install_or_uninstall_module('uninstall',$module_name);
	}
	/**
	 * Run the actual install or uninstall operation
	 *
	 * @param string $mode 'install' or 'uninstall'
	 * @param string $module_name Camel-cased name of the module
	 * @return void
	 * @author Peter Epp
	 */
	protected function install_or_uninstall_module($mode,$module_name) {
		$this->ModuleAuthenticator()->define_access_levels();
		if ($this->ModuleAuthenticator()->user_is_super()) {
			Console::log('Request to '.$mode.' "'.$module_name.'"');
			$module_id = DB::fetch_one("SELECT `id` FROM `modules` WHERE `name` = ?",$module_name);
			if (empty($module_id)) {
				// The module is not yet present in the modules table, lookup it's info file and add to the table
				$next_sort_order = (int)DB::fetch_one("SELECT `sort_order` FROM `modules` ORDER BY `sort_order` DESC LIMIT 1")+10;
				$info_file_path = 'modules/'.AkInflector::underscore($module_name).'/module.info';
				if ($full_info_file_path = Crumbs::file_exists_in_load_path($info_file_path)) {
					$module_info = parse_ini_file($full_info_file_path);
					if (!empty($module_info)) {
						$module_name = $module_info['name'];
						if (empty($module_info['description'])) {
							$module_description = "No description";
						} else {
							$module_description = $module_info['description'];
						}
						$module_id = DB::insert("INSERT INTO `modules` (`name`, `description`, `sort_order`) VALUES (?, ?, ?)", array($module_name, $module_description, $next_sort_order));
					}
				}
			}
			Console::log("Module ID: ".$module_id);
			if (!empty($module_id)) {
				$class_name = Crumbs::module_classname($module_name);
				$method = $mode.'_migration';
				if ($mode == 'install') {
					$installed = 1;
				} else {
					$installed = 0;
				}
				DB::query("UPDATE `modules` SET `installed` = ? WHERE `id` = ?",array($installed,$module_id));
				if ($mode == 'install') {
					// Install the as secondary on the "cron" page
					DB::query("INSERT INTO `module_pages` SET `module_id` = ?, `page_name` = 'cron', `is_primary` = 0", $module_id);
				} else {
					// Uninstall the module from the cron page:
					DB::query("DELETE FROM `module_pages` WHERE `module_id` = ? AND `page_name` = 'cron'", $module_id);
					// NB: it's the responsibility of the module itself to provide an uninstall method to remove itself from other pages
				}
				Session::flash('user_message','Module successfully '.$mode.'ed');
				if (Crumbs::public_method_exists($class_name, $method)) {
					call_user_func_array(array($class_name, $method),array($module_id));
				}
			} else {
				Session::flash('user_error','"'.$module_name.'" Module not found, '.$mode.' aborted.');
			}
		} else {
			Session::flash('user_error',"You do not have sufficient permission to install or uninstall modules.");
		}
		$uri = Crumbs::strip_query_var_from_uri(Request::uri(),$mode.'_module');
		$uri = Crumbs::add_query_var_to_uri($uri,'empty_caches',1);
		Response::redirect($uri);
	}
	/**
	 * Install a module on a given page or on all pages
	 *
	 * @param string $module_name Camel-cased name of the module
	 * @param string $page_slug * for all pages, otherwise the full page slug
	 * @param string $is_primary Whether or not to install as primary (1 or 0)
	 * @return void
	 * @author Peter Epp
	 */
	public function install_module_on_page($module_name, $page_slug, $is_primary) {
		// Get the module ID:
		$module_id = DB::fetch_one("SELECT `id` FROM `modules` WHERE `name` = ?",$module_name);
		if (empty($module_id)) {
			return false;
		}
		if ($is_primary) {
			// If requested module is being installed as primary, ensure that any other modules explicitly installed on the requested page as primary become secondary:
			DB::query("UPDATE `module_pages` SET `is_primary` = 0 WHERE `page_name` = ? AND `is_primary` = 1",array($page_slug));
		}
		// Drop any current installation of the requested module on the specified page (easier than checking/updating existing records):
		DB::query("DELETE FROM `module_pages` WHERE `module_id` = ? AND `page_name` = ?",array($module_id,$page_slug));
		// Now insert new record according to specified installation parameters:
		return DB::insert("INSERT INTO `module_pages` SET `module_id` = ?, `page_name` = ?, `is_primary` = ?",array($module_id,$page_slug,$is_primary));
	}
	/**
	 * Return the URI mapping rules, if any, from all installed modules
	 *
	 * @return array
	 * @author Peter Epp
	 */
	protected function get_module_uri_mapping_rules() {
		Console::log("    Get URI mapping rules for all installed modules");
		$module_mapping_rules = array();
		$module_names = DB::fetch("SELECT `name` FROM `modules` WHERE `installed` = 1 ORDER BY `sort_order`");
		if ($module_names) {
			foreach ($module_names as $name) {
				$class_name = Crumbs::module_classname($name);
				// Store the module's rewrite rules, if provided, so they can be provided when it comes time to parse the query:
				if (Crumbs::public_method_exists($class_name,'uri_mapping_rules')) {
					$curr_module_mapping_rules = call_user_func(array($class_name,'uri_mapping_rules'));
					if (is_array($curr_module_mapping_rules)) {	// In case of developer blonde moment
						$module_mapping_rules = array_merge($module_mapping_rules, $curr_module_mapping_rules);
					}
				}
			}
		}
		return $module_mapping_rules;
	}
	/**
	 * Load all common models for both the site and the framework, excluding ones from the framework that exist in the site
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function load_shared_models() {
		$base_path = "/modules/shared_models";
		$fw_excludes = array();
		$fw_models = array();
		if (file_exists(SITE_ROOT.$base_path)) {
			$fw_excludes = array();
			$site_models = FindFiles::ls($base_path, array("types" => "php"));
			if ($site_models) {
				$fw_excludes = $site_models;
			}
		}
		if (file_exists(FW_ROOT.$base_path)) {
			$fw_models = FindFiles::ls("/framework".$base_path, array("types" => "php", "excludes" => $fw_excludes));
		}
		if (!empty($fw_models)) {
			foreach ($fw_models as $model_file) {
				include_once FW_ROOT.$base_path."/".$model_file;
			}
		}
		if (!empty($site_models)) {
			foreach ($site_models as $model_file) {
				include_once SITE_ROOT.$base_path."/".$model_file;
			}
		}
	}
	/**
	 * Instantiate all modules associated with the current page
	 *
	 * @author Peter Epp
	 */
	protected function init_page_modules() {
		Console::log("    Initialize modules");
		$page_modules = $this->Page->find_modules();
		if (!empty($page_modules)) {
			foreach ($page_modules as $module) {
				$module_name = $module['name'];
				Console::log("        ".$module_name);
				$class_name = Crumbs::module_classname($module_name);
				if (!Crumbs::public_method_exists($class_name, "run")) {
					trigger_error($module_name." cannot be initialized because it has no run method!", E_USER_ERROR);
				}
				if (!empty($module['init_script'])) {
					$this->modules[$module_name] = eval($module['init_script']);
					if ($this->modules[$module_name] === NULL) {
						trigger_error($module_name." cannot be initialized because it's custom init script (".$module['init_script'].") returned NULL.");
					}
				}
				else {
					$this->modules[$module_name] = new $class_name();
					$this->modules[$module_name]->load_models();
				}
			}
			foreach ($page_modules as $module) {
				$module_name = $module['name'];
				if (Crumbs::public_method_exists($this->modules[$module_name], "register_biscuit")) {
					$this->modules[$module_name]->register_biscuit($this);
				}
				if (Crumbs::public_method_exists($this->modules[$module_name], "set_params")) {
					$this->modules[$module_name]->set_params(Request::user_input());
				}
				if (Crumbs::public_method_exists($this->modules[$module_name], "check_dependencies")) {
					$this->modules[$module_name]->check_dependencies();
				}
				if (Crumbs::public_method_exists($this->modules[$module_name], "is_primary")) {
					$is_primary = ((int)$module['is_primary'] == 1);
					$this->modules[$module_name]->is_primary($is_primary);
				}
			}
		}
		else {
			Console::log("        No modules found, moving on");
		}
	}
	/**
	 * Run all modules associated with the current page
	 *
	 * @author Peter Epp
	 */
	protected function run_page_modules() {
		if (!Request::user_input('action')) {
			$action = "index";
		}
		else {
			$action = Request::user_input('action');
		}
		$count = 0;	// For detailed logging

		// Always run authenticator if present:
		if ($this->module_exists('Authenticator')) {
			Console::log("    Running Authenticator module");
			$this->modules['Authenticator']->run();
			$count++;
		}
		// Only run the other modules if the cache is invalid and the request is good
		if (!$this->page_cache_is_valid() && !$this->request_is_bad()) {
			foreach ($this->modules as $module_name => $module_obj) {
				if ($module_name != "Authenticator") {
					Console::log("    Run:                ".$module_name);
					Console::log("                        Module is primary: ".(($this->modules[$module_name]->is_primary()) ? "Yes" : "No"));
					$count++;
					$this->modules[$module_name]->run();
				}
			}
		}
		if ($count == 0) {
			Console::log("    No modules need to run");
		}
	}
	/**
	 * Renders and returns the view file for the module appended to any existing page content
	 *
	 * @param string $page_content Current page content
	 * @param string $view_vars Array of local variables for the view
	 * @return string
	 * @author Peter Epp
	 */
	protected function render_module_views($page_content, $view_vars) {
		foreach ($this->_module_viewfiles as $module_name => $view_file) {
			Console::log("    Appending module view to content: ".$view_file);
			$page_content .= Crumbs::capture_include($view_file, $view_vars);
		}
		return $page_content;
	}
	/**
	 * Adds a module view file to the queue for rendering after the main view file
	 * 
	 * eg. from a module, say ProgramManager,
	 *     $page->render_module($this, 'list');
	 *     adds 'modules/program_manager/views/list.php' to the $page->_module_viewfiles array
	 *
	 * @param object $module Module object instance
	 * @param string $name the name of the view to show (defaults to 'index')
	 * @return void
	 **/
	public function render_module($module, $action = 'index') {
		if (!is_object($module)) {
			trigger_error("ModuleCore::render_module() Requires an object reference as the first argument.", E_USER_ERROR);
		}
		$module_path = $module->base_path();
		$module_name = get_class($module);
		if (!$module->is_primary() || !$this->primary_module_rendered()) {
			$locale = I18n::instance()->locale();

			// First look for a view file specific to the current locale ([module_path]/views/[locale]/action_name.php)
			$module_viewfile = implode('/',array($module_path, 'views', $locale, $action.'.php'));

			if (!Crumbs::file_exists_in_load_path($module_viewfile)) {
				// If no locale-specific view exists, look for just the action-specific view:
				$module_viewfile = implode('/', array($module_path, 'views', $action.'.php'));

				if (!Crumbs::file_exists_in_load_path($module_viewfile)) {
					// If that one doesn't exist, look for a locale-specific generic view:
					$module_viewfile = 'modules/generic_views/'.$locale.'/'.$action.'.php';

					if (!Crumbs::file_exists_in_load_path($module_viewfile)) {
						// If no locale-specific generic view, look for just the action-specific generic view:
						$module_viewfile = 'modules/generic_views/'.$action.'.php';

						if (!Crumbs::file_exists_in_load_path($module_viewfile)) {
							// If that doesn't exist, check special case for delete view:

							if ($module->base_action_name($action) == 'delete') {
								// Special case for delete action, since we know for a fact that there is a generic delete view file in the framework that can
								// apply to any model and can therefore always use it as a fallback regardless of the model-specific delete action
								$module_viewfile = 'modules/generic_views/delete.php';
							} else {
								// If not the delete special case, throw an exception. This will trigger an error 404 for the user
								throw new ViewNotFoundException();
							}
						}
					}
				}
			}
			$this->_module_viewfiles[$module_name] = $module_viewfile;
			if ($module->is_primary()) {
				$this->primary_module_rendered(true);
			}
			Console::log("    Added module to render queue: ".$module_name);
		}
		else {
			Console::log("    A primary module was already added to the render queue before ".$module_name);
		}
	}
	/**
	 * Return a reference to the primary module, if there is one, or false if none.
	 *
	 * @return object|bool
	 * @author Peter Epp
	 */
	public function primary_module() {
		foreach ($this->modules as $module_name => $module) {
			if ($module->is_primary()) {
				return $module;
			}
		}
		return false;
	}
	/**
	 * Select a template provided by the primary module for the current action, if present
	 *
	 * @return string|null
	 * @author Peter Epp
	 */
	public function select_primary_module_template() {
		if ($primary_module = $this->primary_module()) {
			// A template specific to the exact current action:
			$module_template1 = $primary_module->action().'.php';
			if ($primary_module->action() != $primary_module->base_action_name($primary_module->action())) {
				// A template specific to the base action if not the same as the actual action:
				$module_template2 = $primary_module->base_action_name($primary_module->action()).'.php';
			}
			// A default template for all of the module's actions:
			$module_template3 = 'default.php';

			if (Request::is_ajax()) {
				$path_prefix = $primary_module->base_path().'/templates/ajax-';
			} else {
				$path_prefix = $primary_module->base_path().'/templates/';
			}

			if ($module_template_path = Crumbs::file_exists_in_load_path($path_prefix . $module_template1,SITE_ROOT_RELATIVE)) {
				// Use action-specific template if found:
				$module_template_path = ltrim($module_template_path,'/');
				return $module_template_path;
			} else if (!empty($module_template2) && $module_template_path = Crumbs::file_exists_in_load_path($path_prefix . $module_template2,SITE_ROOT_RELATIVE)) {
				// else use base-action-specific template if found:
				$module_template_path = ltrim($module_template_path,'/');
				return $module_template_path;
			} else if ($module_template_path = Crumbs::file_exists_in_load_path($path_prefix . $module_template3,SITE_ROOT_RELATIVE)) {
				// else use default template for all the module's actions if found:
				$module_template_path = ltrim($module_template_path,'/');
				return $module_template_path;
			}
		}
		return null;
	}
	/**
	 * Setter and getter for whether or not a module has called render.
	 *
	 * @param bool $set_value Optional. Leave empty to get, or provide it to set.
	 * @return void
	 * @author Peter Epp
	 */
	public function primary_module_rendered($set_value = null) {
		if (is_bool($set_value)) {
			$this->_module_has_rendered = $set_value;
		}
		return $this->_module_has_rendered;
	}
	/**
	 * Where or or not a module is installed on the current page
	 *
	 * @param string $name Name of the module (camelized)
	 * @return bool
	 * @author Peter Epp
	 */
	public function module_exists($name) {
		return (!empty($this->modules[$name]) && is_object($this->modules[$name]));
	}
	/**
	 * Check to see if a static module is installed/available.
	 *
	 * @param string $class_name The name of the object class to look for
	 * @param string $installed_check_function Optional - If the static module has a function for checking if it's properly installed, put its name in here
	 * @return bool
	 * @author Peter Epp
	 */
	public function static_module_exists($class_name,$installed_check_function = null) {
		if (class_exists($class_name)) {
			if (is_string($installed_check_function) && !empty($installed_check_function)) {
				return call_user_func(array($class_name,$installed_check_function));
			}
			return true;
		}
		Console::log("Class not found!");
		return false;
	}
	/**
	 * Return a list of the names of all the models used by modules on the current page
	 *
	 * @return array
	 * @author Peter Epp
	 */
	protected function all_model_names() {
		$model_names = array();
		foreach ($this->modules as $module_name => $module) {
			if (Crumbs::public_method_exists($module, 'models_affecting_content')) {
				$model_names = array_merge($model_names, $module->models_affecting_content());
			} else {
				$model_names = array_merge($model_names, $module->all_model_names());
			}
		}
		// List of models to never check:
		$skip_models = array(
			'Menu', 'AccessLevel', 'AccountStatus', 'Permission', 'UserEmailVerification', 'PasswordResetToken'
		);
		return array_diff($model_names, $skip_models);
	}
}
