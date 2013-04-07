<?php
/**
 * Set of common helper functions
 *
 * @author Peter Epp
 * @version $Id$
 * @copyright Open Source
 * @package Core
 **/
class Crumbs {
	/**
		* The purpose of this static function is to gracefully handle include errors, if any, and provide friendly user feedback as well useful log information
		*
		* @return void
		* @author Peter Epp
		**/
	function include_response($include_return,$include_name,$include_error_response = "") {
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
	function clean_input($dirty_input) {
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
	function file_exists_in_load_path($file) {
		// break load_path into bits
		$bits = explode(':', ini_get("include_path"));
		// check each bit to see if the file exists
		while($next_bit = array_shift($bits)){
			$full_path = realpath($next_bit . '/'. $file);
			if (file_exists($full_path)) {
				// if so, return the full path to the file:
				return $full_path;
			}
		}
	}
	/**
	 * Look for a file first in the site root then in the framework root and return the appropriate path relative to the site root. This function is useful for getting file paths for things like image, JavaScript and CSS tags used in view files.
	 *
	 * @param string $file Path of file to look for. Do not prefix it with a "/" (eg. "scripts/myscript.js")
	 * @return string Path of the file found from the site root. If found in the framework root, "/framework" will be added to the beginning, otherwise the original filename will be returned as-is.
	 * @author Peter Epp
	 */
	function file_exists_in_site($file) {
		$file = "/".$file;
		if (file_exists(SITE_ROOT.$file)) {
			return $file;
		}
		elseif (file_exists(FW_ROOT.$file)) {
			return "/framework".$file;
		}
	}
	/**
	 * Recursively stripslashes.
	 * 
	 * From http://ca.php.net/manual/en/function.stripslashes.php#id3691252
	 *
	 * @param mixed $value something to stripslashes
	 * @return mixed
	 **/
	function stripslashes_deep($value) {
		// NOTE: Normally in PHP 5 this would be declared as a static funciton, and therefore "Crumbs::stripslashes_deep" could be passed to array_map as the
		// callback without PHP complaining about it.  However, for PHP 4 compatibility we cannot declare this as a static function, and PHP 5 complains (maybe 4 as well?)
		// if we try use "Crumbs::stripslashes_deep" as the callback.  For compatiblity with both PHP 4 and 5, we instantiate the Crumbs class locally so this
		// function can be passed to array_map the proper way.
		$tools = new Crumbs();	// We need to do this because $this doesn't exist when function is called statically
		$value = is_array($value) ?
			array_map(array($tools,__FUNCTION__), $value) :
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
	function capture_include($filename, $locals=array()) {
		// import vars into the current scope
		foreach ($locals as $key => $value) {
			if (!isset($$key)) { // only if it's not trampling an existing var
				$$key = $value; // TODO what about oddly named keys?
			} else {
				trigger_error("not setting $key", E_USER_NOTICE);
			}
		}
		ob_start();
		require($filename);
		return ob_get_clean();
	}
	/**
	 * Set the pointer in an array to a specified index
	 *
	 * @param string $array A reference to the target array
	 * @param string $key The target index of the array
	 * @return void
	 * @author Peter Epp
	 */
	function array_set_current(&$array, $key) {
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
	function date_format($datestring,$format) {
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
	function days_in_month($a_month, $a_year) {
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
	function strtotime($datestring) {
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
	function is_even($number) {
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
	function currency_format($val,$decimals = 2) {
		$negative = false;
		if ($val < 0) {
			$val -= ($val*2);
			$negative = true;
		}
		$val = number_format(round($val,2),$decimals);
		return (($negative) ? "(" : "")."\$".$val.(($negative) ? ")" : "");
	}
	/**
	 * Convert string with line breaks into paragraphs
	 *
	 * @param string $string 
	 * @return string
	 * @author Peter Epp
	 */
	function auto_paragraph($string) {
		// Start by normalizing the line breaks to ensure proper splitting:
		$string = preg_replace('/([\r\n]+)/',"\n",$string);
		$string_parts = explode("\n",$string);
		return '<p>'.implode('</p><p>',$string_parts).'</p>';
	}
	/**
	 * Encode a value in JSON
	 * 
	 * @param array $myarray the array you want converted to a json object - can be any kind of array, indexed or not, multi-dimensional - anything goes
	 * @return string JSON object string
	 * @author Peter Epp
	**/
	function to_json($myarray) {
		if (function_exists("json_encode")) {
			// For PHP 5
			return json_encode($myarray);
		}
		else {
			// For PHP 4
			$json = new Services_JSON();
			return $json->encode($myarray);
		}
	}
	/**
	 * Decode a JSON object
	 * 
	 * @param array $myarray the array you want converted to a json object - can be any kind of array, indexed or not, multi-dimensional - anything goes
	 * @return string JSON object string
	 * @author Peter Epp
	**/
	function from_json($myjson) {
		if (function_exists("json_decode")) {
			// For PHP 5
			return json_decode($myjson);
		}
		else {
			// For PHP 4
			$json = new Services_JSON();
			return $json->decode($myjson);
		}
	}
	/**
	 * Validate an email address
	 *
	 * @param string $email The email address to validate
	 * @return bool Whether or not the address is valid
	 * @author Peter Epp
	 */
	function valid_email($email) {
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
	function ensure_directory($dir_path) {
		// Create destination folders if they don't exist:
		if (!file_exists($dir_path)) {
			if(!@mkdir($dir_path, 0755)){
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
	function randstring() {
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
	function random_password($totalChar = 7) {
		$salt = "abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789!#%*_-";  // salt to select chars from
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
	function array_to_htmlstring($my_array) {
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
	function nl2br($string,$is_xhtml = false) {
		$new_string = nl2br($string);
		if (!$is_xhtml) {
			$new_string = preg_replace('/\<br \/\>/','<br>',$new_string);
		}
		return $new_string;
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
	function nl2li($string,$ordered = false, $type = "1",$css_class = "") {
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
	
	function xml_encode($string) {
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
	function html_entity_decode($string) {
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
	function set_timezone() {
		// Store the timezone offset in seconds before setting
		if (date_default_timezone_set(TIME_ZONE)) {
			Console::log("    Failed to set time zone!");
		}
		else {
			Console::log("    Set time zone to: ".TIME_ZONE);
		}
	}
	/**
	 * Return the size of a given file with units rounded off appropriately
	 *
	 * @param string $filename Filename relative to the site root with a leading slash
	 * @return string Formatted file size (eg. 843b, 6Kb, 10.7Mb, 12.24Gb)
	 * @author Peter Epp
	 */
	function formatted_file_size($filename) {
		$kb = 1024;
		$mb = 1024*1024;
		$gb = 1024*1024*1024;
		$fsize = filesize(SITE_ROOT.$filename);
		if ($fsize >= $gb) {		// GB range
			return round(($fsize/$gb),2)."Gb";
		}
		if ($fsize >= $mb) {
			return round(($fsize/$mb),1)."Mb";
		}
		if ($fsize >= $kb) {
			return round(($fsize/$kb))."Kb";
		}
		return $fsize."b";
	}
	/**
	* Map an array of objects calling a method on each member.
	*
		* @return array
		* @param array a list of objects
		* @param string the name of the method to invoke on each item
		* @author Lee O'Mara
		*/
	function map_method($array, $method_or_property) {
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
	function filter_method($array, $method_or_property){
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
	function method_or_property($item, $name) {
		if (is_callable(array($item, $name))) {
			return $item->$name();
		} elseif (is_array($item) && isset($item[$name])){
			return $item[$name];
			// The next statement has to go after this one because isset($item->$name) seems to validate both an object property or an array element.
			// But if it's an array element "return $item->$name" fails. Could this depend on PHP version?
		} elseif (isset($item->$name)) {
			return $item->$name;
		} else {
			trigger_error("'$name' is not a method or property of the array item ".print_r($item, 1),E_USER_ERROR);
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
	function method($method_or_attribute) {
	    return create_function('$object', "return Crumbs::method_or_property(\$object, '$method_or_attribute');");
	}

	/**
	 * Get a file listing for a given directory
	 *
	 * @param string $path 
	 * @param array $options This can include "types" as a single file extension or array of file extensions, "excludes" as a single filename to exclude or array of filenames, and "include_directories" as a boolean which is false by default.
	 * @return void
	 * @author Peter Epp
	 */
	function ls($path,$options = array()) {
		$full_path = SITE_ROOT.$path;
		if (!file_exists($full_path)) {
			return array();
		}
		// Remove trailing slash:
		if (substr($full_path,-1) == "/") {
			$full_path = substr($full_path,0,-1);
		}
		// Set options and defaults
		if (empty($options['types'])) {
			$types = array();
		}
		else {
			$types = (array)$options['types'];
		}
		$default_excludes = array(".","..","_notes",".svn");
		if (empty($options['excludes'])) {
			$excludes = $default_excludes;
		}
		else {
			$excludes = array_merge((array)$options['excludes'],$default_excludes);
		}
		if (!empty($options['match_pattern'])) {
			$match_pattern = "/".$options['match_pattern']."/";
		}
		else {
			$match_pattern = "/.*/";
		}
		$include_directories = false;
		if (!empty($options['include_directories'])) {
			$include_directories = $options['include_directories'];
		}
		// Get the listing
		clearstatcache();
		if ($handle = opendir($full_path)) {
			while (($file = readdir($handle)) !== false) {
				if (!in_array($file,$excludes) && preg_match($match_pattern,$file)) {
					$file_is_valid = false;
					$full_file_path = $full_path."/".$file;
					if (is_file($full_file_path)) {
						$ext_pos = strrpos($file,".");
						if ($ext_pos !== false) {
							$extension = substr($file,($ext_pos+1));
						}
						$file_is_valid = (empty($types) || (!empty($extension) && !empty($types) && in_array($extension,$types)));
					}
					elseif (is_dir($full_file_path)) {
						$file_is_valid = ($include_directories);
					}
					if ($file_is_valid) {
						$files[] = $file;
					}
				}
			}
			closedir($handle);
			if (is_array($files)) {
				@natcasesort($files);
				return @array_values($files);
			}
		}
		else {
			Console::log('WARNING: Cannot read directory "'.$full_path.'"');
		}
		return array();
	}
}
?>