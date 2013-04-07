<?php
/**
 * Register knockout js for inclusion
 *
 * @package Libraries
 * @subpackage KnockoutJs
 * @version $Id: library.php 14180 2011-08-31 19:59:11Z teknocat $
 */
class KnockoutJs extends LibraryLoader {
	protected static function register() {
		Biscuit::instance()->Theme->register_js('footer', 'libraries/knockout_js/vendor/knockout.js');
	}
}
