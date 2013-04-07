<?php
// Include appropriate version of HTMLPurifier
if (version_compare(PHP_VERSION,"5.0.5",">=")) {
	Console::log("        HTML Purifier: PHP 5.0.5 and up is supported");
	define("PURIFIER_VERSION","PHP5");
	include('plugins/html_purifier/php5/library/HTMLPurifier.auto.php');
}
else if (version_compare(PHP_VERSION,"4.3.3",">=") && version_compare(PHP_VERSION,"5.0.5","<")) {
	Console::log("        HTML Purifier: PHP 4.3.3 to PHP 5.0.5 is supported");
	define("PURIFIER_VERSION","PHP4");
	include('plugins/html_purifier/php4/library/HTMLPurifier.auto.php');
}
else {
	Console::log("                The version of PHP on this server (".PHP_VERSION.") is insufficient to run HTML Purifier!!!");
}
/**
* A wrapper for the HTML purifier library.  When this class gets instantiated, it determines which version of the purifier library to include based on the PHP version,
* instantiates it (storing it in it's own property for easy re-use), and can then call it with wrapper functions that allow a list of allowable HTML tags on any individual
* purifier call
* @package Plugins
* @author Peter Epp
*/
class H {
	/**
	 * Use HTMLPurifier to purify dirty HTML.
	 *
	 * @param $dirty_html string Text that contains HTML that needs purifying
	 * @param $allowed string Optional - comma-separated list of HTML elements and attributes that are allowed.  Defaults to the most commonly used elements/attributes.
	 * @return string The purified HTML
	 * @author Peter Epp
	 */
	function purify_html($dirty_html,$filters = array()) {
		if (H::is_installed()) {
			if (!isset($filters['allowed'])) {
				$filters['allowed'] = "p[class|style],
								strong,
								b,
								i,
								em,
								h1,
								h2,
								h3,
								h4,
								br,
								hr,
								a[href|title|class|style|target],
								ul[class|style],
								ol[class|style],
								li[class|style],
								dl[class|style],
								dt[class|style],
								dd[class|style],
								span[class|style],
								img[alt|src|width|height|border|class|style],
								sup,
								sub";
			}
			if (!isset($filters['css_allowed'])) {
				$filters['css_allowed'] = array();
			}
			$purifier_config = HTMLPurifier_Config::createDefault();
			$purifier_config->set('Core', 'Encoding', 'UTF-8');
			$allowed = preg_replace("/(\t|\r|\n|\s)/","",$filters["allowed"]);
			$purifier_config->set('HTML','Allowed',$allowed);
			$purifier_config->set('Attr','AllowedFrameTargets',array("_blank","_top","_self"));
			if (!empty($filters["css_allowed"]) && PURIFIER_VERSION == "PHP5") {
				$purifier_config->set('CSS','AllowedProperties',$filters["css_allowed"]);		// NOTE: This does not work in the PHP4 version
			}
			$purifier = new HTMLPurifier($purifier_config);
			$purified_html = $purifier->purify($dirty_html);
			unset($purifier_config,$purifier);
			return $purified_html;
		}
		else {
			return "Purified content could not be displayed!  Please contact the system administrator.";
		}
	}
	/**
	 * Use HTMLPurifier to strip all HTML from text. This is useful, for example, to clean out HTML for the plain text body of an email
	 *
	 * @param string $dirty_text Text that may contain HTML to be stripped
	 * @return string The text with all HTML stripped out
	 * @author Peter Epp
	 */
	function purify_text($dirty_text) {
		if (H::is_installed()) {
			$purifier_config = HTMLPurifier_Config::createDefault();
			$purifier_config->set('Core', 'Encoding', 'UTF-8');
			$purifier_config->set('HTML','Allowed',"");
			$purifier = new HTMLPurifier($purifier_config);
			$purified_text = $purifier->purify($dirty_text);
			unset($purifier_config,$purifier);
			return $purified_text;
		}
		else {
			return "Purified content could not be displayed!  Please contact the system administrator.";
		}
	}
	/**
	 * Whether or not the HTMLPurifier class exists
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function is_installed() {
		return (class_exists('HTMLPurifier'));
	}
	/**
	 * Purify an entire array of text data recursively. All elements of the array will be stripped of any HTML.
	 *
	 * @param string $data 
	 * @return void
	 * @author Peter Epp
	 */
	function purify_array_text($data) {
		if (is_array($data) && !empty($data)) {
			foreach ($data as $key => $value) {
				if (is_array($value)) {
					$data[$key] = H::purify_array_text($value);
				}
				else {
					$data[$key] = H::purify_text($value);
				}
			}
		}
		return $data;
	}
	/**
	 * Purify an entire array of HTML data recursively. All elements of the array will have any HTML purified.
	 *
	 * @param string $data 
	 * @param array $filters Same as for H::purify_html(). Exclude to use default.
	 * @return void
	 * @author Peter Epp
	 */
	function purify_array_html($data,$filters = array()) {
		if (is_array($data) && !empty($data)) {
			foreach ($data as $key => $value) {
				if (is_array($value)) {
					$data[$key] = H::purify_array_html($value,$filters);
				}
				else {
					$data[$key] = H::purify_html($value,$filters);
				}
			}
		}
		return $data;
	}
}
?>