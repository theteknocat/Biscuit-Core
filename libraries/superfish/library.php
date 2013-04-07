<?php
/**
 * Register superfish JS for inclusion
 *
 * @package Libraries
 * @subpackage Superfish
 * @version $Id: library.php 14246 2011-09-13 15:21:35Z teknocat $
 */
class Superfish extends LibraryLoader {
	protected static function register() {
		Biscuit::instance()->Theme->register_js('footer', 'libraries/superfish/vendor/hoverIntent.js');
		Biscuit::instance()->Theme->register_js('footer', 'libraries/superfish/vendor/jquery.bgiframe.min.js');
		Biscuit::instance()->Theme->register_js('footer', 'libraries/superfish/vendor/superfish.js');
	}
}
