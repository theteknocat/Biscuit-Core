<?php
/**
 * Plugin for easy inclusion of prototype and scriptaculous JS, with the ability to turn it on and off in the database and assign it to specific pages if desired.
 *
 * @package Plugins
 * @author Peter Epp
 **/
class jQuery extends AbstractPluginController {
	function run() {
		$this->Biscuit->register_js('jquery.js');
		if ($this->Biscuit->plugin_exists("Prototype")) {
			$this->Biscuit->register_js('jquery-noconflict.js');
		}
	}
}
?>
