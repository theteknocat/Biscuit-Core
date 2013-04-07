<?php
/**
 * Register swfobject for inclusion
 *
 * @package Libraries
 * @subpackage Swfobject
 * @version $Id: library.php 14238 2011-09-12 21:55:55Z teknocat $
 */
class Swfobject extends LibraryLoader {
	protected static function register() {
		Biscuit::instance()->Theme->register_js('footer', 'libraries/swfobject/vendor/swfobject.js');
	}
}
