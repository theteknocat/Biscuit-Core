<?php
/**
 * Provides core extension functionality to the Biscuit class
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: extension_core.php 14801 2013-03-27 20:14:53Z teknocat $
 */
class ExtensionCore extends CacheControl {
	/**
	 * Install an extension when requested and user has sufficient privileges
	 *
	 * @param string $extension_name Camel-cased name of the extension
	 * @return void
	 * @author Peter Epp
	 */
	protected function install_extension($extension_name) {
		error_reporting(E_ALL);
		$this->install_or_uninstall_extension('install',$extension_name);
	}
	/**
	 * Un-install an extension when requested and user has sufficient privileges
	 *
	 * @param string $extension_name Camel-cased name of the extension
	 * @return void
	 * @author Peter Epp
	 */
	protected function uninstall_extension($extension_name) {
		$this->install_or_uninstall_extension('uninstall',$extension_name);
	}
	/**
	 * Run the actual install or uninstall operation
	 *
	 * @param string $mode 'install' or 'uninstall'
	 * @param string $extension_name Camel-cased name of the extension
	 * @return void
	 * @author Peter Epp
	 */
	protected function install_or_uninstall_extension($mode,$extension_name) {
		$this->ModuleAuthenticator()->define_access_levels();
		if ($this->ModuleAuthenticator()->user_is_super()) {
			$extension_id = DB::fetch_one("SELECT `id` FROM `extensions` WHERE `name` = ?",$extension_name);
			if (empty($extension_id)) {
				// The extension is not yet present in the extensions table, lookup it's info file and add to the table
				$info_file_path = 'extensions/'.AkInflector::underscore($extension_name).'/extension.info';
				if ($full_info_file_path = Crumbs::file_exists_in_load_path($info_file_path)) {
					$extension_info = parse_ini_file($full_info_file_path);
					if (!empty($extension_info)) {
						if (isset($extension_info['is_global'])) {
							$extension_name = $extension_info['name'];
							if (empty($extension_info['description'])) {
								$extension_description = "No description";
							} else {
								$extension_description = $extension_info['description'];
							}
							$is_global = (int)$extension_info['is_global'];
							$extension_id = DB::insert("INSERT INTO `extensions` (`name`, `description`, `is_global`) VALUES (?, ?, ?)", array($extension_name, $extension_description, $is_global));
						} else {
							Session::flash('user_error','"'.$extension_name.'" extension could not be installed as it\'s info file is missing the is_global variable. Please report this to the extension developer.');
						}
					}
				}
			}
			if (!empty($extension_id)) {
				$method = $mode.'_migration';
				if ($mode == 'uninstall') {
					DB::query("DELETE FROM `extensions` WHERE `id` = ?",$extension_id);
				}
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
		$uri = Crumbs::strip_query_var_from_uri(Request::uri(),$mode.'_extension');
		$uri = Crumbs::add_query_var_to_uri($uri,'empty_caches',1);
		Response::redirect($uri);
	}
	/**
	 * Instantiate and run any extensions that have a run method
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function init_global_extensions() {
		Console::log("    Initialize global extensions");
		$extension_names = DB::fetch("SELECT `name` FROM `extensions` WHERE `is_global` = 1");
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