<?php
/**
 * Model the system_settings table
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: system_settings.php 13959 2011-08-08 16:25:15Z teknocat $
 */
class SystemSettings extends AbstractModel {
	public static function db_tablename() {
		return 'system_settings';
	}
}
?>