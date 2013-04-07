<?php
/**
 * A constant to use as the second optional argument when calling Crumbs::file_exists_in_load_path when you want a site-root relative result
 */
define("SITE_ROOT_RELATIVE",true);
/**
 * Set of common helper functions
 *
 * @author Peter Epp
 * @package Core
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 **/
class Crumbs {
	private function __construct() {
		// Prevent instantiation
	}
	/**
		* The purpose of this static function is to gracefully handle include errors, if any, and provide friendly user feedback as well useful log information
		*
		* @return void
		* @author Peter Epp
		**/
	public static function include_response($include_return,$include_name,$include_error_response = "") {
		if ($include_return == 1) {
			Console::log("    ".$include_name." included successfully");
		}
		if ($include_return != 1) {
			Console::log("    ".$include_name." include failed, file not found!");
			if (!empty($include_error_response)) {
				echo $include_error_response;
			}
		}
	}
	/**
	 * Clean user input with stripslashes conditional to setting of magic quotes
	 *
	 * @param string $dirty_input
	 * @return void
	 * @author Peter Epp
	 */
	public static function clean_input($dirty_input) {
		if (get_magic_quotes_gpc()) {
			// User input with slashes stripped:
			return Crumbs::stripslashes_deep($dirty_input);
		}
		return $dirty_input;
	}
	/**
		* Like file_exists, but takes include_path into account.
		*
		* @param string $file file name(can include bits of path)
		* @return string the found file path or false if none found
		* @author Lee O'Mara
		**/
	public static function file_exists_in_load_path($file, $root_relative = false) {
		// break load_path into bits
		$bits = explode(':', ini_get("include_path"));
		// check each bit to see if the file exists
		while($next_bit = array_shift($bits)){
			$full_path = realpath($next_bit . '/'. $file);
			if (!empty($full_path)) {
				// if so, return the path to the file:
				if ($root_relative) {
					// Relative to the site root if requested
					$full_path = substr($full_path,strlen(SITE_ROOT));
				}
				return $full_path;
			}
		}
		return false;
	}
	/**
	 * Recursively stripslashes.
	 * 
	 * From http://ca.php.net/manual/en/function.stripslashes.php#id3691252
	 *
	 * @param mixed $value something to stripslashes
	 * @return mixed
	 **/
	public static function stripslashes_deep($value) {
		$value = is_array($value) ?
			array_map(array('Crumbs','stripslashes_deep'), $value) :
		stripslashes($value);
		return $value;
	}

	/**
	 * Capture the content of an include.
	 * 
	 * Using output buffering, capture the results of including a file and return that
	 * string.
	 *
	 * @param string $filename The file to include
	 * @param array $locals variables you want to have access to in the included file.
	 * @return string
	 * @author Lee O'Mara
	 **/
	public static function capture_include($filename, $locals=array()) {
		// import vars into the current scope
		foreach ($locals as $local_var_key => $local_var_value) {
			if (!isset($$local_var_key)) { // only if it's not trampling an existing var
				$$local_var_key = $local_var_value; // TODO what about oddly named keys?  Keys with invalid characters?  $locals should be a trusted source, but just in case.
			} else {
				trigger_error("not setting $local_var_key", E_USER_NOTICE);
			}
		}
		unset($locals);
		if ($full_file_path = Crumbs::file_exists_in_load_path($filename)) {
			ob_start();
			require($full_file_path);
			return ob_get_clean();
		}
		else {
			throw new CoreException("Include file not found: ".$filename);
		}
	}
	/**
	 * Set the pointer in an array to a specified index
	 *
	 * @param string $array A reference to the target array
	 * @param string $key The target index of the array
	 * @return void
	 * @author Peter Epp
	 */
	public static function array_set_current(&$array, $key) {
		reset($array);
		while(current($array)){ 
			if(key($array) == $key){
				break;
			}
			next($array);
		}
	}
	/**
	 * Reformat a date from a date string into a new date string
	 *
	 * @param string $format The format of the date as per the PHP date() function
	 * @param string $datestring The string date to reformat
	 * @return void
	 * @author Peter Epp
	 */
	public static function date_format($datestring,$format) {
		$date = new Date($datestring);
		return $date->format($format);
	}
	/**
	 * Find the number of days in a given month and year
	 *
	 * @param string $a_month Numeric month
	 * @param string $a_year Year
	 * @return string Formatted date
	 * @author Peter Epp
	 */
	public static function days_in_month($a_month, $a_year) {
		$my_date = $a_year."-".$a_month."-01";
		$date = new Date($my_date);
		return $date->format('t');
	}
	/**
	 * Wrapper for the date class that returns a timestamp for a given date in string format
	 *
	 * @param string $datestring Date in a standard string format.  Note that ordinals (eg. th, st) are not supported
	 * @return void
	 * @author Peter Epp
	 */
	public static function strtotime($datestring) {
		$date = new Date($datestring);
		return $date->getTimestamp();
	}
	/**
	 * Is a number even?  If not it must be odd.
	 *
	 * @param string $number The number to test
	 * @return void
	 * @author Peter Epp
	 */
	public static function is_even($number) {
		return ($number%2 == 0);
	}
	/**
	 * Format any float value as currency (dollars)
	 *
	 * TODO Add multiple-currency support
	 *
	 * @param string $val Floating point value
	 * @param string $decimals Optional - number of decimal places
	 * @return void
	 * @author Peter Epp
	 */
	public static function currency_format($val,$decimals = 2) {
		$negative = false;
		if (round($val,$decimals) < 0) {
			$val -= ($val*2);
			$negative = true;
		}
		$val = number_format(round($val,$decimals),$decimals);
		return (($negative) ? "(" : "")."\$".$val.(($negative) ? ")" : "");
	}
	/**
	 * Encode a value in JSON
	 * 
	 * @param array $myarray the array you want converted to a json object - can be any kind of array, indexed or not, multi-dimensional - anything goes
	 * @return string JSON object string
	 * @author Peter Epp
	**/
	public static function to_json($myarray) {
		return json_encode($myarray);
	}
	/**
	 * Decode a JSON object
	 * 
	 * @param array $myarray the array you want converted to a json object - can be any kind of array, indexed or not, multi-dimensional - anything goes
	 * @return string JSON object string
	 * @author Peter Epp
	**/
	public static function from_json($myjson) {
		return json_decode($myjson);
	}
	/**
	 * Validate an email address
	 *
	 * @param string $email The email address to validate
	 * @return bool Whether or not the address is valid
	 * @author Peter Epp
	 */
	public static function valid_email($email) {
		// First, we check that there's one @ symbol, and that the lengths are right
		if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
			// Email invalid because wrong number of characters in one section, or wrong number of @ symbols.
			return false;
		}
		// Split it into sections to make life easier
		$email_array = explode("@", $email);
		$local_array = explode(".", $email_array[0]);
		for ($i = 0; $i < sizeof($local_array); $i++) {
			if (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i])) {
				return false;
			}
		}
		if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) { // Check if domain is IP. If not, it should be valid domain name
			$domain_array = explode(".", $email_array[1]);
			if (sizeof($domain_array) < 2) {
				return false; // Not enough parts to domain
			}
			for ($i = 0; $i < sizeof($domain_array); $i++) {
				if (!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$", $domain_array[$i])) {
					return false;
				}
			}
		}
		return true;
	}
	/**
	 * Ensure that a directory exists and is writable
	 *
	 * @param string $dir_path 
	 * @return void
	 * @author Peter Epp
	 */
	public static function ensure_directory($dir_path) {
		// Create destination folders if they don't exist:
		if (!file_exists($dir_path)) {
			if(!@mkdir($dir_path, 0755, true)){
				return false;
			};
		}
		if (!is_writable($dir_path)) {
			return false;
		}
		return true;
	}
	/**
	 * Generate a random alpha-numeric string between 6 and 8 characters long
	 *
	 * @return string Your random alpha-numeric string, if you please
	 * @author Peter Epp
	 */
	public static function randstring() {
		$chars = array("a","A","b","B","c","C","d","D","e","E","f","F","g","G","h","H","i","I","j","J","k","K","l","L","m","M","n","N","o","O","p","P","q","Q","r","R","s","S","t","T","u","U","v","V","x","X","y","Y","z","Z","0","1","2","3","4","5","6","7","8","9","0");
		$length = rand(6,8);
		$rstring = "";
		for ($i=0;$i < $length;$i++) {
			$rstring .= $chars[rand(0,count($chars)-1)];
		}
		return $rstring;
	}
	/**
	 * Generate a random password of a specified length (default 7 characters) using numbers, upper and lowercase letters, and common symbols
	 *
	 * @param string $totalChar Optional - password length
	 * @return string Your random password, if you please
	 * @author Peter Epp
	 */
	public static function random_password($totalChar = 7) {
		$salt = "abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789!%#*_-";  // salt to select chars from
		srand((double)microtime()*1000000); // start the random generator
		$password=""; // set the inital variable
		for ($i=0;$i<$totalChar;$i++) { // loop and create password
			$password = $password . substr ($salt, rand() % strlen($salt), 1);
		}
		return $password;
	}
	/**
	* Recursively dump the contents of an array into an HTML formatted string with break tags between the contents of each array element
	*
	* @return string HTML formatted array contents
	* @author Peter Epp
	* @param $my_array array The array that you want to have converted in to an HTML string
	**/
	public static function array_to_htmlstring($my_array) {
		$output = '';
		$i = 0;
		foreach($my_array as $k => $v) {
			if ($i > 0) {
				$output .= '<br>';
			}
			if (is_array($v)) {
				$output .= Crumbs::array_to_htmlstring($v);
			}
			else {
				$output .= $v;
			}
			$i++;
		}
	}
	/**
	 * Run nl2br with optional parameter for XHTML line breaks (<br />).  The built-in PHP nl2br function is documented as having a second parameter for determining
	 * whether or not XHTML is used, but it does not seem to work (at least in PHP 5). This function is a workaround.
	 *
	 * @param string $string The string in which to insert line breaks
	 * @param bool $is_xhtml Optional, defaults to false. Determines whether or not to use XHTML line breaks (<br />)
	 * @return void
	 * @author Peter Epp
	 */
	public static function nl2br($string,$is_xhtml = false) {
		$new_string = nl2br($string);
		if (!$is_xhtml) {
			$new_string = preg_replace('/\<br \/\>/','<br>',$new_string);
		}
		return $new_string;
	}
	/**
	 * Convert string with line breaks into paragraphs
	 *
	 * @param string $string 
	 * @return string
	 * @author Peter Epp
	 */
	public static function auto_paragraph($string) {
		// Start by normalizing the line breaks to ensure proper splitting:
		$string = preg_replace('/([\r\n]+)/',"\n",$string);
		$string_parts = explode("\n",$string);
		return '<p>'.implode('</p><p>',$string_parts).'</p>';
	}
	/**
	 * Convert line breaks to list items
	 *
	 * @param string $string The string to convert.
	 * @param bool $ordered Whether or not to use an ordered list.
	 * @param string $type "type" attribute value. For ordered lists only.
	 * @param string $css_class CSS class name to apply to each list item
	 * @author Peter Epp
	 */
	public static function nl2li($string,$ordered = false, $type = "1",$css_class = "") {
		$string = trim($string);
		if (!empty($string)) {
			if ($ordered)  {
				$tag="ol";
				$tag_type="type=$type";
			}
			else {    
				$tag="ul";
				$tag_type=NULL;
			}
			$string = "<$tag $tag_type><li class=\"$css_class\">" . $string ."</li></$tag>"; 
			$string = str_replace("\n","</li>\n<li class=\"$css_class\">",$string);
		}
		return $string;
	}
	/**
	 * Encodes any given string, that could include HTML, for output into a dynamic XML document.
	 *
	 * @param string $string The string 
	 * @return void
	 * @author Peter Epp
	 */
	
	public static function xml_encode($string) {
		$string = preg_replace("'([\r\n]+)'"," ",$string);
		return htmlentities($string,ENT_QUOTES,"UTF-8");
	}
	/**
	 * Replacement for html_entity_decode available in PHP 4.3.0 and up for compatibility
	 *
	 * @param string $string 
	 * @return void
	 * @author Peter Epp
	 */
	public static function html_entity_decode($string) {
		// replace numeric entities
		$string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
		$string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $string);
		// replace literal entities
		$trans_tbl = get_html_translation_table(HTML_ENTITIES);
		$trans_tbl = array_flip($trans_tbl);
		return strtr($string, $trans_tbl);
	}
	/**
	 * Set the time zone
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function set_timezone() {
		date_default_timezone_set(TIME_ZONE);
		Console::log("    Set time zone to: ".TIME_ZONE);
	}
	/**
	 * Return the size of a given file with units rounded off appropriately
	 *
	 * @param string $filename Filename relative to the site root with a leading slash
	 * @return string Formatted file size (eg. 843b, 6Kb, 10.7Mb, 12.24Gb)
	 * @author Peter Epp
	 */
	public static function formatted_file_size($filename) {
		$fsize = filesize(SITE_ROOT.$filename);
		return self::formatted_filesize_from_bytes($fsize);
	}
	/**
	 * Formated bytes into Kb, Mb or Gb
	 *
	 * @param string $fsize 
	 * @return string
	 * @author Peter Epp
	 */
	public static function formatted_filesize_from_bytes($fsize) {
		$kb = 1024;
		$mb = 1024*1024;
		$gb = 1024*1024*1024;
		if ($fsize >= $gb) {		// GB range
			return round(($fsize/$gb),2)."Gb";
		}
		if ($fsize >= $mb) {
			return round(($fsize/$mb),1)."Mb";
		}
		if ($fsize >= $kb) {
			return round(($fsize/$kb))."Kb";
		}
		return $fsize." bytes";
	}
	/**
	 * Suffix a numeric value with an ordinal
	 *
	 * @param string $value 
	 * @param string $superscript 
	 * @return string
	 * @author Peter Epp
	 */
	function ordinal_suffix($value, $superscript = false){
		if (!is_numeric($value)) {
			return '';
		}
		if (substr($value, -2, 2) == 11 || substr($value, -2, 2) == 12 || substr($value, -2, 2) == 13) {
			$suffix = "th";
		} else if (substr($value, -1, 1) == 1) {
			$suffix = "st";
		} else if (substr($value, -1, 1) == 2) {
			$suffix = "nd";
		} else if (substr($value, -1, 1) == 3) {
			$suffix = "rd";
		} else {
			$suffix = "th";
		}
		if ($superscript) {
			$suffix = "<sup>" . $suffix . "</sup>";
		}
		return $value . $suffix;
	}
	/**
	* Map an array of objects calling a method on each member.
	*
		* @return array
		* @param array a list of objects
		* @param string the name of the method to invoke on each item
		* @author Lee O'Mara
		*/
	public static function map_method($array, $method_or_property) {
		return array_map(Crumbs::method($method_or_property),$array);
	}
	/**
		* Filter an array of objects based on the results of a method call/property
		* of each member
		* 
		* @param array $array a list of objects/arrays
		* @param string $method the name of the method/property
		* @return array list of objects/arrays that passed the filter
		*/
	public static function filter_method($array, $method_or_property){
		return array_filter($array,Crumbs::method($method_or_property));
	}
	/**
		* Return the value of a method call or property access. Prefer method over
		* property
		*
		* @return mixed
		* @param Object $item
		* @param string $name the name of the method or property
		* @author Lee O'Mara
		*/
	public static function method_or_property($item, $name) {
		if (is_object($item) && Crumbs::public_method_exists($item, $name)) {
			return $item->$name();
		} elseif (is_array($item) && isset($item[$name])){
			return $item[$name];
			// The next statement has to go after this one because isset($item->$name) seems to validate both an object property or an array element.
			// But if it's an array element "return $item->$name" fails. Could this depend on PHP version?
		} elseif (isset($item->$name)) {
			return $item->$name;
		} else {
			trigger_error("'$name' is not a method or property of the array item ".print_r($item, 1));
			return false;
		}
	}
	/**
	 * A method to pass as the callback to functions like array_map and array_filter along with an array of objects, where you want
	 * to call on a method or property of each object in the array.
	 *
	 * @param string $method_or_attribute 
	 * @return void
	 * @author Lee O'Mara
	 */
	public static function method($method_or_attribute) {
	    return create_function('$object', "return Crumbs::method_or_property(\$object, '$method_or_attribute');");
	}

	/**
	 * Check to see if a method exists and is callable on an object instance.  This is a workaround to a quirk in PHP5 where "is_callable" still returns
	 * true even if the method cannot be publicly called.
	 *
	 * I found this workaround here: http://ca3.php.net/manual/en/function.method-exists.php#65405
	 *
	 * @param object $class Instance of the object you are checking
	 * @param string $method_name Name of the method you are looking for
	 * @return bool
	 * @author Peter Epp
	 */
	public static function public_method_exists($class,$method_name) {
		$is_object = is_object($class);
		if (!$is_object) {
			$class_exists = class_exists($class);
		}
		else {
			$class_exists = true;
		}
		if (!$class_exists) {
			$msg = ((!$class_exists) ? "Non-existent class '".$class."'" : "Non-object")." passed to Crumbs::public_method_exists()";
			trigger_error($msg, E_USER_ERROR);
		}
		$methods = get_class_methods($class);
		return in_array($method_name,$methods);
	}
	/**
	 * Determine the actual class name for a module, which could be either the same as the module name or the module name plus "Manager"
	 *
	 * @param string $module_name Name of the module whose class name you want to obtain
	 * @return string
	 * @author Peter Epp
	 */
	public static function module_classname($module_name) {
		$classname1 = $module_name."Manager";
		$classname2 = $module_name;
		if (class_exists('Custom'.$classname1)) {
			return 'Custom'.$classname1;
		} else if (class_exists('Custom'.$classname2)) {
			return 'Custom'.$classname2;
		} else if (class_exists($classname1)) {
			return $classname1;
		}
		else if (class_exists($classname2)) {
			return $classname2;
		}
		return false;
	}
	/**
	 * Return normalized module name based on class name (strip "Manager" and "Custom" from class name if present)
	 *
	 * @param object $module 
	 * @return string
	 * @author Peter Epp
	 */
	public static function normalized_module_name($module) {
		$module_name = get_class($module);
		if (substr($module_name,-7) == 'Manager') {
			$module_name = substr($module_name,0,-7);
		}
		if (substr($module_name,0,6) == 'Custom') {
			$module_name = substr($module_name,6);
		}
		return $module_name;
	}
	/**
	 * Return the full path to a file requested for download, if the file is valid and publicly accessible.
	 *
	 * @param string $path Path to the file to download
	 * @param string $filename Name of the file to download
	 * @return mixed Full file path if file exists and is publicly accessible, otherwise false
	 * @author Peter Epp
	 */
	public static function full_download_path($path,$filename) {
		if (empty($path) || empty($filename)) {
			return false;
		}
		if (substr($path,0,1) != "/") {
			$path = "/".$path;
		}
		$full_file_path = SITE_ROOT.$path."/".$filename;
		$path_bits = explode("/",$path);
		if (!file_exists($full_file_path) || $path_bits[0] != "uploads") {
			return false;
		}
		return $full_file_path;
	}
	/**
	 * Explode a CSV string
	 *
	 * @param string $str The input string
	 * @param string $delim The delimiter
	 * @param string $qual The enclosing character
	 */
	public static function csv_explode($str, $delim = ',', $qual = '"', $esc = '\\') {
		$len = strlen($str);
		$inside = false;
		$word = '';
		for ($i = 0; $i < $len; ++$i) {
			if ($str[$i]==$delim && !$inside) {
				$out[] = $word;
				$word = '';
			} else if ($inside && $str[$i]==$qual && ($i > 0 && $str[$i-1] == $esc)) {
				$word .= $qual;
			} else if ($str[$i] == $qual) {
				$inside = !$inside;
			} else {
				$word .= $str[$i];
			}
		}
		$out[] = $word;
		return $out;
	}
	/**
	 * Produce a copyright notice to display in the page
	 *
	 * @param string $prefix Optional - something to start the notice with
	 * @param string $suffix Optional - something to end the notice with
	 * @return string Copyright notice
	 * @author Peter Epp
	 */
	public static function copyright_notice($copyright_owner = SITE_OWNER, $prefix = '',$suffix = '') {
		$cnotice = '';
		if (!empty($prefix)) {
			$cnotice .= $prefix." ";
		}
		$cnotice .= "Copyright &copy;".LAUNCH_YEAR.((date("Y") != LAUNCH_YEAR) ? ' - '.date("Y") : '').' '.$copyright_owner;
		if (!empty($suffix)) {
			$cnotice .= " ".$suffix;
		}
		return $cnotice;
	}
	/**
	 * Return info about a file attribute - size, date, download URL and filename
	 *
	 * @param string $attribute_name Name of the attribute whose file information you want to retrieve
	 * @return array Or boolean - array of data if file exists, false if file does not exist
	 * @author Peter Epp
	**/
	public static function file_info($file_path) {
		$full_file_path = SITE_ROOT.$file_path;
		if (file_exists($full_file_path)) {
			return new SPLFileInfo($full_file_path);
		}
		return false;
	}
	/**
	 * Render a small bar positioned at the bottom of the page on the local development and staging servers to let the user know which server they
	 * are on and if debug mode is enabled.  Also provides a link to empty caches on the local development machine.
	 *
	 * @return string HTML code, blank on production server
	 * @author Peter Epp
	 */
	public static function server_info_bar() {
		if (SERVER_TYPE == 'LOCAL_DEV' || SERVER_TYPE == 'TESTING') {
			$info_bar_start = <<<HTML
<style type="text/css" media="screen">
	body {
		margin-bottom: 30px;
	}
</style>
<div style="position: fixed; left: 0; bottom: 0; width: 100%; font-size: 10px; background: #eee; border-top: 1px solid #333;color: #333;z-index: 90000">
	<div style="padding: 5px; height: 18px"><div style="float: left; height: 18px">
HTML;
			if (SERVER_TYPE == 'LOCAL_DEV') {
				$server_type = 'DEV';
				$info_bar_content = 'This site is running on the local development machine.';
			} else if (SERVER_TYPE == 'TESTING') {
				$server_type = 'STAGE';
				$info_bar_content = 'This site is a preview running on the staging server.';
			}
			if (DEBUG) {
				$info_bar_content = '<div style="float: left; padding: 0 5px; background: #94f088; border: 1px solid #3a8230; margin: 0 5px 0 0; color: #3a8230">DEBUG</div>'.$info_bar_content;
			}
			$empty_caches_link = '';
			if (SERVER_TYPE == 'LOCAL_DEV') {
				$empty_caches_url = Request::uri();
				if (preg_match('/\?/',$empty_caches_url)) {
					$empty_caches_url .= '&empty_caches=1';
				} else {
					$empty_caches_url .= '?empty_caches=1';
				}
				$empty_caches_link = '<a style="float: right; display: block; width: 100px; text-align: right" href="'.$empty_caches_url.'">Empty Caches</a>';
			}
			$info_bar_content = '<div style="float: left; padding: 0 5px; background: #94f088; border: 1px solid #3a8230; margin: 0 5px 0 0; color: #3a8230">'.$server_type.'</div>'.$info_bar_content;
			$info_bar = $info_bar_start.$info_bar_content.<<<HTML
		</div>$empty_caches_link</div>
</div>
HTML;
			return $info_bar;
		}
		return '';
	}
}
?>