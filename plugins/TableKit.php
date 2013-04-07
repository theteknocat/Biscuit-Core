<?php
class TableKit extends AbstractPluginController {
	var $dependencies = array("Prototype");
	function run() {
		if ($this->dependencies_met()) {	// If the web 2.0 plugin is loaded only
			$this->Biscuit->register_js('tablekit/tablekit.js');
			$this->Biscuit->register_css(array('filename' => 'tablekit.css', 'media' => 'screen,projection,print'));
		}
		else {
			Console::log("                        TableKit died because it can't live without Prototype");
		}
	}
}
?>