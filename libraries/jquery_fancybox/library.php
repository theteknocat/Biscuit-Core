<?php
/**
 * Register the jQuery Fancybox library for inclusion
 *
 * @package Libraries
 * @subpackage JqueryFancybox
 * @version $Id: library.php 14194 2011-09-01 17:54:45Z teknocat $
 */
class JqueryFancybox extends LibraryLoader {
	/**
	 * Register the Fancybox CSS and JS files
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected static function register() {
		$args = func_get_args();
		Biscuit::instance()->Theme->register_css(array('filename' => 'libraries/jquery_fancybox/vendor/fancybox/jquery.fancybox-1.3.4.css', 'media' => 'screen'));
		Biscuit::instance()->Theme->register_js('footer', 'libraries/jquery_fancybox/vendor/fancybox/jquery.mousewheel-3.0.4.pack.js');
		Biscuit::instance()->Theme->register_js('footer', 'libraries/jquery_fancybox/vendor/fancybox/jquery.fancybox-1.3.4.pack.js');
		if (!empty($args) && !empty($args[0]) && $args[0] == 'auto-apply-to-images') {
			self::auto_apply_to_images();
		}
	}
	/**
	 * Register extra script file that auto-applies fancybox (with default settings) to any image links within containers with a class of "has-image-links"
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function auto_apply_to_images() {
		Biscuit::instance()->Theme->register_js('footer', 'libraries/jquery_fancybox/js/auto-apply-to-image-links.js');
	}
}
