<?php
/**
 * The little engine that could
 *
 * @package Core
 * @author Peter Epp
 **/
// TODO Add a mechanism for handling certain file types, like images, in a special when an error 404 occurs.  For example send a "no_image.gif".
class Biscuit extends PluginCore {
	/**
	 * Array of Javascript files used by plugins for inclusion in the template.  Plugins can add to this array by calling Biscuit::register_js('filename','plugin-name')
	 *
	 * @var array
	 */
	var $js_files = array();
	var $queued_js_files = array();
	/**
	 * Array of CSS files used by plugins for inclusion in the template.  Plugins can add to this array by calling Biscuit::register_css(array('filename' => 'filename.css','media' => '[screen/projection/print]'),'plugin-name')
	 *
	 * @var array
	 */
	var $css_files = array();
	var $queued_css_files = array();
	/**
	 * Filename of the view file or template to render
	 *
	 * @var string
	 */
	var $viewfile = '';
	/**
	 * MIME type of output being rendered.  Plugins can check this value with Biscuit::content_type() if they need to do different things based on this value (eg. HitCounter may only want
	 * to count hits for text/html output).  Plugins can set this value to override the default with Biscuit::content_type("mime/type").
	 * See the content_type function for more details
	 *
	 * @var string
	 */
	var $content_type = 'text/html';
	/**
	 * An array of variables that will be made into local variables at render time, if any exist
	 *
	 * @var array
	 */
	var $view_vars = array();
	/**
	 * A list of custom headers to output at render time
	 *
	 * @var array
	 */
	var $custom_headers = array();
	/**
	 * Whether or not the request is bad (400). This used to prevent running of plugins on bad requests when the HTTP status code is not set to 400
	 *
	 * @var string
	 */
	var $request_is_bad = false;
	/**
	 * The HTTP status to set in the headers at render time. Default to 200 (good), to be overridden when applicable
	 *
	 * @var string
	 */
	var $http_status = 200;
	function Biscuit() {
		// Mark the start of the page in the console log for detailed logging mode:
		Console::log("====================== Biscuit Started ======================");
		Console::log("Initialization:");
		Console::log("    Configure error handling");

		Console::set_err_level();
		Console::set_err_handler();

		Console::log("    Initiate database connection");
		DB::connect();

		if (Request::query_string("run_migrations") == 1) {
			Console::log("    Running migrations...");
			BiscuitModel::run_migrations();
			$redirect_to = preg_replace("/\?.+/","",Request::uri());
			Response::redirect($redirect_to);
		}

		Console::log("    Define global system settings");
		BiscuitModel::define_system_settings();

		Crumbs::set_timezone();
		Console::log("    Current System Time: ".date("r"));

		$this->check_server_config();

		Console::log("    Start session");
		SessionStorage::install();
		Session::start();

		$this->load_plugins();

	}
	/**
	 * Initialize all page variables, retrieve page data, instantiate and run plugins
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function run() { // Initialize the current web page

		Console::log("    Initialize page");
		// Set the page configuration based on defaults and/or user input:
		$this->initialize_page();

		// Store any user input in a variable:
		$this->set_user_input();

		// Redirect if needed based on security defined for the current page in the database compated to the port the user is connected on:
		if ($this->force_secure == 1 && Request::port() != '443' && (!defined("SSL_DISABLED") || !SSL_DISABLED)) {
			Console::log("    Redirecting to secure page...");
			Response::redirect(SECURE_URL.'/'.$this->full_page_name);
		}
		// Set to render with template by default. Plugins that do special rendering that musn't use the template can override this
		$this->render_with_template(true);
		Console::log("    Initialize plugins");
		// Initialize plugins for this page:
		$this->init_page_plugins();

		EventManager::notify('biscuit_initialization');

		Console::log("\nRequest to Process:");
		$has_post_data = "No";
		if (Request::is_post() && !empty($this->raw_user_input)) {
			$has_post_data = "Yes";
			if (DEBUG) {
				$has_post_data .= ", content:\n".print_r($this->raw_user_input,true);
			}
		}
		$query_params = "No";
		if (Request::query_string() !== null) {
			$query_params = "Yes";
			if (DEBUG) {
				$has_query_string = ", content:\n".print_r(Request::query_string(),true);
			}
		}
		$log_str =  "    URI:              ".Request::uri()."\n".
		            "    Status:           ".$this->http_status."\n".
		            "    Method:           ".Request::method()."\n".
		            "    Type:             ".Request::type()."\n".
		            "    Page Name:        ".$this->page_name."\n".
		            "    Is AJAX:          ".((Request::is_ajax()) ? "Yes" : "No")."\n".
		            "    Has Query Params: ".$query_params."\n".
		            "    Has Post Data:    ".$has_post_data;
		Console::log($log_str);
		if (DEBUG) {
			Console::log("    All Request Headers:\n\n".print_r(Request::headers(),true));
		}
		if ($this->http_status == 200 && !RequestTokens::check($this->full_page_name)) {
			Console::log("        BAD REQUEST! Token does not match!");
			$this->request_is_bad = true;
			if (Request::is_ajax() && (Request::type() == "update" || Request::type() == "validation")) {
				// On ajax update requests, simply output the bad request message
				$this->render("Error 400 - Bad Request.\n\nSorry, but your request cannot be processed by the server.");
				Biscuit::end_program();
			}
			else if (Request::is_ajax() || Request::is_ajaxy_iframe_post()) {
				// On ajaxy iframe posts render a Javascript alert
				$this->render_js('alert("Error 400 - Bad Request.\n\nSorry, but your request cannot be processed by the server.");');
				Biscuit::end_program();
			}
			else {
				// Otherwise load the error 400 page, setting the HTTP status accordingly
				$this->http_status = 400;
				$this->page_name = "error400";
				$this->full_page_name = "error400";
				$this->fetch_page_data("error400");
			}
		}
		// Execute the run() function for any page plugins that have one:
		$this->run_page_plugins();
		$this->register_queued_includes();
	}
	/**
	 * Process page-related GET and POST variables, configure the page and fetch page information from the database
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function initialize_page() {
		// Set the page as not yet rendered by default
		$this->page_rendered = false;
		// Create the temporary folder if it is set and does not exist:
		$tmp_dir = TEMP_DIR;
		if (!empty($tmp_dir) && !file_exists($tmp_dir)) {
			@mkdir($tmp_dir);
		}
		// Move post variables that are normally expected as GET variables into the GET array:
		if (Request::form('page') != null) {
			Request::set_query('page',Request::form('page'));
		}
		if (Request::form('printer_friendly') != null) {
			Request::set_query('printer_friendly',Request::form('printer_friendly'));
		}
		// Set basic request information:
		if (Request::query_string('page') == null) {
			Console::log("        No page provided in post or query string, using \"index\"");
			$page_name = "index";
		}
		else {
			$page_name = Request::query_string('page');
		}
		$this->full_page_name = $page_name; // eg "resources/links"
		$page_bits = explode("/",$page_name);
		$this->page_name = end($page_bits); // eg "links"
		$this->hyphenized_page_name = implode("-",$page_bits);	// eg "resources-links"

		// Check to see if a printer friendly version of the page was requested
		if (Request::query_string('printer_friendly') == null) {
			$this->printer_friendly = 0;
		}
		else {
			$this->printer_friendly = Request::query_string('printer_friendly');
		}
		// Note: The Prototype Ajax.Updater() function seems to add a strange variable as a parameter with the key "_", hence the reason for trying to clear it
		Request::clear_query(array('page','printer_friendly','_'));
		Request::clear_form(array('page','printer_friendly','_'));
		// Load page data based on page existence and accessibility:
		if (!$this->page_exists($this->full_page_name)) {
			$this->http_status = 404;
			$this->page_name = "error404";
			$this->full_page_name = "error404";
			$this->fetch_page_data("error404");
		}
		else {
			$this->fetch_page_data($this->full_page_name);
			if ($this->page_name == "error404") {
				$this->http_status = 404;
			}
			elseif (!$this->plugin_exists('Authenticator') || ($this->plugin_exists('Authenticator') && Permissions::can_access($this->access_level))) {
				$this->http_status = 200;
			}
			else {
				$this->http_status = 403;
				$this->page_name = "error403";
				$this->full_page_name = "error403";
				$this->fetch_page_data("error403");
			}
		}
		// Override whatever the DB defined as the template file with a supplied parameter, if present
		if (Request::query_string('template_name') != null) {
			$this->set_template(Request::query_string('template_name'));
			Request::clear_query('template_name');		// We won't need this any more
		}
		// Set the default view file for this page:
		$this->set_viewfile();
	}
	/**
	 * Process all user provided GET and POST variables that were left after processing the page-related variables. Store the raw data in one array, and the un-escaped data in another.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function set_user_input() {
		$raw_user_input = array();
		
		if (Session::var_exists('user_input')) {
			$raw_user_input = array_merge(Session::get('user_input'),$raw_user_input);
			Session::unset_var('user_input');
		}
		if (Request::has_query_string()) {
			$raw_user_input = array_merge(Request::query_string(), $raw_user_input);
		}
		if (Request::has_form_data()) {
			$raw_user_input = array_merge(Request::form(), $raw_user_input);
		}

		$this->raw_user_input = $raw_user_input;
		$this->user_input = Crumbs::clean_input($raw_user_input);
	}
	/**
	 * Fetch data about either the current page or a specified page and set the data as properties of the Biscuit object
	 *
	 * @param string $pagename Optional - the names of the page to retrieve. The find_one function will default to the current page name if not provided.
	 * @return void
	 * @author Peter Epp
	 */
	function fetch_page_data($pagename) {
		$page_vars = BiscuitModel::find_one($pagename);
		foreach ($page_vars as $k => $v) {
			$this->$k = $v;
		}
	}
	/**
	 * Whether or not a specified page exists
	 *
	 * @author Peter Epp
	 */
	function page_exists($pagename) {
		return (BiscuitModel::find_one($pagename) !== false);
	}
	/**
	 * Add a JS file to the list of ones to include in the page
	 *
	 * @return void
	 * @param string $js_file The name of the file relative to the "scripts" folder
	 * @param string $queue_for_later Optional - whether or not to add the file to the end of the list. This queues it to be registered after all plugin JS files have been registered
	 * @author Peter Epp
	 **/
	function register_js($js_file,$queue_for_later = false) {
		if (!is_bool($queue_for_later)) {
			$queue_for_later = false;		// For backwards compatibility
		}
		if (!in_array($js_file,$this->queued_js_files) && !in_array($js_file,$this->js_files)) {
			if ($queue_for_later) {
				$this->queued_js_files[] = $js_file;
			}
			else {
				$this->js_files[] = $js_file;
			}
			Console::log("                        JS file: ".$js_file);
		}
	}
	/**
	 * Whether or not a specified JavaScript file has been registered
	 *
	 * @param string $owner_name Name of the package or plugin (case-sensitive)
	 * @return bool
	 * @author Peter Epp
	 */
	function has_registered_js($filename) {
		return (in_array($filename,$this->js_files));
	}
	/**
	 * Add a CSS file to the list of ones to include in the page
	 *
	 * @return void
	 * @param string $css_file An associative array containing 'filename' and 'media' values, where media is the CSS media type (ie. "screen" or "print"). The filename must be relative to the "css" folder.
	 * @param string $queue_for_later Optional - whether or not to add the file to the end of the list. This queues it to be registered after all plugin CSS files have been registered
	 * @author Peter Epp
	 **/
	function register_css($css_file,$queue_for_later = false) {
		if (!is_bool($queue_for_later)) {
			$queue_for_later = false;		// For backwards compatibility
		}
		if (!in_array($css_file,$this->queued_css_files) && !in_array($css_file,$this->css_files)) {
			if ($queue_for_later) {
				$this->queued_css_files[] = $css_file;
			}
			else {
				$this->css_files[] = $css_file;
			}
			Console::log("                        CSS file: ".$css_file['filename'].' ('.$css_file['media'].')');
		}
	}
	/**
	 * Register JS and CSS include files queued to render after plugin includes
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function register_queued_includes() {
		if (!empty($this->queued_js_files)) {
			foreach ($this->queued_js_files as $file) {
				$this->js_files[] = $file;
			}
			unset($this->queued_js_files);
		}
		if (!empty($this->queued_css_files)) {
			foreach ($this->queued_css_files as $file) {
				$this->css_files[] = $file;
			}
			unset($this->queued_css_files);
		}
	}
	/**
	 * Prepare all registered JS and CSS include files that go in the <head> tag of the page and render their HTML
	 *
	 * @return string HTML code - JS script tags and CSS link tags
	 * @author Peter Epp
	 **/
	function render_header_includes() {
		Console::log("    Rendering Javascript and CSS includes");
		$this->prep_header_include_files("js",$this->js_files);
		$this->add_common_js_files();
		$this->prep_header_include_files("css",$this->css_files);
		$this->add_site_css_files();
		return $this->render_include_tags();
	}
	/**
	 * Prep all the JS and CSS includes by checking for their existence in the system and prepending the filenames with the appropriate path
	 *
	 * @param string $type "js" or "css" (case-sensitive)
	 * @param string $files The array of filenames to prep (usually Biscuit::js_files or Biscuit::css_files)
	 * @param string $for_minification Whether or not to prepend the full file system path needed for minification
	 * @return void
	 * @author Peter Epp
	 */
	function prep_header_include_files($type,$files) {
		foreach ($files as $index => $file) {
			$filename = $this->set_header_include_file($type,$file);
			if ($type == "js") {
				$this->js_files[$index] = $filename;
			}
			else if ($type == "css") {
				$this->css_files[$index]['filename'] = $filename;
			}
		}
	}
	/**
	 * Check the existence of a JS or CSS include file and if found prepend the appropriate path and return it
	 *
	 * @param string $type "js" or "css"
	 * @param string $file Filename without path
	 * @param string $for_minification Whether or not to prepend the full file system path needed for minification
	 * @return void
	 * @author Peter Epp
	 */
	function set_header_include_file($type,$file) {
		$dir_prefix = ($type == "js") ? "scripts/" : "css/";
		if ($type == "js") {
			$actual_file = $file;
		}
		else {
			$actual_file = $file['filename'];
		}
		if ($include_file = Crumbs::file_exists_in_site($dir_prefix.$actual_file)) {
			return $include_file;
		}
		else {
			Console::log("        Missing file: ".$dir_prefix.$actual_file);
			return false;
		}
	}
	/**
	 * Set the list of js and css files to minify in session
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function set_minify_groups() {
		$minify_groups = array();
		foreach ($this->js_files as $index => $file) {
			if (!empty($file)) {
				$minify_groups["jscript-".$index] = SITE_ROOT.$file;
			}
		}
		foreach ($this->css_files as $index => $file) {
			if (!empty($file['filename'])) {
				$minify_groups["stylesheet-".$index] = SITE_ROOT.$file['filename'];
			}
		}
		Session::set("minify_groups",$minify_groups);
	}
	/**
	 * Add common Javascript libraries to the list as required
	 *
	 * @param string $for_minification Whether or not to prepend the full file system path needed for minification
	 * @return void
	 * @author Peter Epp
	 */
	function add_common_js_files() {
		if (defined("USE_FRAMEWORK_JS") && USE_FRAMEWORK_JS == 1) {
			$this->js_files[] = "/framework/scripts/framework.js";
			$this->js_files[] = "/framework/scripts/common.js";
		}
		if (file_exists(SITE_ROOT."/scripts/common.js")) {
			$this->js_files[] = "/scripts/common.js";
		}
	}
	/**
	 * Add the site-specific CSS files to the list as required
	 *
	 * @param string $for_minification Whether or not to prepend the full file system path needed for minification
	 * @return void
	 * @author Peter Epp
	 */
	function add_site_css_files() {
		$this->css_files[] = array(
			'filename' => '/css/styles_screen.css',
			'media' => 'screen, projection'
		);
		if (file_exists(SITE_ROOT.'/css/styles_print.css')) {
			$this->css_files[] = array(
				'filename' => '/css/styles_print.css',
				'media' => 'print'
			);
		}
	}
	/**
	 * Render the script and link tags for standard (non-minified) header includes
	 *
	 * @return string HTML tags
	 * @author Peter Epp
	 */
	function render_include_tags() {
		$for_minification = Response::minify_header_includes();
		if ($for_minification) {
			$this->set_minify_groups();
		}
		return		$this->render_js_or_css_tags("js",$this->js_files,$for_minification) .
					$this->render_js_or_css_tags("css",$this->css_files,$for_minification) .
					$this->render_ie_css();
	}
	/**
	 * Render either JS or CSS tags from a list of files
	 *
	 * @param string $type "js" or "css"
	 * @param array $file_list An array of either JS filenames, or CSS files with both a "name"
	 * @param string $for_minification 
	 * @return void
	 * @author Peter Epp
	 */
	function render_js_or_css_tags($type,$file_list,$for_minification) {
		if ($type == "js") {
			$tag_code = '
	<script language="javsacript" type="text/javascript" charset="utf-8" src="%s"></script>';
		} else {
			$tag_code = '
	<link rel="stylesheet" type="text/css" href="%s"%s>';
		}
		foreach ($file_list as $index => $file) {
			if ($type == "js") {
				$filename = $file;
				$media = "";
			} else {
				$filename = $file['filename'];
				$media = ' media="'.$file['media'].'"';
			}
			if ($for_minification) {
				$src_bits = explode("/",$filename);
				array_pop($src_bits);
				$file_path = implode("/",$src_bits)."/stylesheet{$index}-min.css";
			} else {
				$file_path = $filename;
			}
			if (!empty($filename)) {
				$full_file_path = SITE_ROOT.$file_path;
				$file_path .= '?_v='.filemtime($full_file_path);
				$returnHtml .= sprintf($tag_code,$file_path,$media);
			}
		}
		return $returnHtml;
	}
	/**
	 * Render link tags for IE-specific CSS files if they exist
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function render_ie_css() {
		$returnHtml = '';
		// IE stylesheet for any version of IE, if present
		if (file_exists(SITE_ROOT.'/css/ie.css')) {
			$returnHtml .= '
	<!--[if IE]>
		<link href="/css/ie.css" rel="stylesheet" type="text/css">
	<![endif]-->';
		}
		// IE6-specific stylesheet, if present
		if (file_exists(SITE_ROOT.'/css/ie6.css')) {
			$returnHtml .= '
	<!--[if lt IE 7]>
		<link href="/css/ie6.css" rel="stylesheet" type="text/css">
	<![endif]-->';
		}
		return $returnHtml;
	}
	/**
	 * Set the page's main content view file
	 * 
	 * @return void
	 **/
	function set_viewfile() {
		$base = "views/".$this->full_page_name; // is this a security risk? are we potentially exposing files?

		if ($html_file = Crumbs::file_exists_in_load_path($base.".html")) {
		    $this->viewfile = $html_file;
		}
		elseif ($php_file = Crumbs::file_exists_in_load_path($base.".php")) {
		    $this->viewfile = $php_file;
		}
		elseif ($txt_file = Crumbs::file_exists_in_load_path($base.".txt")) {
		    $this->viewfile = $txt_file;
		}
		elseif ($default_html = Crumbs::file_exists_in_load_path("views/default.html")) {
		    $this->viewfile = $default_html;
		}
		elseif ($default_php = Crumbs::file_exists_in_load_path("views/default.php")) {
			$this->viewfile = $default_php;
		}
	}
	/**
	 * Get or set the MIME type of the render output
	 *
	 * @param string $type Optional - MIME type of render output. Omit this value when getting the mime type. Provide it when you want to set the value.  It must be
	 * a valid MIME type and can include, but is not limited to:
	 *
	 * text/html	// Default
	 * text/css
	 * text/javascript
	 * application/pdf
	 * application/rss+xml
	 * etc...
	 *
	 * @return mixed Boolean "true" when setting, or the output type string when getting
	 * @author Peter Epp
	 */
	function content_type($content_type = null) {
		if (!empty($content_type) && is_string($content_type)) {
			$this->content_type = $content_type;
			return true;
		}
		else {
			return $this->content_type;
		}
	}
	/**
	 * Add a custom response header.  It must be a valid http response header, such as "X-JSON: true"
	 *
	 * @param string $header The header 
	 * @return void
	 * @author Peter Epp
	 */
	function add_header($header) {
		if (!empty($header)) {
			$this->custom_headers[] = $header;
			return true;
		}
		Console::log("    Biscuit::add_header(): No header value provided, custom header has not been added.");
		return false;
	}
	/**
	 * Output content type and any custom headers if defined
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function content_headers() {
		Console::log("    Content type: ".$this->content_type);
		Response::header("Content-type: ".$this->content_type);
		if (!empty($this->custom_headers)) {
			foreach ($this->custom_headers as $header) {
				Console::log("    Custom header: ".$header);
				Response::header($header);
			}
		}
	}
	/**
	 * Render the web page. Static content passed as an argument takes first precedence, plugins that request rendering take second, and the page itself takes third.
	 *
	 * @param string $content Optional - static HTML content to render instead of a template or view file
	 * @return void
	 * @author Peter Epp
	 */
	function render($content = "") {
		if ($this->page_rendered == false && Request::type() != 'server_action') {
			Console::log("Rendering Output:");
			// Set applicable headers for browser:
			Response::standard_headers($this->http_status,$this->access_level);
			$this->content_headers();
			// Only render if the request type is correct and the page has not already been rendered:
			if (!empty($content)) {
				Console::log("    Content source: Static");
				Console::log("    Content to render:\n".$content);
				echo $content;
			}
			else {
				Console::log("    Content source: Include file");
				// Store a reference to this (the web page object) in a nice variable for the view file:
				$Biscuit = &$this;
				// Store references to all plugin objects in the local namespace for the view
				foreach ($this->plugins as $plugin_name => $plugin_obj) {
					$$plugin_name = &$this->plugins[$plugin_name];
				}
				// Set any variables in the view_vars array to the local namespace
				foreach ($this->view_vars as $key => $value) {
					if (!isset($$key)) {
						$$key = $value;
					}
				}
				Console::log("    Using template: ".(($this->render_with_template()) ? "Yes" : "No"));
				if (!$this->render_with_template()) {
					// If we are not rendering with the template render the view file only:
					$render_file = $this->viewfile;
				}
				else {
					$render_file = $this->select_template();
				}
				// Render the output file:
				Console::log("    Render file: ".$render_file);
				if ($this->render_with_template()) {
					Console::log("    Page view file: ".$this->viewfile);
					$render_type = "Template file";
					$include_error_response = "The page cannot be displayed because the template file could not be found. Please contact the system administrator immediately.";
				}
				else {
					$render_type = "View file";
					$include_error_response = "The page cannot be displayed because the view file could be not found. Please contact the system administrator immediately.";
				}
				$return = include $render_file;
				Crumbs::include_response($return,$render_type,$include_error_response);
			}
			$this->page_rendered = true;
		}
	}
	/**
	 * Set or check whether or not to render the page with template
	 *
	 * @param bool $set_value Optional - provide this argument to set whether or not to render with template
	 * @return bool Wether or not to render with template
	 * @author Peter Epp
	 */
	function render_with_template($set_value = null) {
		if (is_bool($set_value)) {
			$this->use_template = $set_value;
		}
		return $this->use_template;
	}
	/**
	 * Select a template file for rendering
	 *
	 * @return string The path and filename of the template to render relative to either the project or framework root 
	 * @author Peter Epp
	 */
	function select_template() {
		if ($this->printer_friendly == 0) {
			// If a printer-friendly version of the page has NOT been requested
			if (Request::is_ajax()) {
				$template_file = "templates/ajax.php";
			}
			else {
				if ($this->template_name != "") {
					// If a custom template has been defined for this page, include it.
					$template_file = "templates/".$this->template_name.".php";
				}
				else {
					// Otherwise include the main template:
					$template_file = "templates/standard.php";
				}
			}
		}
		elseif ($this->printer_friendly == 1) {
			// Use printer-friendly template:
			$template_file = "templates/printer_friendly.php";
		}
		return $template_file;
	}
	/**
	 * Set a specific template file to be used
	 *
	 * @param string $template_name 
	 * @return void
	 * @author Peter Epp
	 */
	
	function set_template($template_name) {
		if (!empty($template_name)) {
			$this->template_name = $template_name;
		}
	}
    /**
     * Render as Javascript.
     * 
     * The code is rendered within SCRIPT tags.
     * 
     * @param string $javascript the JS to render back to the user
     **/
    function render_js($javascript) {
		$output = '<script type="text/javascript" language="javascript" charset="utf-8">'.$javascript."</script>";
		$this->render($output);
    }

	/**
	 * Render as json object literal.
	 * @param array $values An indexed array of values to be converted to json
	 * @return void
	 * @author Peter Epp
	**/
	function render_json($values) {
		$this->content_type("application/json");
		$this->add_header("X-JSON: true");
		$this->render(Crumbs::to_json($values));
	}
	/**
	 * Output the HTML meta tags and title tag for the page
	 *
	 * @return void
	 * @author Peter Epp
	 */
	
	function render_header_tags() {
		$returnHtml = '
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="Style-Content-Type" content="text/css; charset=utf-8">';
		if ($this->hidden == 1) {
			$returnHtml .= '
	<meta name="robots" content="noindex,nofollow">';
		}
		else {
			$returnHtml .= '
	<meta name="robots" content="index,follow">';
		}
		if ($this->keywords != "") {
			$returnHtml .= '
	<meta name="keywords" content="'.$this->keywords.'">';
		}
		if ($this->description != "") {
			$returnHtml .= '
	<meta name="description" content="'.$this->description.'">';
		}
		$returnHtml .= '
	<title>'.BiscuitModel::full_title($this->page_id,$this->page_name).'</title>';
		if (file_exists(SITE_ROOT."/favicon.ico")) {
			$returnHtml .= '
	<!-- Website icon -->
	<link rel="shortcut icon" href="/favicon.ico">';
		}
		return $returnHtml;
	}
	/**
	 * Set a variable to be set as local a local variable at render time
	 *
	 * @param string $key Name of the variable
	 * @param string $value Value of the variable
	 * @return mixed Value of the variable
	 * @author Peter Epp
	 * @author Lee O'Mara
	 */
	function set_var($key,$value) {
		$this->view_vars[$key] = $value;
		return $value;
	}
	/**
	 * Set the body title
	 *
	 * @param string $title The body title
	 * @return void
	 **/
	function set_title($title) {
		$this->pagetitle = $title;
	}
	/**
	 * Return the date that the current page was last updated
	 *
	 * @return string A nicely formatted last-updated string with the date
	 * @author Peter Epp
	 */
	function get_pageupdate() {
		// Now we grab the date on the content file for the current page:
		if ($this->viewfile != false) {
			$filedate = filemtime(SITE_ROOT.$this->viewfile);
		}
		// Now we compare the date from the database to the date of the content file and use whichever is newer:
		if (isset($filedate) && isset($newest_date)) {
			$use_date = (($filedate > $newest_date) ? $filedate : $newest_date);
		}
		elseif (isset($filedate) && !isset($newest_date)) {
			$use_date = $filedate;
		}
		elseif (!isset($filedate) && isset($newest_date)) {
			$use_date = $newest_date;
		}
		if (isset($use_date)) {
			return "Last Updated: ".date("F j, Y",$use_date);
		}
		else {
			return "&nbsp;";
		}
	}
	/**
	 * Produce a copyright notice to display in the page
	 *
	 * @param string $prefix Optional - something to start the notice with
	 * @param string $suffix Optional - something to end the notice with
	 * @return string Copyright notice
	 * @author Peter Epp
	 */
	function copyright_notice($prefix = '',$suffix = '') {
		$cnotice = '';
		if (!empty($prefix)) {
			$cnotice .= $prefix." ";
		}
		$cnotice .= "Copyright &copy;".LAUNCH_YEAR.((date("Y") != LAUNCH_YEAR) ? ' - '.date("Y") : '').' '.SITE_OWNER;
		if (!empty($suffix)) {
			$cnotice .= " ".$suffix;
		}
		return $cnotice;
	}
	/**
	 * Check server config - at the moment this only logs the info
	 * TODO make this check configuration and throw errors when problems are found.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function check_server_config() {
		if (DEBUG) {
			Console::log("    Server Information:");
			Console::log("        Server IP:            ".Request::server("SERVER_ADDR"));
			Console::log("        Server Name:          ".Request::server("SERVER_NAME"));
			Console::log("        Server Software:      ".Request::server("SERVER_SOFTWARE"));
			Console::log("\n    PHP Config:");
			Console::log("        upload_max_filesize:  ".ini_get("upload_max_filesize"));
			Console::log("        post_max_size:        ".ini_get("post_max_size"));
			Console::log("        max_input_time:       ".ini_get("max_input_time"));
			Console::log("        max_execution_time:   ".ini_get("max_execution_time"));
			$magic_quotes = (get_magic_quotes_gpc()) ? "On" : "Off";
			Console::log("        magic_quotes_gpc:     ".$magic_quotes);
			$register_globals = ini_get("register_globals");
			if (empty($register_globals)) {
				$register_globals = "empty - should therefore be Off";
			}
			Console::log("        register_globals:     ".$register_globals);
			Console::log("        display_errors:       ".ini_get("display_errors")."\n");
		}
	}
	/**
	 * Do any wrap up and call exit
	 *
	 * @param bool $full_run_complete Whether or not Biscuit ran all the way through before exiting. This is just for logging purposes, and should only be set to true at the end of core.php.
	 * @return void
	 * @author Peter Epp
	 */
	function end_program($full_run_complete = false) {
		// Disconnect from the database:
		DB::disconnect();
		if (!$full_run_complete) {
			Console::log("\nBiscuit was asked to exit prior to completing it's run.\n");
		}
		else {
			Session::flash_empty();
		}
		// Put an ending marker in the log
		Console::log("\n============= Done! Can I have a biscuit now? ===============\n");
		exit;
	}
}	// END Biscuit class
?>