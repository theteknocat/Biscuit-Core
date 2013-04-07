<?php
/**
 * A generic model for cases where no customization is needed
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 1.0 $Id: generic_model.php 14744 2012-12-01 20:50:43Z teknocat $
 */
class GenericModel extends AbstractModel {
	protected static $_model_name;
	/**
	 * Set the name of the model
	 *
	 * @param string $model_name 
	 * @author Peter Epp
	 */
	public function __construct($model_name) {
		self::$_model_name = $model_name;
		parent::__construct();
	}
	/**
	 * Return the table name based on the imposed model name
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public static function db_tablename() {
		return AkInflector::tableize(self::$_model_name);
	}
}
