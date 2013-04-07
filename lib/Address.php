<?php
/**
 * A class for dealing with common address (as in mailing or street address) information and formatting
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 */
class Address {
	private function __construct() {
		// Prevent instantiation
	}
	/**
	 * Return an associative array of all Canadian provinces with the 2-letter abbreviations as the keys and full province names as the values
	 *
	 * @return array
	 * @author Peter Epp
	 */
	public static function provinces() {
		return array(
			'AB' => "Alberta",
			'BC' => "British Columbia",
			'MB' => "Manitoba",
			'NB' => "New Brunswick",
			'NL' => "Newfoundland and Labrador",
			'NT' => "Northwest Territories",
			'NS' => "Nova Scotia",
			'NU' => "Nunavut",
			'ON' => "Ontario",
			'PE' => "Prince Edward Island",
			'QC' => "Quebec",
			'SK' => "Saskatchewan",
			'YT' => "Yukon"
		);
	}
	/**
	 * Return the full province name by 2-letter abbreviation
	 *
	 * @param string $prov_code 2-letter province abbreviation (eg. "AB")
	 * @return string The full province name
	 * @author Peter Epp
	 */
	public static function province($prov_code) {
		$provinces = Address::provinces();
		return $provinces[$prov_code];
	}
}
?>