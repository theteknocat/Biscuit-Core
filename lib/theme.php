<?php
/**
 * Encapsulate all theme and rendering-related functions
 *
 * @package Core
 * @author Peter Epp
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
	 * List of IE6-specific CSS files
	 *
	 * @var string
	 */
	protected $_ie6_css_files = array();
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
	 * Place for extra header tag HTML to be registered
	 *
	 * @var string
	 */
	private $_extra_header_tags = array();
	/**
	 * Add this object to list of event observers
	 *
	 * @author Peter Epp
	 */
	public function __construct() {
		Event::add_observer($this);
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
	public function register_ie_css($file) {
		$this->_ie_css_files[] = $file;
	}
	/**
	 * Register an IE6-specific CSS file
	 *
	 * @param string $file 
	 * @return void
	 * @author Peter Epp
	 */
	public function register_ie6_css($file) {
		$this->_ie6_css_files[] = $file;
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
			'http-equiv' => 'Content-Type',
			'content' => 'text/html; charset=utf-8'
		));
		$this->register_header_tag('meta',array(
			'http-equiv' => 'Style-Content-Type',
			'content' => 'text/css; charset=utf-8'
		));
		if ($this->_page()->hidden()) {
			$robots_tag_content = 'noindex,nofollow';
		}
		else {
			$robots_tag_content = 'index,follow';
		}
		$this->register_header_tag('meta',array(
			'name' => 'robots',
			'content' => $robots_tag_content
		));
		if ($this->_page()->keywords() != "") {
			$this->register_header_tag('meta',array(
				'name' => 'keywords',
				'content' => htmlentities($this->_page()->keywords())
			));
		}
		if ($this->_page()->description()) {
			$this->register_header_tag('meta',array(
				'name' => 'description',
				'content' => htmlentities(__($this->_page()->description()))
			));
		}
		// Add Open Graph tags:
		Event::fire('set_og_title');
		if (!empty($this->_og_custom_title)) {
			$og_title = $this->_og_custom_title;
		} else {
			if ($this->_page()->slug() == 'index') {
				$og_title = __(HOME_TITLE);
			} else {
				$og_title = __($this->_page()->navigation_title());
			}
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
		Event::fire('build_header_tags');
		$head_tags = $this->build_meta_tags();
		$head_tags .= '
	<title>'.$this->_page()->full_title().'</title>';
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
						$extra_tag_html .= ' '.$attr_name.'="'.htmlentities($attr_value).'"';
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
	 * Prepare all registered JS and CSS include files that go in the <head> tag of the page and render their HTML
	 *
	 * @return string HTML code - JS script tags and CSS link tags
	 * @author Peter Epp
	 **/
	public function set_js_and_css_includes() {
		Console::log("    Rendering Javascript and CSS includes");
		$this->prep_js_include_files('normal',$this->_js_files);
		$this->prep_js_include_files('standalone',$this->_standalone_js_files);
		$this->add_common_js_files();
		$this->prep_css_include_files($this->_css_files);
		$this->prep_css_include_files($this->_ie_css_files,true);
		$this->prep_css_include_files($this->_ie6_css_files,true,6);
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
	private function prep_js_include_files($type,$files_by_position) {
		foreach ($files_by_position as $position => $files) {
			foreach ($files as $index => $file) {
				$filename = $this->set_js_include_file($file);
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
	private function set_js_include_file($filename) {
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
	private function prep_css_include_files($files, $for_ie = false, $ie_version = 'all') {
		foreach ($files as $index => $file) {
			$filename = $this->set_css_include_file($file);
			if ($for_ie) {
				if ($ie_version == 'all') {
					$this->_ie_css_files[$index]['filename'] = $filename;
				} else if ($ie_version == 6) {
					$this->_ie6_css_files[$index]['filename'] = $filename;
				}
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
	private function set_css_include_file($file) {
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
		if (file_exists($this->_page()->full_theme_path()."/js/common.js")) {
			$this->_js_files['footer'][] = '/'.$this->_page()->theme_dir()."/js/common.js";
		}
		// Add any JS files found in the theme folder to the page footer, if not already registered:
		$theme_js_files = FindFiles::ls($this->_page()->full_theme_path(true).'/js',array('excludes' => 'common.js', 'types' => 'js'));
		if (!empty($theme_js_files)) {
			foreach ($theme_js_files as $js_file) {
				$js_file_path = $this->_page()->theme_dir().'/js/'.$js_file;
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
		if (file_exists($this->_page()->full_theme_path().'/css/jquery-ui.css')) {
			// Use custom UI skin from theme, if present
			array_unshift($this->_css_files, array(
				'filename' => '/'.$this->_page()->theme_dir().'/css/jquery-ui.css',
				'media' => 'screen'
			));
		} else {
			// Use default neutral one from the FW admin theme
			array_unshift($this->_css_files, array(
				'filename' => '/themes/admin/css/jquery-ui.css',
				'media' => 'screen'
			));
		}
		// Add forms stylesheet
		if (file_exists($this->_page()->full_theme_path().'/css/forms.css')) {
			// Use one from theme, if present
			$this->_css_files[] = array(
				'filename' => '/'.$this->_page()->theme_dir().'/css/forms.css',
				'media' => 'screen'
			);
		} else {
			// Otherwise use default one from the FW admin theme
			$this->_css_files[] = array(
				'filename' => '/themes/admin/css/forms.css',
				'media' => 'screen'
			);
		}
		$this->_css_files[] = array(
			'filename' => '/'.$this->_page()->theme_dir().'/css/styles_screen.css',
			'media' => 'screen, projection'
		);
		if (file_exists($this->_page()->full_theme_path().'/css/styles_print.css')) {
			$this->_css_files[] = array(
				'filename' => '/'.$this->_page()->theme_dir().'/css/styles_print.css',
				'media' => 'print'
			);
		}
		if (file_exists($this->_page()->full_theme_path().'/css/ie.css')) {
			$this->_ie_css_files[] = array(
				'filename' => '/'.$this->_page()->theme_dir().'/css/ie.css',
				'media' => 'print'
			);
		}
		if (file_exists($this->_page()->full_theme_path().'/css/ie6.css')) {
			$this->_ie6_css_files[] = array(
				'filename' => '/'.$this->_page()->theme_dir().'/css/ie6.css',
				'media' => 'print'
			);
		}
		// Add any additional CSS files found in the theme folder (for all media):
		$theme_css_files = FindFiles::ls($this->_page()->full_theme_path(true).'/css',array('excludes' => array('styles_screen.css','styles_print.css','styles_tinymce.css','ie.css','ie6.css','forms.css','jquery-ui.css'),'types' => 'css'));
		if (!empty($theme_css_files)) {
			foreach ($theme_css_files as $css_file) {
				if (!preg_match('/\.src\.css/si',$css_file)) {
					$css_file_path = '/'.$this->_page()->theme_dir().'/css/'.$css_file;
					$this->_css_files[] = array(
						'filename' => $css_file_path,
						'media'    => 'all'
					);
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
		if ($this->_page()->theme_favicon_url()) {
			$this->register_header_tag('link',array(
				'rel' => 'shortcut icon',
				'href' => $this->_page()->theme_favicon_url()
			));
		}
		$this->cache_js_and_css();
		$action = Biscuit::instance()->user_input('action');
		if (empty($action)) {
			$action = 'index';
		}
		$cache_folder = sha1($this->_page()->theme_name().'-'.$this->_page()->hyphenized_slug().'-'.$action);
		$header_js_file  = '/js/cache/'.$this->header_js_cache_path();
		$footer_js_file  = '/js/cache/'.$this->footer_js_cache_path();
		$screen_css_file = array('media' => 'screen, projection', 'filename' => '/css/cache/'.$this->screen_css_cache_path());
		$print_css_file  = array('media' => 'print', 'filename' => '/css/cache/'.$this->print_css_cache_path());
		$header_tags =	$this->render_js_or_css_tag('js',$header_js_file) .
						$this->render_js_or_css_tag('css',$screen_css_file) .
						$this->render_js_or_css_tag('css',$print_css_file) .
						$this->render_ie_css() .
						$this->render_standalone_js_tags('header');
		$footer_tags =	$this->render_js_or_css_tag('js',$footer_js_file) .
						$this->render_standalone_js_tags('footer');
		Event::fire('build_extra_include_tags');
		$header_tags .= $this->build_extra_include_tags();
		Biscuit::instance()->set_view_var('header_includes',$header_tags);
		Biscuit::instance()->append_view_var('footer',$footer_tags);
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
						$extra_includes_html .= ' type="text/javascript" charset="utf-8"';
					} else if ($tag_data['tag_type'] == 'style') {
						$extra_includes_html .= ' type="text/css" charset="utf-8"';
					}
					if (!empty($tag_data['tag_attributes'])) {
						foreach ($tag_data['tag_attributes'] as $attr_name => $attr_value) {
							$extra_includes_html .= ' '.$attr_name.'="'.htmlentities($attr_value).'"';
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
			$tag_code = '
	<script type="text/javascript" charset="utf-8" src="%s"></script>';
		}
		else {
			$tag_code = '
	<link rel="stylesheet" type="text/css" href="%s"%s charset="utf-8">';
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
				if ($full_path = Crumbs::file_exists_in_load_path($filename, SITE_ROOT_RELATIVE)) {
					$tags .= $this->render_js_or_css_tag('js',$filename);
				}
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
		$action = Biscuit::instance()->user_input('action');
		if (empty($action)) {
			$action = 'index';
		}
		// IE stylesheet for any version of IE, if present
		$ie_css_file = Crumbs::file_exists_in_load_path('css/cache/'.$this->ie_css_cache_path(), SITE_ROOT_RELATIVE);
		if ($ie_css_file) {
			$returnHtml .= '
	<!--[if IE]>';
			$ie_css_file = $this->add_js_css_version_number($ie_css_file);
			$returnHtml .= '
		<link href="'.$ie_css_file.'" rel="stylesheet" type="text/css" media="all" charset="utf-8">
	<![endif]-->';
		}
		// IE6 stylesheet, if present
		$ie6_css_file = Crumbs::file_exists_in_load_path('css/cache/'.$this->ie6_css_cache_path(), SITE_ROOT_RELATIVE);
		if ($ie6_css_file) {
			$returnHtml .= '
	<!--[if lt IE 7]>';
			$ie6_css_file = $this->add_js_css_version_number($ie6_css_file);
			$returnHtml .= '
		<link href="'.$ie6_css_file.'" rel="stylesheet" type="text/css" media="all" charset="utf-8">
	<![endif]-->';
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
			$template = $this->_page()->select_template();
		}
		return $template;
	}
	/**
	 * Find site image in theme, if present, and return it's fully qualified URL
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function site_image_url() {
		$site_image = null;
		$image_base_path = $this->_page()->full_theme_path(true).'/images/site-image';
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
	 * Return an instance of the current page object
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function _page() {
		return Biscuit::instance()->Page;
	}
}
