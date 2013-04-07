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
	 * Names of all the installed extensions
	 *
	 * @author Peter Epp
	 */
	private static $_extensions = array();
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
				DB::query("UPDATE `extensions` SET `is_global` = 1 WHERE `id` = ?",$extension_id);
				Session::flash('user_message','Extension successfully '.$mode.'ed');
			} else {
				Session::flash('user_error','"'.$extension_name.'" Extension not found, installation aborted.');
			}
		} else {
			Console::log('Request to install extension "'.$extension_name.'" ignored, user does not have super access');
		}
		Response::redirect('/'.$this->user_input['page']);
	}
	/**
	 * Load all installed extensions
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function load_global_extensions() {
		Console::log("    Load global extensions");
		$extension_names = DB::fetch("SELECT `name` FROM `extensions` WHERE `is_global` = 1 ORDER BY `sort_order`");
		if (DEBUG && SERVER_TYPE != "PRODUCTION" && (!defined('DISABLE_DEBUG_BAR') || !DISABLE_DEBUG_BAR)) {
			// Include DebugBar extension
			$extension_names[] = "DebugBar";
		}
		if ($extension_names) {
			foreach ($extension_names as $name) {
				try {
					$this->load_extension($name);
					Console::log("        ".$name);
				} catch (ExtensionException $e) {
					trigger_error($e->getMessage(), E_USER_ERROR);
				}
			}
		}
	}
	/**
	 * Load the extension file
	 *
	 * @param string $extension_folder 
	 * @return void
	 * @author Peter Epp
	 */
	protected function load_extension($extension_name) {
		$extension_folder = AkInflector::underscore($extension_name);
		$extension_path = $extension_folder."/extension.php";
		if ($file = Crumbs::file_exists_in_load_path($extension_path)) {
			self::$_extensions[] = $extension_name;
			include_once $file;
		}
		else {
			throw new ExtensionException("Extension could not load, file not found: ".$extension_path);
		}
	}
	/**
	 * Instantiate and run any extensions that have a run method
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function init_global_extensions() {
		Console::log("    Initialize global extensions");
		$extensions = self::$_extensions;
		foreach ($extensions as $extension_name) {
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
		if (!in_array($extension_name,self::$_extensions)) {
			$this->load_extension($extension_name);
		}
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
	/**
	 * Return the date of the most recently updated extension. Checks all view files used by the extension, returning the most recent
	 *
	 * @return mixed
	 * @author Peter Epp
	 */
	protected function latest_extension_update() {
		$timestamps = array();
		foreach ($this->extensions as $extension_name => &$extension) {
			if (Crumbs::public_method_exists($extension, 'base_path')) {
				$base_path = call_user_func(array($extension,'base_path'));
				$extension_path = $base_path.'/extension.php';
				if ($full_ext_path = Crumbs::file_exists_in_load_path($extension_path)) {
					$timestamps[] = filemtime($full_ext_path);
				}
				if ($folder_path = Crumbs::file_exists_in_load_path($base_path.'/views', SITE_ROOT_RELATIVE)) {
					$file_list = FindFiles::ls($folder_path);
					if (!empty($file_list)) {
						foreach ($file_list as $file_name) {
							$full_file_path = SITE_ROOT.$folder_path.'/'.$file_name;
							$timestamps[] = filemtime($full_file_path);
						}
					}
				}
			}
		}
		if (empty($timestamps)) {
			return false;
		}
		rsort($timestamps);
		return reset($timestamps);
	}
}
?>