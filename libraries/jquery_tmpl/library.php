<?php
/**
 * Register the jQuery template library for inclusion
 *
 * @package Libraries
 * @subpackage JqueryTmpl
 * @version $Id: library.php 14179 2011-08-31 19:59:03Z teknocat $
 */
class JqueryTmpl extends LibraryLoader {
	protected static function register() {
		Biscuit::instance()->Theme->register_js('footer', 'libraries/jquery_tmpl/vendor/jquery-tmpl/jquery.tmpl.min.js');
	}
}
