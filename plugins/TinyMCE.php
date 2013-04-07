<?php
/**
 * Plugin for easy inclusion of Tiny MCE HTML editor
 *
 * @package Plugins
 * @author Peter Epp
 */
class TinyMCE extends AbstractPluginController {
	function run() {
		$this->Biscuit->register_js('tiny_mce/jscripts/tiny_mce/tiny_mce.js');
		$this->Biscuit->register_js('tiny_mce_init.js');
	}
}
?>