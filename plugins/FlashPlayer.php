<?php
/**
 * Plugin for inclusion of the Dreamweaver Active Content and Flash version detection JS scripts. To use this plugin, you must call the render_constants() function prior to the framework's render_js_includes() function.
 *
 * @package default
 * @author Peter Epp
 **/
class FlashPlayer extends AbstractPluginController {
	function run() {
		$this->Biscuit->register_js('swfobject.js');
	}
	function render_component($component_name) {
		$Biscuit = &$this->Biscuit;
		$return = include "views/plugins/flashplayer/".$component_name.".php";
		Crumbs::include_response($return,'Flash component "'.$component_name.'"','Flash component "'.$component_name.'" could not be found!');
	}
	function render_component_script($component_name) {
		$Biscuit = &$this->Biscuit;
		$return = include "views/plugins/flashplayer/".$component_name.".js";
		Crumbs::include_response($return,'Flash component script "'.$component_name.'"','Flash component script "'.$component_name.'.js" could not be found!');
	}
}
?>