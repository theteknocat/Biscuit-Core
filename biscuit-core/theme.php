<?php
/**
 * Encapsulate all theme-related functions
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: theme.php 14689 2012-06-27 21:27:54Z teknocat $
 */
class Theme extends JsAndCssCache {
	/**
	 * Array of Javascript files for inclusion in the template.  Modules and extensions can add to this array by calling Biscuit::register_js('filename')
	 *
	 * @var array
	 */
	protected $_js_files = array('header' => array(), 'footer' => array());
	/**
	 * Array of Javascript files that must stand alone and not be compiled with the rest
	 *
	 * @var string
	 */
	protected $_standalone_js_files = array();
	/**
	 * List of JS files queued for adding to the end of the main list
	 *
	 * @var string
	 */
	protected $_queued_js_files = array();
	/**
	 * You guessed it - list of standalone JS files queued for adding to the end of the main list
	 *
	 * @var string
	 */
	protected $_queued_standalone_js_files = array();
	/**
	 * Array of CSS files for inclusion in the template.  Modules and extensions can add to this array by calling Biscuit::register_css(array('filename' => 'filename.css','media' => '[all/screen/projection/print]'))
	 *
	 * @var array
	 */
	protected $_css_files = array();
	/**
	 * List of CSS files queued for adding to the end of the main list
	 *
	 * @var string
	 */
	protected $_queued_css_files = array();
	/**
	 * List of IE-specific CSS files
	 *
	 * @var string
	 */
	protected $_ie_css_files = array();
	/**
	 * URL for Open Graph image. If not set, goes with generic image
	 *
	 * @var string
	 */
	private $_og_image_url;
	/**
	 * Custom title to use for the Open Graph title tag. If not set, goes with the current page title
	 *
	 * @var string
	 */
	private $_og_custom_title;
	/**
	 * Open Graph description. Not used if not set
	 *
	 * @var string
	 */
	private $_og_description;
	/**
	 * Open Graph type. Can be overridden by modules
	 *
	 * @var string
	 */
	private $_og_type = 'website';
	/**
	 * Override value for the robots meta tag
	 *
	 * @var string
	 */
	private $_robots_meta_tag_value = '';
	/**
	 * List of all allowed Open Graph types as defined at {@link http://developers.facebook.com/docs/opengraph/#types}
	 *
	 * @var string
	 */
	private $_allowed_og_types = array(
		"activity",
		"sport",
		"bar",
		"company",
		"cafe",
		"hotel",
		"restaurant",
		"cause",
		"sports_league",
		"sports_team",
		"band",
		"government",
		"non_profit",
		"school",
		"university",
		"actor",
		"athlete",
		"author",
		"director",
		"musician",
		"politician",
		"public_figure",
		"city",
		"country",
		"landmark",
		"state_province",
		"album",
		"book",
		"drink",
		"food",
		"game",
		"product",
		"song",
		"movie",
		"tv_show",
		"blog",
		"website",
		"article"
	);
	/**
	 * Place for extra header tag HTML to be registered
	 *
	 * @var string
	 */
	private $_extra_header_tags = array();
	/**
	 * Place to cache the active template name
	 *
	 * @var string
	 */
	private $_active_template;
	/**
	 * Place to cache the active theme name
	 *
	 * @var string
	 */
	private $_active_theme;
	/**
	 * Theme configuration data
	 *
	 * @var array
	 */
	private $_theme_configuration = array();
	/**
	 * Place to store the favicon URL
	 *
	 * @var string|false
	 */
	private $_favicon_url = false;
	/**
	 * Register event observer
	 *
	 * @author Peter Epp
	 */
	public function __construct() {
		Event::add_observer($this);
	}
	/**
	 * Check the full theme path is not empty and if it is, revert to "default" and display an error message
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function initialize() {
		// Check for theme path existence:
		if (!$this->full_theme_path()) {
			Session::flash('user_error', 'Invalid theme: '.$this->_active_theme.'. Reverting to default.');
			$this->_active_theme = 'default';
		}
		// Load theme configuration:
		$theme_info_file = $this->full_theme_path().'/theme.info';
		if (file_exists($theme_info_file)) {
			$this->_theme_configuration = parse_ini_file($theme_info_file);
		} else {
			$this->_theme_configuration = array(
				'name' => ucwords(AkInflector::humanize($this->theme_name())),
				'author' => 'Unknown',
				'description' => 'No description'
			);
		}
	}
	/**
	 * Return the name of the theme to use for the current page request
	 *
	 * @return string The computer-readable name of the theme
	 * @author Peter Epp
	 */
	public function theme_name() {
		if (empty($this->_active_theme)) {
			$this->_active_theme = 'default';
			if (defined('SITE_THEME') && SITE_THEME != '') {
				$this->_active_theme = SITE_THEME;
			}
			if ($this->_page()->theme_name()) {
				$this->_active_theme = $this->_page()->theme_name();
			}
			if (Request::query_string('theme_name')) {
				// If a theme name is provided in the query string it supercedes everything else, so use it to over-ride the page's defined theme:
				$this->_active_theme = Request::query_string('theme_name');
				Request::clear_query('theme_name');		// We won't need this any more
			} else {
				// If no theme name was supplied in the query string, fire an event to allow other modules or extensions to over-ride the theme if desired.
				// We pass the event handler the theme name in addition to the object because if the observer calls this method again in order to find the
				// normal theme name it'll go into an infinite loop.
				Event::fire('get_theme_name', $this, $this->_active_theme);
			}
		}
		return $this->_active_theme;
	}
	/**
	 * Override the active theme
	 *
	 * @param string $theme_name 
	 * @return void
	 * @author Peter Epp
	 */
	public function theme_override($theme_name) {
		$this->_active_theme = $theme_name;
	}
	/**
	 * Return the theme directory for the current request
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function theme_dir() {
		return "themes/".$this->theme_name();
	}
	/**
	 * Return the full path to the theme directory for the current request
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function full_theme_path($site_root_relative = false) {
		if ($theme_path = Crumbs::file_exists_in_load_path($this->theme_dir(),$site_root_relative)) {
			return $theme_path;
		}
		return false;
	}
	/**
	 * Return the name of the template file for the current page request
	 *
	 * @param return $this 
	 * @return void
	 * @author Peter Epp
	 */
	public function template_name() {
		if (empty($this->_active_template)) {
			if (Request::is_ajax()) {
				// For ajax requests, template files should be prefixed with "ajax-"
				$filename_prefix = 'ajax-';
			} else {
				$filename_prefix = '';
			}
			$path_prefix = $this->full_theme_path() . '/templates/';
			// First look for template based on module/action name:
			$primary_module_name = $this->_page()->primary_module_name();
			if (!empty($primary_module_name)) {
				$path_friendly_module_name = AkInflector::underscore($primary_module_name);
				// Look for template specific to both the primary module and current action name:
				$action = Request::user_input('action');
				if (empty($action)) {
					$action = 'index';
				}
				$module_action_template = $filename_prefix . 'module-'.$path_friendly_module_name.'-'.$action;
				$module_action_template_path = $path_prefix . $module_action_template.'.php';
				if (file_exists($module_action_template_path)) {
					$this->_active_template = $module_action_template;
				} else {
					// Else look for template specific just to the primary module:
					$module_template = $filename_prefix . 'module-'.$path_friendly_module_name;
					$module_template_path = $path_prefix . $module_template.'.php';
					if (file_exists($module_template_path)) {
						$this->_active_template = $module_template;
					}
				}
			}
			if (empty($this->_active_template)) {
				// Else see if a template_name is explicitly defined for this page in the database:
				$template_name = $filename_prefix.$this->_page()->template_name();
				if (!empty($template_name) && (file_exists($this->full_theme_path().'/templates/' . $template_name . '.php'))) {
					// If defined in the database then the attribute will contain a value
					$this->_active_template = $template_name;
				} else {
					// Look for template named by page slug
					$template = $filename_prefix . $this->_page()->hyphenized_slug();
					if (file_exists($this->theme_dir().'/templates/'. $template .'.php')) {
						$this->_active_template = $template;
					} else {
						// Otherwise resort to default template.

						// Backwards compatibility note: Default templates used to be "standard" for normal requests and "ajax" for ajax requests.
						// However we want to change that to "default" or "ajax-default" for semantic reasons. As such, only use "default" or "ajax-default"
						// if present otherwise "standard" or "ajax" depending on request type.
						$default_template = $filename_prefix . 'default';
						if (file_exists($this->full_theme_path().'/templates/' . $default_template . '.php')) {
							$this->_active_template = $default_template;
						} else {
							if (Request::is_ajax()) {
								$this->_active_template = "ajax";
							} else {
								$this->_active_template = "standard";
							}
						}
					}
				}
			}
		}
		return $this->_active_template;
	}
	/**
	 * Add a JS file to the list of ones to include in the page
	 *
	 * @return void
	 * @param string $js_file The name of the file relative to the "scripts" folder
	 * @param string $queue_for_later Optional - whether or not to add the file to the end of the list. This queues it to be registered after all module JS files have been registered
	 * @author Peter Epp
	 **/
	public function register_js($position,$filename,$stand_alone = false,$queue_for_end = false) {
		if (substr($filename, 0, 4) == 'http') {
			$stand_alone = true;
		}
		if ($position != "header" && $position != "footer") {
			trigger_error("Biscuit::register_js() expects 'header' or 'footer' for the first argument", E_USER_ERROR);
		}
		if (!$this->has_registered_js($position,$filename)) {
			if ($stand_alone) {
				if ($queue_for_end) {
					$this->_standalone_queued_js_files[$position][] = $filename;
				} else {
					$this->_standalone_js_files[$position][] = $filename;
				}
			} else {
				if ($queue_for_end) {
					$this->_queued_js_files[$position][] = $filename;
				} else {
					$this->_js_files[$position][] = $filename;
				}
			}
		}
	}
	/**
	 * Whether or not a specified JavaScript file has already been registered
	 *
	 * @param string $owner_name Name of the package or module (case-sensitive)
	 * @return bool
	 * @author Peter Epp
	 */
	private function has_registered_js($position,$js_file) {
		$in_js_files                   = (!empty($this->_js_files[$position]) && in_array($js_file,$this->_js_files[$position]));
		$in_standalone_js_files        = (!empty($this->_standalone_js_files[$position]) && in_array($js_file,$this->_standalone_js_files[$position]));
		$in_queued_js_files            = (!empty($this->_queued_js_files[$position]) && in_array($js_file,$this->_queued_js_files[$position]));
		$in_queued_standalone_js_files = (!empty($this->_queued_standalone_js_files[$position]) && in_array($js_file,$this->_queued_standalone_js_files[$position]));
		return ($in_js_files || $in_standalone_js_files || $in_queued_js_files || $in_queued_standalone_js_files);
	}
	/**
	 * Add a CSS file to the list of ones to include in the page
	 *
	 * @return void
	 * @param string $css_file An associative array containing 'filename' and 'media' values, where media is the CSS media type (ie. "screen" or "print"). The filename must be relative to the "css" folder.
	 * @param string $queue_for_later Optional - whether or not to add the file to the end of the list. This queues it to be registered after all module CSS files have been registered
	 * @author Peter Epp
	 **/
	public function register_css($file,$queue_for_later = false) {
		if (!$this->has_registered_css($file)) {
			if ($queue_for_later) {
				$this->_queued_css_files[] = $file;
			}
			else {
				$this->_css_files[] = $file;
			}
		}
	}
	/**
	 * Register an IE-specific CSS file
	 *
	 * @param string $file 
	 * @return void
	 * @author Peter Epp
	 */
	public function register_ie_css($css_info, $ie_version = 'all') {
		$this->_ie_css_files[$ie_version][] = $css_info;
	}
	/**
	 * Register an IE6-specific CSS file. This method exists for backwards compatibility only
	 *
	 * @param string $file 
	 * @return void
	 * @author Peter Epp
	 */
	public function register_ie6_css($css_info) {
		$this->register_ie_css($css_info, 6);
	}
	/**
	 * Whethor or not a specified CSS file has already been registered
	 *
	 * @param string $css_file 
	 * @return void
	 * @author Peter Epp
	 */
	private function has_registered_css($css_file) {
		return (in_array($css_file,$this->_queued_css_files) || in_array($css_file,$this->_css_files));
	}
	/**
	 * Register the HTML code for an extra header tag
	 *
	 * @param string $tag_html 
	 * @return void
	 * @author Peter Epp
	 */
	public function register_header_tag($tag_type,$tag_attributes,$tag_content = '') {
		if ($tag_type == 'meta' || $tag_type == 'script' || $tag_type == 'link' || $tag_type == 'style') {
			$this->_extra_header_tags[] = array('tag_type' => $tag_type,'tag_attributes' => $tag_attributes, 'tag_content' => $tag_content);
		} else {
			Console::log("Unable to register header tag of type '".$tag_type."'. Either it's not a valid type of tag or I don't know about it.");
		}
	}
	/**
	 * Output the HTML meta tags and title tag for the page
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function set_header_tags() {
		$this->register_header_tag('meta',array(
			'name' => 'Generator',
			'content' => 'Biscuit '.Biscuit::version()
		));
		$this->register_header_tag('meta',array(
			'name' => 'Author',
			'content' => SITE_OWNER
		));
		$this->register_header_tag('meta',array(
			'name' => 'Copyright',
			'content' => Crumbs::copyright_notice()
		));
		$this->register_header_tag('meta',array(
			'http-equiv' => 'Content-Type',
			'content' => 'text/html; charset=utf-8'
		));
		Event::fire('set_robots_meta_tag', $this);
		if (!empty($this->_robots_meta_tag_value)) {
			$robots_tag_content = $this->_robots_meta_tag_value;
		} else if ($this->_page()->hidden()) {
			$robots_tag_content = 'noindex, nofollow';
		} else {
			$robots_tag_content = 'index, follow';
		}
		$this->register_header_tag('meta',array(
			'name' => 'robots',
			'content' => $robots_tag_content
		));
		$this->register_header_tag('meta',array(
			'name' => 'googlebot',
			'content' => $robots_tag_content
		));
		if ($this->_page()->keywords() != "") {
			$this->register_header_tag('meta',array(
				'name' => 'keywords',
				'content' => $this->_page()->keywords()
			));
		}
		if ($this->_page()->description()) {
			$this->register_header_tag('meta',array(
				'name' => 'description',
				'content' => __($this->_page()->description())
			));
		}
		$full_page_title = __(H::purify_text($this->_page()->full_title()));
		// Add Open Graph tags:
		Event::fire('set_og_title');
		if (!empty($this->_og_custom_title)) {
			$og_title = $this->_og_custom_title;
		} else {
			$og_title = $full_page_title;
		}
		$this->register_header_tag('meta',array(
			'property' => 'og:title',
			'content' => $og_title
		));
		$this->register_header_tag('meta',array(
			'property' => 'og:site_name',
			'content' => __(SITE_TITLE)
		));
		Event::fire("set_og_description");
		if (!empty($this->_og_description)) {
			$this->register_header_tag('meta',array(
				'property' => 'og:description',
				'content' => $this->_og_description
			));
		}
		Event::fire("set_og_image");
		if (!empty($this->_og_image_url)) {
			$page_image = $this->_og_image_url;
		} else {
			$page_image = $this->site_image_url();
		}
		if (!empty($page_image)) {
			$this->register_header_tag('meta',array(
				'property' => 'og:image',
				'content' => $page_image
			));
		}
		Event::fire("set_og_type");
		$this->register_header_tag('meta',array(
			'property' => 'og:type',
			'content' => $this->_og_type
		));
		Event::fire('build_header_tags');
		$head_tags = $this->build_meta_tags();
		$head_tags .= '
	<title>'.$full_page_title.'</title>';
		Biscuit::instance()->set_view_var("header_tags",$head_tags);
	}
	/**
	 * Compile the HTML code for any additional meta tags that were registered
	 *
	 * @return string
	 * @author Peter Epp
	 */
	private function build_meta_tags() {
		$extra_tag_html = '';
		if (!empty($this->_extra_header_tags)) {
			foreach ($this->_extra_header_tags as $tag_data) {
				if ($tag_data['tag_type'] == 'meta') {
					$extra_tag_html .= '<meta';
					foreach ($tag_data['tag_attributes'] as $attr_name => $attr_value) {
						$extra_tag_html .= ' '.$attr_name.'="'.addslashes($attr_value).'"';
					}
					$extra_tag_html .= '>';
				}
			}
		}
		return $extra_tag_html;
	}
	/**
	 * Register JS and CSS include files queued to render after module includes
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function register_queued_includes() {
		if (!empty($this->_queued_js_files)) {
			foreach ($this->_queued_js_files as $position => $files) {
				foreach ($files as $filename) {
					$this->_js_files[$position][] = $filename;
				}
			}
			$this->_queued_js_files = array();
		}
		if (!empty($this->_queued_standalone_js_files)) {
			foreach ($this->_queued_standalone_js_files as $position => $files) {
				foreach ($files as $filename) {
					$this->_standalone_js_files[$position][] = $filename;
				}
			}
			$this->_queued_standalone_js_files = array();
		}
		if (!empty($this->_queued_css_files)) {
			foreach ($this->_queued_css_files as $file) {
				$this->_css_files[] = $file;
			}
			$this->_queued_css_files = array();
		}
	}
	/**
	 * Prepare all registered JS and CSS include files page and render their HTML
	 *
	 * @return string HTML code - JS script tags and CSS link tags
	 * @author Peter Epp
	 **/
	public function set_js_and_css_includes() {
		Console::log("    Rendering Javascript and CSS includes");
		$this->set_js_include_paths('normal',$this->_js_files);
		$this->set_js_include_paths('standalone',$this->_standalone_js_files);
		$this->add_common_js_files();
		$this->set_css_include_paths($this->_css_files);
		foreach ($this->_ie_css_files as $ie_version => $css_files) {
			$this->set_css_include_paths($css_files,true,$ie_version);
		}
		$this->add_theme_css_files();
		$this->set_include_tags();
	}
	/**
	 * Prep all the JS includes by checking for their existence in the system and prepending the filenames with the appropriate path
	 *
	 * @param string $type "js" or "css" (case-sensitive)
	 * @param string $files The array of filenames to prep (usually Biscuit::js_files or Biscuit::css_files)
	 * @param string $for_minification Whether or not to prepend the full file system path needed for minification
	 * @return void
	 * @author Peter Epp
	 */
	private function set_js_include_paths($type,$files_by_position) {
		foreach ($files_by_position as $position => $files) {
			foreach ($files as $index => $file) {
				$filename = $this->set_js_include_path($file);
				if (empty($filename)) {
					continue;
				}
				switch ($type) {
					case 'normal':
						$this->_js_files[$position][$index] = $filename;
						break;
					case 'standalone':
						$this->_standalone_js_files[$position][$index] = $filename;
						break;
				}
			}
		}
	}
	/**
	 * Check the existence of a CSS include file and if found prepend the appropriate path and return it
	 *
	 * @param string $type "js" or "css"
	 * @param string $file Filename without path
	 * @param string $for_minification Whether or not to prepend the full file system path needed for minification
	 * @return void
	 * @author Peter Epp
	 */
	private function set_js_include_path($filename) {
		if (substr($filename, 0, 4) == 'http') {
			return $filename;
		}
		if ($include_file = Crumbs::file_exists_in_load_path($filename, SITE_ROOT_RELATIVE)) {
			return $include_file;
		}
		else {
			Console::log("        Missing JS file: ".$filename);
			return false;
		}
	}
	/**
	 * Prep all the CSS includes by checking for their existence in the system and prepending the filenames with the appropriate path
	 *
	 * @param string $type "js" or "css" (case-sensitive)
	 * @param string $files The array of filenames to prep (usually Biscuit::js_files or Biscuit::css_files)
	 * @param string $for_minification Whether or not to prepend the full file system path needed for minification
	 * @return void
	 * @author Peter Epp
	 */
	private function set_css_include_paths($files, $for_ie = false, $ie_version = 'all') {
		foreach ($files as $index => $file) {
			$filename = $this->set_css_include_path($file);
			if (empty($filename)) {
				continue;
			}
			if ($for_ie) {
				$this->_ie_css_files[$ie_version][$index]['filename'] = $filename;
			} else {
				$this->_css_files[$index]['filename'] = $filename;
			}
		}
	}
	/**
	 * Check the existence of a CSS include file and if found prepend the appropriate path and return it
	 *
	 * @param string $type "js" or "css"
	 * @param string $file Filename without path
	 * @param string $for_minification Whether or not to prepend the full file system path needed for minification
	 * @return void
	 * @author Peter Epp
	 */
	private function set_css_include_path($file) {
		if ($include_file = Crumbs::file_exists_in_load_path($file['filename'], SITE_ROOT_RELATIVE)) {
			return $include_file;
		}
		else {
			Console::log("        Missing CSS file: ".$file);
			return false;
		}
	}
	/**
	 * Add common Javascript libraries to the list as required
	 *
	 * @param string $for_minification Whether or not to prepend the full file system path needed for minification
	 * @return void
	 * @author Peter Epp
	 */
	private function add_common_js_files() {
		// Add jQuery, jQuery UI, jQUery i18n and framework JS
		// We use array_unshift because jquery and the framework JS must come before all others to prevent problems
		array_unshift($this->_js_files['header'],"/framework/js/jquery.min.js");
		array_unshift($this->_js_files['footer'],"/framework/js/jquery-ui.min.js","/framework/js/jquery.i18n.properties.min.js","/framework/js/framework.js");
		if (DEBUG) {
			$this->_js_files['footer'][] = "/framework/js/debug_enabler.js";
		}
		if (file_exists($this->full_theme_path()."/js/common.js")) {
			$this->_js_files['footer'][] = '/'.$this->theme_dir()."/js/common.js";
		}
		// Add any JS files found in the theme folder to the page footer, if not already registered:
		$theme_js_files = FindFiles::ls($this->full_theme_path(true).'/js',array('excludes' => 'common.js', 'types' => 'js'));
		if (!empty($theme_js_files)) {
			foreach ($theme_js_files as $js_file) {
				$js_file_path = $this->theme_dir().'/js/'.$js_file;
				if (!in_array($js_file_path, $this->_js_files['footer']) && !in_array($js_file_path, $this->_js_files['header'])) {
					$this->_js_files['footer'][] = '/'.$js_file_path;
				}
			}
		}
	}
	/**
	 * Add the site-specific CSS files to the list as required
	 *
	 * @param string $for_minification Whether or not to prepend the full file system path needed for minification
	 * @return void
	 * @author Peter Epp
	 */
	private function add_theme_css_files() {
		// Prepend jQuery UI styles:
		if ($full_file_path = Crumbs::file_exists_in_load_path($this->theme_dir().'/css/jquery-ui.css', SITE_ROOT_RELATIVE)) {
			array_unshift($this->_css_files, array(
				'filename' => $full_file_path,
				'media' => 'screen'
			));
		} else {
			// Use default neutral one from the FW admin theme
			array_unshift($this->_css_files, array(
				'filename' => '/framework/themes/sea_biscuit/css/jquery-ui.css',
				'media' => 'screen'
			));
		}
		// Add forms stylesheet
		if ($full_file_path = Crumbs::file_exists_in_load_path($this->theme_dir().'/css/forms.css', SITE_ROOT_RELATIVE)) {
			// Use one from theme, if present
			$this->_css_files[] = array(
				'filename' => $full_file_path,
				'media' => 'screen'
			);
		} else {
			// Otherwise use default one from the FW admin theme
			$this->_css_files[] = array(
				'filename' => '/framework/themes/sea_biscuit/css/forms.css',
				'media' => 'screen'
			);
		}
		$this->_css_files[] = array(
			'filename' => Crumbs::file_exists_in_load_path($this->theme_dir().'/css/styles_screen.css', SITE_ROOT_RELATIVE),
			'media' => 'screen, projection'
		);
		if ($full_theme_path = Crumbs::file_exists_in_load_path($this->theme_dir().'/css/styles_print.css', SITE_ROOT_RELATIVE)) {
			$this->_css_files[] = array(
				'filename' => '/'.$this->theme_dir().'/css/styles_print.css',
				'media' => 'print'
			);
		}
		// Add any additional CSS files found in the theme folder (for all media):
		$theme_css_files = FindFiles::ls($this->full_theme_path(true).'/css',array('excludes' => array('styles_screen.css','styles_print.css','styles_tinymce.css','forms.css','jquery-ui.css'),'types' => 'css'));
		if (!empty($theme_css_files)) {
			foreach ($theme_css_files as $css_file) {
				if (!preg_match('/\.src\.css/si',$css_file)) {
					$css_file_path = Crumbs::file_exists_in_load_path($this->theme_dir().'/css/'.$css_file, SITE_ROOT_RELATIVE);
					$css_file_info = array(
						'filename' => $css_file_path,
						'media'    => 'all'
					);
					if (substr($css_file,0,2) == 'ie') {
						if (substr($css_file,2,1) == '.') {
							$ie_version = 'all';
						} else {
							$ie_version = trim(substr($css_file,2,strpos($css_file,'.')-2),'_-');
						}
						$this->_ie_css_files[$ie_version][] = $css_file_info;
					} else {
						$this->_css_files[] = $css_file_info;
					}
				}
			}
		}
	}
	/**
	 * Render JS and CSS include tags into vars for the view
	 *
	 * @return string HTML tags
	 * @author Peter Epp
	 */
	private function set_include_tags() {
		if ($this->_favicon_url()) {
			$this->register_header_tag('link',array(
				'rel' => 'shortcut icon',
				'href' => $this->_favicon_url()
			));
		}
		if ($this->cache_js_and_css()) {
			$include_tags = $this->build_cached_include_tag_html();
		} else {
			$include_tags = $this->build_uncached_include_tag_html();
		}
		$include_tags['header'] .= $this->render_ie_css() . $this->render_standalone_js_tags('header');
		$include_tags['footer'] .= $this->render_standalone_js_tags('footer');
		Event::fire('build_extra_include_tags');
		$include_tags['header'] .= $this->build_extra_include_tags();
		if (!empty($this->_theme_configuration['uses_html5'])) {
			$include_tags['header'] .= '
	<!--[if lt IE 9]>
		<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->';
		}
		Biscuit::instance()->set_view_var('header_includes',$include_tags['header']);
		Biscuit::instance()->append_view_var('footer',$include_tags['footer']);
	}
	/**
	 * Build include tag HTML using cached JS and CSS files
	 *
	 * @return array
	 * @author Peter Epp
	 */
	private function build_cached_include_tag_html() {
		$tags = array();
		$header_js_file  = '/var/cache/js/'.$this->header_js_cache_path();
		$footer_js_file  = '/var/cache/js/'.$this->footer_js_cache_path();
		$screen_css_file = array('media' => 'screen, projection', 'filename' => '/var/cache/css/'.$this->screen_css_cache_path());
		$print_css_file  = array('media' => 'print', 'filename' => '/var/cache/css/'.$this->print_css_cache_path());
		$tags['header'] =	$this->render_js_or_css_tag('js',$header_js_file) .
							$this->render_js_or_css_tag('css',$screen_css_file) .
							$this->render_js_or_css_tag('css',$print_css_file);
		$tags['footer'] =	$this->render_js_or_css_tag('js',$footer_js_file);
		return $tags;
	}
	/**
	 * Build include tag HTML using non-cached JS and CSS files
	 *
	 * @return array
	 * @author Peter Epp
	 */
	private function build_uncached_include_tag_html() {
		$tags = array('header' => '', 'footer' => '');
		foreach ($this->_js_files['header'] as $js_file) {
			$tags['header'] .= $this->render_js_or_css_tag('js', $js_file);
		}
		foreach ($this->_css_files as $css_file) {
			$tags['header'] .= $this->render_js_or_css_tag('css', $css_file);
		}
		foreach ($this->_js_files['footer'] as $js_file) {
			$tags['footer'] .= $this->render_js_or_css_tag('js', $js_file);
		}
		return $tags;
	}
	/**
	 * Build extra script or link include tags
	 *
	 * @return string
	 * @author Peter Epp
	 */
	private function build_extra_include_tags() {
		$extra_includes_html = '';
		if (!empty($this->_extra_header_tags)) {
			foreach ($this->_extra_header_tags as $tag_data) {
				if ($tag_data['tag_type'] == 'script' || $tag_data['tag_type'] == 'link' || $tag_data['tag_type'] == 'style') {
					$extra_includes_html .= '<'.$tag_data['tag_type'];
					if ($tag_data['tag_type'] == 'script') {
						$extra_includes_html .= ' type="text/javascript"';
						if (array_key_exists('src',$tag_data['tag_attributes'])) {
							$extra_includes_html .= ' charset="utf-8"';
						}
					} else if ($tag_data['tag_type'] == 'style') {
						$extra_includes_html .= ' type="text/css"';
					}
					if (!empty($tag_data['tag_attributes'])) {
						foreach ($tag_data['tag_attributes'] as $attr_name => $attr_value) {
							$extra_includes_html .= ' '.$attr_name.'="'.addslashes($attr_value).'"';
						}
					}
					if ($tag_data['tag_type'] == 'script' || $tag_data['tag_type'] == 'style') {
						$extra_includes_html .= '>'.$tag_data['tag_content'].'</'.$tag_data['tag_type'];
					}
					$extra_includes_html .= '>';
				}
			}
		}
		return $extra_includes_html;
	}
	/**
	 * Render a JS or CSS tag
	 *
	 * @param string $type "js" or "css"
	 * @param string|array $file Full path to JS file or a CSS file info array
	 * @return void
	 * @author Peter Epp
	 */
	private function render_js_or_css_tag($type,$file) {
		$returnHtml = '';
		if ($type == "js") {
			$tag_code = '<script type="text/javascript" charset="utf-8" src="%s"></script>';
		}
		else {
			$tag_code = '<link rel="stylesheet" type="text/css" href="%s"%s>';
		}
		if ($type == "js") {
			$filename = $file;
			$media = "";
		}
		else {
			$filename = $file['filename'];
			$media = ' media="'.$file['media'].'"';
		}
		if (!empty($filename)) {
			$filename = $this->add_js_css_version_number($filename);
			$returnHtml = sprintf($tag_code,$filename,$media);
		}
		return $returnHtml;
	}
	/**
	 * Render tags for all the standalone JS files
	 *
	 * @param string $position 'header' or 'footer'
	 * @return void
	 * @author Peter Epp
	 */
	private function render_standalone_js_tags($position) {
		$tags = '';
		if (!empty($this->_standalone_js_files[$position])) {
			foreach ($this->_standalone_js_files[$position] as $filename) {
				$tags .= $this->render_js_or_css_tag('js',$filename);
			}
		}
		return $tags;
	}
	/**
	 * Render link tags for IE-specific CSS files if they exist
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function render_ie_css() {
		Console::log("    Rendering IE-specific CSS tags");
		$returnHtml = '';
		if (!empty($this->_ie_css_files)) {
			foreach ($this->_ie_css_files as $ie_version => $css_files) {
				if ($ie_version == 'all') {
					$condition = 'IE';
				} else if ($ie_version == 'not') {
					$condition = '!IE';
				} else {
					$version_bits = explode(' ',str_replace(array('_','-'),' ',$ie_version));
					if (count($version_bits) > 1) {
						$condition = $version_bits[0].' IE '.$version_bits[1];
					} else {
						$condition = 'IE '.$version_bits[0];
					}
				}
				$returnHtml .= '
	<!--[if '.$condition.']>';
				if ($ie_version == 'not') {
					$returnHtml .= '<!-->';
				}
				// Stylesheet(s) for any version of IE, if present
				$cached_ie_css_file = Crumbs::file_exists_in_load_path('var/cache/css/'.$this->ie_css_cache_path($ie_version), SITE_ROOT_RELATIVE);
				if ($cached_ie_css_file) {
					// Use cached file, if present
					$ie_css_file = array('filename' => $cached_ie_css_file, 'media' => 'all');
					$returnHtml .= $this->render_js_or_css_tag('css', $ie_css_file);
				} else {
					// Otherwise render all individual CSS files
					foreach ($css_files as $css_file) {
						$full_css_file_path = Crumbs::file_exists_in_load_path($css_file['filename'], SITE_ROOT_RELATIVE);
						if ($full_css_file_path) {
							$css_file_info = array(
								'filename' => $full_css_file_path,
								'media' => 'all'
							);
							$returnHtml .= $this->render_js_or_css_tag('css', $css_file_info);
						}
					}
				}
				if ($ie_version == 'not') {
					$returnHtml .= '<!-- ';
				}
				$returnHtml .= '<![endif]-->';
			}
		}
		return $returnHtml;
	}
	/**
	 * Add the JS and CSS version number to a JS or CSS filename, if applicable
	 *
	 * @param string $filename 
	 * @return string
	 * @author Peter Epp
	 */
	private function add_js_css_version_number($filename) {
		if (substr($filename, 0, 4) == 'http') {
			return $filename;
		}
		return $filename.'?_v='.filemtime(SITE_ROOT.$filename);
	}
	/**
	 * Select a template for rendering. First checks for a template provided by the module for it's current action and if not present asks the Page
	 * model to provide the template defined for the current page.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function select_template() {
		$template = Biscuit::instance()->select_primary_module_template();
		if (empty($template)) {
			$template = $this->theme_dir()."/templates/".$this->template_name().".php";
		}
		return $template;
	}
	/**
	 * Return the URL for the theme's favicon
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function _favicon_url() {
		Event::fire('get_favicon_url', $this);
		if (empty($this->_favicon_url)) {
			if (file_exists($this->full_theme_path()."/favicon.ico")) {
				if (stristr($this->full_theme_path(),FW_ROOT)) {
					$this->_favicon_url = '/framework/'.$this->theme_dir().'/favicon.ico';
				} else {
					$this->_favicon_url = '/'.$this->theme_dir().'/favicon.ico';
				}
			}
		}
		return $this->_favicon_url;
	}
	/**
	 * Set the favicon URL to override the one that comes with the theme
	 *
	 * @param string $url 
	 * @return void
	 * @author Peter Epp
	 */
	public function set_favicon_url($url) {
		$this->_favicon_url = $url;
	}
	/**
	 * Find site image in theme, if present, and return it's fully qualified URL
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function site_image_url() {
		$site_image = null;
		$image_base_path = $this->full_theme_path(true).'/images/site-image';
		if (file_exists(SITE_ROOT.$image_base_path.'.jpg')) {
			$site_image = $image_base_path.'.jpg';
		} else if (file_exists(SITE_ROOT.$image_base_path.'.gif')) {
			$site_image = $image_base_path.'.gif';
		} else if (file_exists(SITE_ROOT.$image_base_path.'.png')) {
			$site_image = $image_base_path.'.png';
		}
		if (!empty($site_image)) {
			$site_image = STANDARD_URL.$site_image;
		}
		return $site_image;
	}
	/**
	 * Set the URL to an image to use for the open graph image tag
	 *
	 * @param string $image_url 
	 * @return void
	 * @author Peter Epp
	 */
	public function set_og_image_url($image_url) {
		$this->_og_image_url = $image_url;
	}
	/**
	 * Set a custom title for the Open Graph title tag
	 *
	 * @param string $title 
	 * @return void
	 * @author Peter Epp
	 */
	public function set_og_title($title) {
		$this->_og_custom_title = $title;
	}
	/**
	 * Set an Open Graph description
	 *
	 * @param string $description 
	 * @return void
	 * @author Peter Epp
	 */
	public function set_og_description($description) {
		$this->_og_description = $description;
	}
	/**
	 * Set the Open Graph type, if it's one of the allowed types
	 *
	 * @param string $type 
	 * @return void
	 * @author Peter Epp
	 */
	public function set_og_type($type) {
		if (in_array($type,$this->_allowed_og_types)) {
			$this->_og_type = $type;
		}
	}
	/**
	 * Override the value of the robots meta tag
	 *
	 * @param string $value 
	 * @return void
	 * @author Peter Epp
	 */
	public function set_robots_meta_tag_value($value) {
		$this->_robots_meta_tag_value = $value;
	}
	/**
	 * Return an instance of the current page object
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function _page() {
		return Biscuit::instance()->Page;
	}
}
