<?php
/**
 * A class for dealing with common address (as in mailing or street address) information and formatting
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: address.php 14431 2011-12-04 04:07:10Z teknocat $
 */
class Address {
	private function __construct() {
		// Prevent instantiation
	}
	/**
	 * Return an associative array of all Canadian provinces with the abbreviations as the keys and full province names as the values
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
	 * Return an associative array of all US states with the abbreviations as the keys and full state name as the value
	 *
	 * @return array
	 * @author Peter Epp
	 */
	public static function us_states() {
		return array(
			'AL' => "Alabama",
			'AK' => "Alaska",
			'AZ' => "Arizona",
			'AR' => "Arkansas",
			'CA' => "California",
			'CO' => "Colorado",
			'CT' => "Connecticut",
			'DE' => "Delaware",
			'DC' => "District Of Columbia",
			'FL' => "Florida",
			'GA' => "Georgia",
			'HI' => "Hawaii",
			'ID' => "Idaho",
			'IL' => "Illinois",
			'IN' => "Indiana",
			'IA' => "Iowa",
			'KS' => "Kansas",
			'KY' => "Kentucky",
			'LA' => "Louisiana",
			'ME' => "Maine",
			'MD' => "Maryland",
			'MA' => "Massachusetts",
			'MI' => "Michigan",
			'MN' => "Minnesota",
			'MS' => "Mississippi",
			'MO' => "Missouri",
			'MT' => "Montana",
			'NE' => "Nebraska",
			'NV' => "Nevada",
			'NH' => "New Hampshire",
			'NJ' => "New Jersey",
			'NM' => "New Mexico",
			'NY' => "New York",
			'NC' => "North Carolina",
			'ND' => "North Dakota",
			'OH' => "Ohio",
			'OK' => "Oklahoma",
			'OR' => "Oregon",
			'PA' => "Pennsylvania",
			'RI' => "Rhode Island",
			'SC' => "South Carolina",
			'SD' => "South Dakota",
			'TN' => "Tennessee",
			'TX' => "Texas",
			'UT' => "Utah",
			'VT' => "Vermont",
			'VA' => "Virginia",
			'WA' => "Washington",
			'WV' => "West Virginia",
			'WI' => "Wisconsin",
			'WY' => "Wyoming"
		);
	}
	/**
	 * Return an associative array of all Australian states with the abbreviations as the keys and full state name as the value
	 *
	 * @return array
	 * @author Peter Epp
	 */
	public static function australian_states() {
		return array(
			"NSW" => "New South Wales",
			"VIC" => "Victoria",
			"QLD" => "Queensland",
			"TAS" => "Tasmania",
			"SA"  => "South Australia",
			"WA"  => "Western Australia",
			"NT"  => "Northern Territory",
			"ACT" => "Australian Capital Terrirory"
		);
	}
	/**
	 * Return all provinces and states in an array formatted for a select list of options, grouped by country
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function province_state_select_options($codes_as_values = false, $with_other = true) {
		$options = array();
		if ($with_other) {
			$options[] = array(
				'value' => 'other',
				'label' => __('Other (specify below)')
			);
		}
		$canadian_provinces = self::provinces();
		foreach ($canadian_provinces as $abbr => $name) {
			if ($codes_as_values) {
				$value = $abbr;
			} else {
				$value = $name;
			}
			$options[] = array(
				'group_label' => 'Canada',
				'value'       => $value,
				'label'       => $name
			);
		}
		$us_states = self::us_states();
		foreach ($us_states as $abbr => $name) {
			if ($codes_as_values) {
				$value = $abbr;
			} else {
				$value = $name;
			}
			$options[] = array(
				'group_label' => 'United States',
				'value'       => $value,
				'label'       => $name
			);
		}
		$aus_states = self::australian_states();
		foreach ($aus_states as $abbr => $name) {
			if ($codes_as_values) {
				$value = $abbr;
			} else {
				$value = $name;
			}
			$options[] = array(
				'group_label' => 'Australia',
				'value'       => $value,
				'label'       => $name
			);
		}
		return $options;
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
		if (array_key_exists($prov_code, $provinces)) {
			return $provinces[$prov_code];
		}
		return null;
	}
	/**
	 * Return the full state name by 2-letter abbreviation
	 *
	 * @param string $prov_code 2-letter province abbreviation (eg. "AB")
	 * @return string The full province name
	 * @author Peter Epp
	 */
	public static function us_state($state_code) {
		$states = Address::us_states();
		if (array_key_exists($state_code, $states)) {
			return $states[$state_code];
		}
		return null;
	}
	/**
	 * Return the full state name by 2-letter abbreviation
	 *
	 * @param string $prov_code 2-letter province abbreviation (eg. "AB")
	 * @return string The full province name
	 * @author Peter Epp
	 */
	public static function australian_state($state_code) {
		$states = Address::australian_states();
		if (array_key_exists($state_code, $states)) {
			return $states[$state_code];
		}
		return null;
	}
	/**
	 * Return the full province name by 2-letter abbreviation
	 *
	 * @param string $prov_code 2-letter province abbreviation (eg. "AB")
	 * @return string The full province name
	 * @author Peter Epp
	 */
	public static function province_code($prov_name) {
		$provinces = Address::provinces();
		foreach ($provinces as $code => $name) {
			if ($name == $prov_name) {
				return $code;
			}
		}
		return null;
	}
	/**
	 * Return the full state name by 2-letter abbreviation
	 *
	 * @param string $prov_code 2-letter province abbreviation (eg. "AB")
	 * @return string The full province name
	 * @author Peter Epp
	 */
	public static function us_state_code($state_name) {
		$states = Address::us_states();
		foreach ($states as $code => $name) {
			if ($name == $state_name) {
				return $code;
			}
		}
		return null;
	}
	/**
	 * Return the full state name by 2-letter abbreviation
	 *
	 * @param string $prov_code 2-letter province abbreviation (eg. "AB")
	 * @return string The full province name
	 * @author Peter Epp
	 */
	public static function australian_state_code($state_code) {
		$states = Address::australian_states();
		foreach ($states as $code => $name) {
			if ($name == $state_name) {
				return $code;
			}
		}
		return null;
	}
	/**
	 * Return an array suitable for the select form helper of all the country names
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function country_select_options() {
		$options = array();
		$countries = self::countries();
		foreach ($countries as $name) {
			$options[] = array(
				'value'       => $name,
				'label'       => $name
			);
		}
		return $options;
	}
	/**
	 * Return array of every country in the world
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function countries() {
		return array(
			"Afghanistan",
			"Albania",
			"Algeria",
			"Andorra",
			"Angola",
			"Antigua & Deps",
			"Argentina",
			"Armenia",
			"Australia",
			"Austria",
			"Azerbaijan",
			"Bahamas",
			"Bahrain",
			"Bangladesh",
			"Barbados",
			"Belarus",
			"Belgium",
			"Belize",
			"Benin",
			"Bhutan",
			"Bolivia",
			"Bosnia Herzegovina",
			"Botswana",
			"Brazil",
			"Brunei",
			"Bulgaria",
			"Burkina",
			"Burundi",
			"Cambodia",
			"Cameroon",
			"Canada",
			"Cape Verde",
			"Central African Rep",
			"Chad",
			"Chile",
			"China",
			"Colombia",
			"Comoros",
			"Congo",
			"Congo {Democratic Rep}",
			"Costa Rica",
			"Croatia",
			"Cuba",
			"Cyprus",
			"Czech Republic",
			"Denmark",
			"Djibouti",
			"Dominica",
			"Dominican Republic",
			"East Timor",
			"Ecuador",
			"Egypt",
			"El Salvador",
			"Equatorial Guinea",
			"Eritrea",
			"Estonia",
			"Ethiopia",
			"Fiji",
			"Finland",
			"France",
			"Gabon",
			"Gambia",
			"Georgia",
			"Germany",
			"Ghana",
			"Greece",
			"Grenada",
			"Guatemala",
			"Guinea",
			"Guinea-Bissau",
			"Guyana",
			"Haiti",
			"Honduras",
			"Hungary",
			"Iceland",
			"India",
			"Indonesia",
			"Iran",
			"Iraq",
			"Ireland {Republic}",
			"Israel",
			"Italy",
			"Ivory Coast",
			"Jamaica",
			"Japan",
			"Jordan",
			"Kazakhstan",
			"Kenya",
			"Kiribati",
			"Korea North",
			"Korea South",
			"Kosovo",
			"Kuwait",
			"Kyrgyzstan",
			"Laos",
			"Latvia",
			"Lebanon",
			"Lesotho",
			"Liberia",
			"Libya",
			"Liechtenstein",
			"Lithuania",
			"Luxembourg",
			"Macedonia",
			"Madagascar",
			"Malawi",
			"Malaysia",
			"Maldives",
			"Mali",
			"Malta",
			"Marshall Islands",
			"Mauritania",
			"Mauritius",
			"Mexico",
			"Micronesia",
			"Moldova",
			"Monaco",
			"Mongolia",
			"Montenegro",
			"Morocco",
			"Mozambique",
			"Myanmar, {Burma}",
			"Namibia",
			"Nauru",
			"Nepal",
			"Netherlands",
			"New Zealand",
			"Nicaragua",
			"Niger",
			"Nigeria",
			"Norway",
			"Oman",
			"Pakistan",
			"Palau",
			"Panama",
			"Papua New Guinea",
			"Paraguay",
			"Peru",
			"Philippines",
			"Poland",
			"Portugal",
			"Qatar",
			"Romania",
			"Russian Federation",
			"Rwanda",
			"St Kitts & Nevis",
			"St Lucia",
			"Saint Vincent & the Grenadines",
			"Samoa",
			"San Marino",
			"Sao Tome & Principe",
			"Saudi Arabia",
			"Senegal",
			"Serbia",
			"Seychelles",
			"Sierra Leone",
			"Singapore",
			"Slovakia",
			"Slovenia",
			"Solomon Islands",
			"Somalia",
			"South Africa",
			"Spain",
			"Sri Lanka",
			"Sudan",
			"Suriname",
			"Swaziland",
			"Sweden",
			"Switzerland",
			"Syria",
			"Taiwan",
			"Tajikistan",
			"Tanzania",
			"Thailand",
			"Togo",
			"Tonga",
			"Trinidad & Tobago",
			"Tunisia",
			"Turkey",
			"Turkmenistan",
			"Tuvalu",
			"Uganda",
			"Ukraine",
			"United Arab Emirates",
			"United Kingdom",
			"United States",
			"Uruguay",
			"Uzbekistan",
			"Vanuatu",
			"Vatican City",
			"Venezuela",
			"Vietnam",
			"Yemen",
			"Zambia",
			"Zimbabwe"
		);
	}
}
