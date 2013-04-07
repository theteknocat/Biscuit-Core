<?php
/**
 * Provides core extension functionality to the Biscuit class
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 */
class ExtensionCore extends CacheControl {
	/**
	 * Install a extension when requested and user has sufficient privileges
	 *
	 * @param string $extension_name Camel-cased name of the extension
	 * @return void
	 * @author Peter Epp
	 */
	protected function install_global_extension($extension_name) {
		$this->install_or_uninstall_global_extension('install',$extension_name);
	}
	/**
	 * Un-install a extension when requested and user has sufficient privileges
	 *
	 * @param string $extension_name Camel-cased name of the extension
	 * @return void
	 * @author Peter Epp
	 */
	protected function uninstall_global_extension($extension_name) {
		$this->install_or_uninstall_global_extension('uninstall',$extension_name);
	}
	/**
	 * Run the actual install or uninstall operation
	 *
	 * @param string $mode 'install' or 'uninstall'
	 * @param string $extension_name Camel-cased name of the extension
	 * @return void
	 * @author Peter Epp
	 */
	protected function install_or_uninstall_global_extension($mode,$extension_name) {
		$this->ModuleAuthenticator()->define_access_levels();
		if ($this->ModuleAuthenticator()->user_is_super()) {
			$extension_id = DB::fetch_one("SELECT `id` FROM `extensions` WHERE `name` = ?",$extension_name);
			if (!empty($extension_id)) {
				$method = $mode.'_migration';
				if ($mode == 'install') {
					$installed = 1;
				} else {
					$installed = 0;
				}
				DB::query("UPDATE `extensions` SET `is_global` = ? WHERE `id` = ?",array($installed,$extension_id));
				if (Crumbs::public_method_exists($extension_name, $method)) {
					call_user_func(array($extension_name, $method));
				}
				Session::flash('user_message','Extension successfully '.$mode.'ed');
			} else {
				Session::flash('user_error','"'.$extension_name.'" Extension not found, installation aborted.');
			}
		} else {
			Console::log('Request to install extension "'.$extension_name.'" ignored, user does not have super access');
		}
		Response::redirect('/'.$this->user_input['page_slug']);
	}
	/**
	 * Instantiate and run any extensions that have a run method
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function init_global_extensions() {
		Console::log("    Initialize global extensions");
		$extension_names = DB::fetch("SELECT `name` FROM `extensions` WHERE `is_global` = 1 ORDER BY `sort_order`");
		if (DEBUG && SERVER_TYPE != "PRODUCTION" && (!defined('DISABLE_DEBUG_BAR') || !DISABLE_DEBUG_BAR)) {
			// Include DebugBar extension
			$extension_names[] = "DebugBar";
		}
		$all_extensions = array();
		if (!empty($extension_names)) {
			foreach ($extension_names as $name) {
				$all_extensions[] = $name;
			}
		}
		foreach ($all_extensions as $extension_name) {
			$this->init_extension($extension_name);
		}
	}
	/**
	 * Initialize a given extension
	 *
	 * @param string $extension_name 
	 * @return void
	 * @author Peter Epp
	 */
	public function init_extension($extension_name) {
		if (Crumbs::public_method_exists($extension_name,"run")) {
			$this->extensions[$extension_name] = new $extension_name();
			if (Crumbs::public_method_exists($this->extensions[$extension_name], "register_biscuit")) {
				$this->extensions[$extension_name]->register_biscuit($this);
			}
			if (Crumbs::public_method_exists($this->extensions[$extension_name], "check_dependencies")) {
				$this->extensions[$extension_name]->check_dependencies();
			}
			Console::log("        ".$extension_name);
			$this->extensions[$extension_name]->run();
		}
		else {
			$this->extensions[$extension_name] = $extension_name;	// Just store the name so we can check that it exists
		}
	}
	/**
	 * Where or or not an extension is installed.  Since some extensions might be instantiated while others may not be,
	 * this only checks to see if the class exists.
	 *
	 * TODO: What if a module or extension doesn't just require an extension class to exist but also to be instantiated?
	 *
	 * @param string $name Name of the extension (camelized)
	 * @return bool
	 * @author Peter Epp
	 */
	public function extension_exists($name) {
		return (!empty($this->extensions[$name]) && (is_object($this->extensions[$name]) || ($this->extensions[$name] == $name && class_exists($name))));
	}
}
?>