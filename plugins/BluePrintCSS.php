<?php
/**
 * Plugin for easy inclusion of Blueprint CSS, with the ability to turn it on and off in the database and assign it to specific pages if desired.
 * Note that the render_ie_css() function must be called after the framework's render_css_includes() function for Internet Explorer compatibility
 * @package Plugins
 * @author Peter Epp
 **/
class BluePrintCSS extends AbstractPluginController {
	var $dependencies = array('BrowserDetection');
	function run() {
		if ($this->dependencies_met()) {
			$this->Biscuit->register_css(array('filename' => 'blueprint/screen.css','media' => 'screen, projection'));
			$this->Biscuit->register_css(array('filename' => 'blueprint/print.css','media' => 'print'));
			if ($this->Biscuit->plugins['BrowserDetection']->info['name'] == "Internet Explorer") {
				// Include IE-specific CSS files
				$this->Biscuit->register_css(array('filename' => 'blueprint/lib/ie.css','media' => 'screen, projection'));
			}
		}
		else {
			Console::log("                        BluePrintCSS died because it can't live without BrowserDetection");
		}
	}
}
?>