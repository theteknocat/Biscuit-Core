<?php
/**
 * Plugin for easy inclusion of prototype and scriptaculous JS, with the ability to turn it on and off in the database and assign it to specific pages if desired.
 *
 * @package Plugins
 * @author Peter Epp
 **/
class Prototype extends AbstractPluginController {
	function run() {
		$this->Biscuit->register_js('scriptaculous/lib/prototype.js');
		$this->Biscuit->register_js('scriptaculous/src/scriptaculous.js');
	}
}
?>