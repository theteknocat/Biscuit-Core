<?php
/**
 * A common Singleton interface
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: singleton.php 14196 2011-09-01 19:08:39Z teknocat $
 */
interface Singleton {
	/**
	 * Require an instance method that returns an instance of the object
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function instance();
}
?>