<?php
/**
 * Plugin for easy inclusion of Lightview JS and CSS files, with the ability to turn it on and off in the database and assign it to specific pages if desired.
 *
 * @package Plugins
 * @author Peter Epp
 **/
class LightView extends AbstractPluginController {
	var $dependencies = array("Prototype");
	function run() {
		if ($this->dependencies_met()) {	// If the web 2.0 plugin is loaded only
			$this->Biscuit->register_js('lightview.js');
			$this->Biscuit->register_css(array('filename' => 'lightview.css', 'media' => 'screen'));
		}
		else {
			Console::log("                        LightView died because it can't live without Prototype");
		}
	}
}
?>