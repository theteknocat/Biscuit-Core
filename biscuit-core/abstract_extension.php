<?php
/**
 * An abstract class for providing base extension functionality
 *
 * @package Extensions
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: abstract_extension.php 14357 2011-10-28 22:23:04Z teknocat $
 */
class AbstractExtension extends EventObserver {
	/**
	 * A reference to the Biscuit object
	 *
	 * @var Biscuit
	 **/
	public $Biscuit;
	/**
	 * Whether or not dependencies have been checked
	 *
	 * @var bool
	 */
	protected $_dependencies_checked = false;
	/**
	 * List of modules and extensions this extension depends on
	 *
	 * @var array
	 */
	protected $_dependencies = array();
	/**
	 * Whether or not the plugin is primary
	 *
	 * @var bool
	 */
	protected $_is_primary;
	/**
	 * Add this object as an event observer
	 *
	 * @author Peter Epp
	 */
	public function __construct() {
		Event::add_observer($this);
	}
	/**
	 * Register a reference of the Biscuit in this plugin
	 *
	 * @abstract
	 * @param string $page The Biscuit object
	 * @return void
	 * @author Peter Epp
	 */
	public function register_biscuit($biscuit_object) {
		$this->Biscuit = $biscuit_object;
		$this->Theme   = $biscuit_object->Theme;
	}
	/**
	 * Register a JS file with Biscuit
	 *
	 * @param string $js_file Name of the file relative to the module's js folder
	 * @return void
	 * @author Peter Epp
	 */
	protected function register_js($position,$js_file,$stand_alone = false) {
		$my_folder = $this->base_path();
		$this->Theme->register_js($position,"{$my_folder}/js/".$js_file,$stand_alone);
	}
	/**
	 * Register a CSS file with Biscuit
	 *
	 * @param array $css_file Associative array of media type and filename relative to the module's css folder
	 * @return void
	 * @author Peter Epp
	 */
	protected function register_css($css_file, $for_ie = false, $ie_version = 'all') {
		$my_folder = $this->base_path();
		$css_file['filename'] = "{$my_folder}/css/".$css_file['filename'];
		if ($for_ie) {
			$this->Biscuit->register_ie_css($css_file, $ie_version);
		} else {
			$this->Biscuit->register_css($css_file);
		}
	}
	/**
	 * Check to see if a plugin's dependencies are met. To use this function, add a $dependencies property to your plugin with an array of dependent plugin names
	 *
	 * @abstract
	 * @return bool Whether or not dependencies are met
	 * @author Peter Epp
	 */
	public function check_dependencies() {
		if (empty($this->_dependencies)) {
			return true;
		}
		if (!$this->_dependencies_checked) {
			$deps_met = false;
			$missing_deps = array();
			$this->_dependencies_checked = true;
			$failed = 0;
			foreach ($this->_dependencies as $dep_name) {
				if (!$this->Biscuit->extension_exists($dep_name)) {
					try {
						$this->Biscuit->init_extension($dep_name);
					} catch (ExtensionException $e) {
						$failed += 1;
						$missing_deps[] = $dep_name;
					}
				}
			}
			if ($failed > 0) {
				trigger_error(get_class($this)." cannot run because it is missing the following dependencies:\n\n".implode(", ",$missing_deps));
			}
		}
	}
	/**
	 * Set a variable that will be made into a local variable for the view at render time
	 *
	 * @param string $key Variable name
	 * @param string $value Variable value
	 * @return mixed Variable value
	 * @author Peter Epp
	 * @author Lee O'Mara
	 */
	protected function set_view_var($key,$value) {
		return $this->Biscuit->set_view_var($key,$value);
	}
	/**
	 * Return the name of the folder for this extension based on the class name
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function base_path() {
		return AkInflector::underscore(get_class($this));
	}
}
?>