<?php
/**
 * Plugin for easy inclusion of Lightview JS and CSS files, with the ability to turn it on and off in the database and assign it to specific pages if desired.
 *
 * @package Plugins
 * @author Peter Epp
 **/
class sIFR extends AbstractPluginController {
	function run() {
		$this->Biscuit->register_js('sifr.js');
		$this->Biscuit->register_js('sifr-init.js');
		$this->Biscuit->register_css(array('filename' => 'sIFR/sIFR-screen.css', 'media' => 'screen'));
		$this->Biscuit->register_css(array('filename' => 'sIFR/sIFR-print.css', 'media' => 'print'));
	}
}
?>
