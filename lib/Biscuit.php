<?php
/**
 * The little engine that could
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 **/
// TODO Add a mechanism for handling certain file types, like images, in a special when an error 404 occurs.  For example send a "no_image.gif".
class Biscuit extends ModuleCore implements Singleton {
	/**
	 * Array of Javascript files for inclusion in the template.  Modules and extensions can add to this array by calling Biscuit::register_js('filename')
	 *
	 * @var array
	 */
	protected $_js_files = array();
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
	 * An array of variables that will be made into local variables at render time, if any exist
	 *
	 * @var array
	 */
	protected $_view_vars = array();
	/**
	 * Whether or not the request is bad (400). This used to prevent running of modules on bad requests when the HTTP status code is not set to 400
	 *
	 * @var string
	 */
	protected $_request_is_bad = false;
	/**
	 * Whether or not to use a template when rendering. Defaults to true.
	 *
	 * @var string
	 */
	protected $_use_template = true;
	/**
	 * List of directories needed by modules for writing files
	 *
	 * @var array
	 */
	protected $_module_and_extension_directory_list = array();
	/**
	 * The page slug requested in the query
	 *
	 * @var string
	 */
	protected $_request_slug;
	/**
	 * Place to put vars parsed from the request URI by the parse_request() method
	 *
	 * @var string
	 */
	protected $_parsed_query = array();
	/**
	 * Reference to factory for the Page madel
	 *
	 * @var ModelFactory
	 */
	public $page_factory;
	/**
	 * Reference to instantiation of self
	 *
	 * @author Peter Epp
	 */
	private static $_instance;
	/**
	 * Reference to the configuration object
	 *
	 * @var Configuration
	 * @see Configuration
	 */
	protected $Config;
	/**
	 * Content compiled by the compile_page method
	 *
	 * @var string
	 */
	private $_compiled_content;
	/**
	 * Place for extra header tag HTML to be registered
	 *
	 * @var string
	 */
	private $_extra_header_tags = array();
	/**
	 * Return a singleton instance of the Biscuit object
	 *
	 * @return Biscuit
	 * @author Peter Epp
	 */
	public static function instance() {
		if (empty(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
	 * Set the configuration object as a property
	 *
	 * @param Configuration $config 
	 * @return void
	 * @author Peter Epp
	 */
	public function register_configuration(Configuration $config) {
		$this->Config = $config;
	}
	/**
	 * Allow access to modules and extensions by calling magic method names, for example:
	 *
	 * $Biscuit->ModuleAuthenticator()->user_can_edit();
	 *
	 * $Biscuit->ExtensionNavigation()->render_list_menu();
	 *
	 * @param string $method_name 
	 * @param string $args 
	 * @return object|void
	 * @author Peter Epp
	 */
	public function __call($method_name, $args) {
		if (method_exists($this,$method_name)) {
			// The method exists on the object already, but for some reason PHP decided to defer to the magic caller anyway.
			// This seems to happen in PHP 5.2.11 when you call a protected or private method from an external context. We
			// therefore need a way to catch that, so this is my workaround
			throw new CoreException("An attempt was made to call ".get_class($this)."::".$method_name.", which exists on the object, but PHP deferred to the magic __call() method. You probably defined the method as private or protected but tried to call it outside the context of the ".get_class($this)." object instance.");
		}
		if ($method_name == 'Modules') {
			return $this->modules;
		} else if ($method_name == 'Extensions') {
			return $this->extensions;
		} else if (substr($method_name, 0, 6) == 'Module') {
			$module_name = substr($method_name, 6);
			if (is_object($this->modules[$module_name])) {
				return $this->modules[$module_name];
			}
		} else if (substr($method_name, 0, 9) == 'Extension') {
			$extension_name = substr($method_name, 9);
			if (is_object($this->extensions[$extension_name])) {
				return $this->extensions[$extension_name];
			}
		}
		// Throw an exception if we couldn't find the appropriate method to call
		throw new CoreException("Undefined method: ".get_class($this)."::".$method_name);
	}
	/**
	 * Initialize page, load modules and extensions, and set the user's input
	 *
	 * @author Peter Epp
	 */
	private function __construct() {
		try {
			Event::add_observer($this);

			// Setup page factory
			$this->page_factory = new ModelFactory("Page");

			// Load and initialize global extensions
			$this->load_global_extensions();
			$this->init_global_extensions();

			$this->load_shared_models();

			$this->load_modules();

			$this->parse_request();

			// Store any user input in a variable:
			$this->set_user_input();

			// Fire an event as a hook for any extensions that want to do something prior to page initialization. This allows an extension to take over page initialization if desired
			Event::fire('biscuit_initialization',$this->_request_slug);

			if (empty($this->Page)) {	// Only initialize the page now if it wasn't already initialized, in case an extension already took care of it
				$this->initialize_page();
			} else {	// If the page was already initialized, validate the page request
				$this->validate_page_request();
			}

			$this->init_page_modules();

		} catch (CoreException $e) {
			trigger_error("Core Exception: ".$e->getMessage());
		}
	}
	/**
	 * Run extension or module install or uninstall operation if requested
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function run_install_or_uninstall_ops() {
		if (Request::query_string('install_extension')) {
			$this->install_global_extension(Request::query_string('install_extension'));
		} else if (Request::query_string('uninstall_extension')) {
			$this->uninstall_global_extension(Request::query_string('uninstall_extension'));
		}
		if (Request::query_string('install_module')) {
			$this->install_module(Request::query_string('install_module'));
		} else if (Request::query_string('uninstall_module')) {
			$this->uninstall_module(Request::query_string('uninstall_module'));
		}
	}
	/**
	 * Dispatch the request 
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function dispatch() {
		$this->request_token_check();
		$record_or_view_found = true;
		try {
			$this->run_install_or_uninstall_ops();

			$this->module_and_extension_directory_setup();

			Event::fire("dispatch_request");

			Console::log("\nDispatch Request:");
			$has_post_data = "No";
			if (Request::is_post() && !empty($this->raw_user_input)) {
				$has_post_data = "Yes";
				if (DEBUG) {
					Console::log_var_dump('Request post data',$this->raw_user_input);
				}
			}
			$query_params = "No";
			if (Request::query_string() !== null) {
				$query_params = "Yes";
				if (DEBUG) {
					Console::log_var_dump('Request query string',Request::query_string());
				}
			}
			$log_str =  "    URI:              ".Request::uri()."\n".
			            "    Status:           ".Response::http_status()."\n".
			            "    Method:           ".Request::method()."\n".
			            "    Type:             ".Request::type()."\n".
			            "    Page Name:        ".$this->Page->short_slug()."\n".
			            "    Is AJAX:          ".((Request::is_ajax()) ? "Yes" : "No")."\n".
			            "    Has Query Params: ".$query_params."\n".
			            "    Has Post Data:    ".$has_post_data;
			Console::log($log_str);
			if (DEBUG) {
				Console::log_var_dump('All request headers',Request::headers());
			}

			$this->check_modified();

			$this->run_page_modules();

			if (!$this->page_cache_is_valid() && !$this->request_is_bad()) {
				$this->register_queued_includes();
			}

			$this->render();
		} catch (ModuleException $e) {
			trigger_error("Module Exception: ".$e->getMessage(), E_USER_ERROR);
		} catch (ThemeException $e) {
			trigger_error("Theme Exception: ".$e->getMessage(), E_USER_ERROR);
		} catch (RecordNotFoundException $e) {
			// This exception is thrown by a module when a requested DB record cannot be found by the provided ID. Abstract controller throws it on edit, show
			// and delete actions automatically. Remember to throw the exception at the appropriate time in your module in any custom action methods.
			$record_or_view_found = false;
		} catch (ViewNotFoundException $e) {
			// This exception gets thrown by the module core when trying to render the requested view file for the module. It ends up in the same result as the above
			// Exception but it's named differently for semantic purposes
			$record_or_view_found = false;
		}
		if (!$record_or_view_found) {
			Response::http_status(404);
			$this->Page = $this->page_factory->find_by("slug",'error404');
			$this->render();
		}
	}
	/**
	 * Fetch the page and validate the request
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function initialize_page() {
		Console::log("    Initialize page");
		// Set the page as not yet rendered by default
		$this->page_rendered = false;

		Console::log("        Fetching page: ".$this->_request_slug);
		$this->Page = $this->page_factory->find_by("slug",$this->_request_slug);
		$this->validate_page_request();
	}
	/**
	 * Validate that the page loaded and the request is valid
	 *
	 * @return void
	 * @author Peter Epp
	 * @throws ThemeException
	 */
	private function validate_page_request() {
		if (!$this->Page) {
			// See if an alias exists for the requested page
			$alias_factory = new PageAliasFactory();
			$alias = $alias_factory->find_by('old_slug',$this->_request_slug);
			if (empty($alias)) {
				Response::http_status(404);
				$this->Page = $this->page_factory->find_by("slug",'error404');
			} else {
				$base_request_uri = '/'.$this->_request_slug;
				$full_request_uri = Request::uri();
				$full_request_uri = rtrim($full_request_uri,'/');	// Strip trailing slash, if present
				if (strlen($full_request_uri) > strlen($base_request_uri)) {
					$remaining_uri = substr($full_request_uri,strlen($base_request_uri));
					$remaining_uri = ltrim($remaining_uri,'/');
					$redirect_uri = '/'.$alias->current_slug().'/'.$remaining_uri;
				} else {
					$redirect_uri = '/'.$alias->current_slug();
				}
				Response::redirect($redirect_uri,true);
				return;
			}
		} else if (preg_match('/(error[0-9]{3})/',$this->_request_slug)) {
			$http_status = (int)substr($this->_request_slug,-3);
			Response::http_status($http_status);
		} else {
			// Redirect if needed based on security defined for the current page in the database compated to the port the user is connected on:
			if ($this->Page->force_secure() && Request::port() != '443' && (!defined("SSL_DISABLED") || !SSL_DISABLED)) {
				Console::log("    Redirecting to secure page...");
				Response::redirect('https://'.Request::host().Request::uri());
			}
		}
		if (Response::http_status() != 200) {
			$this->set_never_cache();
		}
		// Check for theme path existence:
		if (!$this->Page->full_theme_path()) {
			throw new ThemeException("Theme not found: ".$this->Page->theme_name());
		}
	}
	/**
	 * Check the page token and produce a bad request response if the check fails
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function request_token_check() {
		if (Response::http_status() == 200 && !RequestTokens::check($this->Page->hyphenized_slug())) {
			Response::http_status(400);
			Console::log("        BAD REQUEST! Token does not match!");
			$this->_request_is_bad = true;
			if (Request::is_ajax()) {
				$err_msg = "Error 400 - Bad Request. Sorry, but your request cannot be processed by the server.";
				if (Request::type() == "update") {
					// Just dump the message out into the page:
					$this->render($err_msg);
				} else {
					// Return a JSON object with the error message
					$this->render_json(array('message' => $err_msg));
				}
				Bootstrap::end_program();
			} else if (Request::is_ajaxy_iframe_post()) {
				$this->render_js('Biscuit.Crumbs.Alert("'.__("Error 400 - Bad Request.").'\n\n'.__("Sorry, but your request cannot be processed by the server.").'");');
				Bootstrap::end_program();
			} else {
				$this->Page = $this->page_factory->find_by("slug",'error400');
			}
		}
	}
	/**
	 * Parse the request URI. Matches the request against an array of patterns (in perl regex format) and when found replaces it with the associated
	 * replacement string. Biscuit provides all the common and known special rules, individual modules can add on to the list any special action rules of
	 * their own. This essentially does the job of htaccess rewrite rules and uses the same sort of syntax for the patterns and replacements
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function parse_request() {
		$request_uri = trim(Request::uri(),'/');
		if (empty($request_uri)) {
			return;
		}
		if (preg_match('/\?/',$request_uri)) {
			// Remove the query string, if present
			$request_uri = substr($request_uri,0,strpos($request_uri,'?'));
		}
		$request_uri = trim($request_uri,'/');
		$rewrite_rules = $this->rewrite_rules();
		$module_rewrite_rules = $this->get_module_rewrite_rules();
		$rewrite_rules = array_merge($rewrite_rules,$module_rewrite_rules);
		foreach ($rewrite_rules as $rewrite_rule) {
			$patterns[] = $rewrite_rule['pattern'];
			$replacements[] = $rewrite_rule['replacement'];
		}
		// Add the generic pattern/replacement:
		$patterns[] = '/^([^\.]+)$/';
		$replacements[]  = 'page_slug=$1';
		$real_query_string = '';
		foreach ($patterns as $index => $pattern) {
			if (preg_match($pattern,$request_uri)) {
				$real_query_string = preg_replace($pattern,$replacements[$index],$request_uri);
				break;
			}
		}
		if (!empty($real_query_string)) {
			parse_str($real_query_string,$this->_parsed_query);
			if (!empty($this->_parsed_query['page_slug'])) {
				$this->_request_slug = $this->_parsed_query['page_slug'];
				unset($this->_parsed_query['page_slug']);
			}
		} else {
			if (!empty($request_uri)) {
				$this->_request_slug = 'error404';
			}
		}
	}
	/**
	 * Process all user provided GET and POST variables that were left after processing the page-related variables. Store the raw data in one array, and the un-escaped data in another.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function set_user_input() {
		$raw_user_input = array();
		// Set the page slug from form input or query string if provided. These get to stomp over whatever may have been set when parsing the request URI
		// for cases where you want to override.
		if (Request::form('page_slug')) {
			$this->_request_slug = Request::form('page_slug');
		} else if (Request::query_string('page_slug')) {
			$this->_request_slug = Request::query_string('page_slug');
		}
		if (empty($this->_request_slug)) {
			Console::log("        No page provided in post or query string, using \"index\"");
			$this->_request_slug = 'index';
		}

		// Ensure that 'page' and the odd underscore param added bp Prototype Ajax.Updater() are cleared
		Request::clear_query(array('page_slug','_'));
		Request::clear_form(array('page_slug','_'));

		if (Session::var_exists('user_input')) {
			$raw_user_input = array_merge(Session::get('user_input'),$raw_user_input);
			Session::unset_var('user_input');
		}
		if (!empty($this->_parsed_query)) {
			$raw_user_input = array_merge($this->_parsed_query, $raw_user_input);
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
	 * Get the value of a user input var
	 *
	 * @param string $key 
	 * @return mixed
	 * @author Peter Epp
	 */
	public function user_input($key) {
		if (!isset($this->user_input[$key])) {
			return null;
		}
		return $this->user_input[$key];
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
	 * Return the array of JS files
	 *
	 * @return array
	 * @author Peter Epp
	 */
	public function js_files($position) {
		return $this->_js_files[$position];
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
	 * Return the array of CSS files
	 *
	 * @return array
	 * @author Peter Epp
	 */
	public function css_files() {
		return $this->_css_files;
	}
	/**
	 * Return the array of IE-specific CSS files
	 *
	 * @return array
	 * @author Peter Epp
	 */
	public function ie_css_files() {
		return $this->_ie_css_files;
	}
	/**
	 * Return the array of IE6-specific CSS files
	 *
	 * @return array
	 * @author Peter Epp
	 */
	public function ie6_css_files() {
		return $this->_ie6_css_files;
	}
	/**
	 * Register JS and CSS include files queued to render after module includes
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function register_queued_includes() {
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
		if (defined("USE_FRAMEWORK_JS") && USE_FRAMEWORK_JS == 1) {
			$this->_js_files['footer'][] = "/framework/js/framework.js";
			if (DEBUG) {
				$this->_js_files['footer'][] = "/framework/js/debug_enabler.js";
			}
			$this->_js_files['footer'][] = "/framework/js/common.js";
		}
		if (file_exists($this->Page->full_theme_path()."/js/common.js")) {
			$this->_js_files['footer'][] = $this->Page->theme_dir()."/js/common.js";
		}
		// Add any JS files found in the theme folder to the page footer, if not already registered:
		$theme_js_files = FindFiles::ls($this->Page->full_theme_path(true).'/js',array('excludes' => 'common.js', 'types' => 'js'));
		if (!empty($theme_js_files)) {
			foreach ($theme_js_files as $js_file) {
				$js_file_path = $this->Page->theme_dir().'/js/'.$js_file;
				if (!in_array($js_file_path, $this->_js_files['footer']) && !in_array($js_file_path, $this->_js_files['header'])) {
					$this->_js_files['footer'][] = $js_file_path;
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
		$this->_css_files[] = array(
			'filename' => $this->Page->theme_dir().'/css/styles_screen.css',
			'media' => 'screen, projection'
		);
		if (!file_exists($this->Page->full_theme_path().'/css/forms.css')) {
			// If a forms.css file doesn't exist in the theme's css folder (which will get included automatically), add the relative path to the default
			// Biscuit theme forms css file so it will use that instead:
			$this->_css_files[] = array(
				'filename' => 'themes/default/css/forms.css',
				'media' => 'screen'
			);
		}
		if (file_exists($this->Page->full_theme_path().'/css/styles_print.css')) {
			$this->_css_files[] = array(
				'filename' => $this->Page->theme_dir().'/css/styles_print.css',
				'media' => 'print'
			);
		}
		if (file_exists($this->Page->full_theme_path().'/css/ie.css')) {
			$this->_ie_css_files[] = array(
				'filename' => $this->Page->theme_dir().'/css/ie.css',
				'media' => 'print'
			);
		}
		// Add any additional CSS files found in the theme folder (for all media):
		$theme_css_files = FindFiles::ls($this->Page->full_theme_path(true).'/css',array('excludes' => array('styles_screen.css','styles_print.css','styles_tinymce.css','ie.css','ie6.css'),'types' => 'css'));
		if (!empty($theme_css_files)) {
			foreach ($theme_css_files as $css_file) {
				if (!preg_match('/\.src\.css/si',$css_file)) {
					$css_file_path = $this->Page->theme_dir().'/css/'.$css_file;
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
		if ($this->Page->theme_favicon_url()) {
			$this->register_header_tag('link',array(
				'rel' => 'shortcut icon',
				'href' => $this->Page->theme_favicon_url()
			));
		}
		JsAndCssCache::run();
		$action = $this->user_input('action');
		if (empty($action)) {
			$action = 'index';
		}
		$prefix = $this->Page->theme_name().'-'.$this->Page->hyphenized_slug().'-'.$action;
		$header_js_file  = '/js/cache/'.$prefix.'_header_scripts.js';
		$footer_js_file  = '/js/cache/'.$prefix.'_footer_scripts.js';
		$screen_css_file = array('media' => 'screen, projection', 'filename' => '/css/cache/'.$prefix.'_screen_styles.css');
		$print_css_file  = array('media' => 'print', 'filename' => '/css/cache/'.$prefix.'_print_styles.css');
		$header_tags =	$this->render_js_or_css_tag('js',$header_js_file) .
						$this->render_js_or_css_tag('css',$screen_css_file) .
						$this->render_js_or_css_tag('css',$print_css_file) .
						$this->render_ie_css() .
						$this->render_standalone_js_tags('header');
		$footer_tags =	$this->render_js_or_css_tag('js',$footer_js_file) .
						$this->render_standalone_js_tags('footer');
		Event::fire('build_extra_include_tags');
		$header_tags .= $this->build_extra_include_tags();
		$this->set_view_var('header_includes',$header_tags);
		$this->append_view_var('footer',$footer_tags);
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
		// IE stylesheet for any version of IE, if present
		$ie_css_file = Crumbs::file_exists_in_load_path('css/cache/'.$this->Page->theme_name().'-'.$this->Page->hyphenized_slug().'_ie.css', SITE_ROOT_RELATIVE);
		if ($ie_css_file) {
			$returnHtml .= '
	<!--[if IE]>';
			$ie_css_file = $this->add_js_css_version_number($ie_css_file);
			$returnHtml .= '
		<link href="'.$ie_css_file.'" rel="stylesheet" type="text/css" media="all" charset="utf-8">';
			$returnHtml .= '
	<![endif]-->';
		}
		$theme_ie6_css = Crumbs::file_exists_in_load_path($this->Page->theme_dir().'/css/ie6.css', SITE_ROOT_RELATIVE);
		// IE6-specific stylesheet, if present
		if ($theme_ie6_css) {
			$returnHtml .= '
	<!--[if lt IE 7]>
		<link href="'.$theme_ie6_css.'" rel="stylesheet" type="text/css" media="all" charset="utf-8">
	<![endif]-->';
		}
		// IE6 stylesheet, if present
		$ie6_css_file = Crumbs::file_exists_in_load_path('css/cache/'.$this->Page->theme_name().'-'.$this->Page->hyphenized_slug().'_ie6.css', SITE_ROOT_RELATIVE);
		if ($ie6_css_file) {
			$returnHtml .= '
	<!--[if lt IE 7]>';
			$ie6_css_file = $this->add_js_css_version_number($ie6_css_file);
			$returnHtml .= '
		<link href="'.$ie6_css_file.'" rel="stylesheet" type="text/css" media="all" charset="utf-8">';
			$returnHtml .= '
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
		if (defined("JS_AND_CSS_VERSION")) {
			if (substr($filename,-2) == "js") {
				$ext_length = 3;
			} else if (substr($filename,-3) == "css") {
				$ext_length = 4;
			}
			$src_ext = substr($filename,-$ext_length);
			$src_base = substr($filename,0,-$ext_length);
			$filename = $src_base."_v".JS_AND_CSS_VERSION.$src_ext;
			return $filename;
		}
		return $filename;
	}
	/**
	 * Render the web page. Static content passed as an argument takes first precedence, modules that request rendering take second, and the page itself takes third.
	 *
	 * @param string $content Optional - static HTML content to render instead of a template or view file
	 * @return void
	 * @author Peter Epp
	 */
	public function render($content = "") {
		if (!$this->page_rendered && Request::type() != 'server_action') {
			Console::log("Rendering:");
			if ($this->browser_cache_allowed()) {
				$last_modified = gmdate(GMT_FORMAT, ((!empty($content)) ? time() : $this->latest_update_timestamp()));
				if (empty($content) && !$this->modified_since_last_request() && Request::method() != 'HEAD') {
					Response::http_status(304);
				}
			} else {
				$last_modified = gmdate(GMT_FORMAT, time());
				$expires = gmdate(GMT_FORMAT,(time()-((60*60*24)*365)*10));
				Response::add_header('Expires',$expires);
				Response::add_header('Cache-Control','no-cache, no-store, must-revalidate, post-check=0, pre-check=0');
				Response::add_header('Pragma','no-cache');
			}
			Response::add_header("Last-Modified",$last_modified);
			if (!empty($content)) {
				Console::log("    Content source: Static");
				Console::log("    Content to render:\n".$content);
			} else {
				if (!$this->modified_since_last_request()) {
					Console::log("    Page not modified since the last request. Skipping render.");
				} else {
					Console::log("    Content source: View file(s)");
					// Store a reference to this (the web page object) in a nice variable for the view file:

					try {
						$content = $this->compile_page();
					} catch (CoreException $e) {
						trigger_error("Unable to render content: ".$e->getMessage(),E_USER_ERROR);
					}
				}
			}
			Response::add_header('ETag',sha1($content.$last_modified));
			Response::send_headers($this->Page->access_level());
			if (!empty($content)) {
				print $content;
			}
			$this->page_rendered = true;
		}
	}
	/**
	 * Compile all appropriate view files and return the rendered content
	 *
	 * @return string
	 * @author Peter Epp
	 */
	protected function compile_page() {
		if ($this->page_cache_is_valid()) {
			Console::log("    Using cached page content");
			return $this->Page->cached_content();
		} else {
			Console::log("    Page cache invalid, compiling content");
			if ($this->render_with_template()) {
				$this->set_view_var('body_id','page-'.$this->Page->hyphenized_slug());
				Console::log("    Rendering with template");
				$template_codefile = $this->Page->full_theme_path().'/template.php';
				if (file_exists($template_codefile)) {
					Console::log("    Including template code file");
					$Biscuit = $this;
					require_once($template_codefile);
				}
			}
			if ($this->render_with_template() && !Request::is_ajax()) {
				Console::log("    Compiling footer");
				// Trigger any modules or extensions to compile content, if any, for the footer
				Event::fire("compile_footer");
				$this->append_view_var('footer',Crumbs::server_info_bar());
				// Set HTML headers
				$this->set_header_tags();
				// Set JS and CSS HTML includes
				$this->set_js_and_css_includes();
			}
			$view_vars = array();
			if (empty($this->_view_vars['page_title'])) {
				$view_vars['page_title'] = $this->Page->title();
			}
			$view_vars['Biscuit'] = $this;
			$view_vars['Page'] = $this->Page;

			$view_vars['user_messages'] = '';
			if ($this->render_with_template()) {
				if (Session::flash_get('user_success')) {
					$view_vars["user_messages"] .= '<div class="success">'.Session::flash_html_dump('user_success').'</div>';
				}
				if (Session::flash_get('user_message')) {
					$view_vars["user_messages"] .= '<div class="notice">'.Session::flash_html_dump('user_message').'</div>';
				}
				if (Session::flash_get('user_error')) {
					$view_vars["user_messages"] .= '<div class="error">'.Session::flash_html_dump('user_error').'</div>';
				}
			}

			foreach ($this->extensions as $extension_name => $extension) {
				if (is_object($extension)) {
					$view_vars[$extension_name] = $this->extensions[$extension_name];
				}
			}

			foreach ($this->modules as $module_name => $module) {
				$module_classname = Crumbs::module_classname($module_name);
				if (substr($module_classname,0,6) == 'Custom') {
					$var_name = substr($module_classname,6);
				} else {
					$var_name = $module_classname;
				}
				$view_vars[$var_name] = $this->modules[$module_name];
			}

			$view_vars = array_merge($this->_view_vars, $view_vars);

			if (!$this->Page->using_default_view() || empty($this->_module_viewfiles)) {
				Console::log("    Rendering main view file: ".$this->Page->view_file());
				$page_content = Crumbs::capture_include($this->Page->view_file(), $view_vars);
			}
			else {
				Console::log("    Rendering module view files only");
				$page_content = "";
			}
			$page_content = $this->render_module_views($page_content, $view_vars);
			if ($this->render_with_template()) {
				$template_file = $this->select_template();
				Console::log("    Rendering template: ".$template_file);
				$view_vars['page_content'] = $page_content;
				$this->_compiled_content = Crumbs::capture_include($template_file, $view_vars);
			}
			else {
				Console::log("    Rendering content without template");
				$this->_compiled_content = $page_content;
			}
			Event::fire('content_compiled');
			// Eliminate all whitespace between HTML tags:
			if (Response::is_html()) {
				$this->_compiled_content = MinifyHtml::minify($this->_compiled_content);
			}
			if ($this->page_cache_allowed()) {
				$this->Page->cache_write($this->_compiled_content);
				Event::fire('page_cached');
			}
			return $this->_compiled_content;
		}
	}
	/**
	 * Function to allow someone else to override or replace the compiled page content. It should be used by modules that act on the content_compiled
	 * event to post-process content, if desired.
	 *
	 * @param string $compiled_content 
	 * @return void
	 * @author Peter Epp
	 */
	public function set_compiled_content($compiled_content) {
		$this->_compiled_content = $compiled_content;
	}
	/**
	 * Return the current compiled content. For use by others that need to grab a copy of the content as it is at any given point, for
	 * example modules that act on the "content_compiled" event and need to post-process it.
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function get_compiled_content() {
		return $this->_compiled_content;
	}
	/**
	 * Select a template for rendering. First checks for a template provided by the module for it's current action and if not present asks the Page
	 * model to provide the template defined for the current page.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function select_template() {
		$template = $this->select_primary_module_template();
		if (empty($template)) {
			$template = $this->Page->select_template();
		}
		return $template;
	}
	/**
	 * Set or check whether or not to render the page with template
	 *
	 * @param bool $set_value Optional - provide this argument to set whether or not to render with template
	 * @return bool Wether or not to render with template
	 * @author Peter Epp
	 */
	public function render_with_template($set_value = null) {
		if (is_bool($set_value)) {
			$this->_use_template = $set_value;
		}
		return $this->_use_template;
	}
    /**
     * Render Javascript code wrapped in a script tag
     * 
     * @param string $javascript the JS to render back to the user
     **/
    public function render_js($javascript) {
		if (Request::is_ajax()) {
			$output = $javascript;
			Response::content_type("text/javascript");
		}
		else {
			$output = '<script type="text/javascript" language="javascript" charset="utf-8">'.$javascript."</script>";
		}
		$this->render($output);
    }

	/**
	 * Render as json object literal.
	 * @param array $values An indexed array of values to be converted to json
	 * @return void
	 * @author Peter Epp
	**/
	public function render_json($values) {
		Response::content_type("application/json");
		Response::add_header("X-JSON","true");
		$this->render(Crumbs::to_json($values));
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
		if ($this->Page->hidden()) {
			$robots_tag_content = 'noindex,nofollow';
		}
		else {
			$robots_tag_content = 'index,follow';
		}
		$this->register_header_tag('meta',array(
			'name' => 'robots',
			'content' => $robots_tag_content
		));
		if ($this->Page->keywords() != "") {
			$this->register_header_tag('meta',array(
				'name' => 'keywords',
				'content' => htmlentities($this->Page->keywords())
			));
		}
		if ($this->Page->description()) {
			$this->register_header_tag('meta',array(
				'name' => 'description',
				'content' => htmlentities($this->Page->description())
			));
		}
		// Add Open Graph tags:
		if ($this->Page->slug() == 'index') {
			$page_title = HOME_TITLE;
		} else {
			$page_title = $this->Page->title();
		}
		$this->register_header_tag('meta',array(
			'property' => 'og:title',
			'content' => $page_title
		));
		$this->register_header_tag('meta',array(
			'property' => 'og:site_name',
			'content' => SITE_TITLE
		));
		$site_image = $this->site_image_url();
		if (!empty($site_image)) {
			$this->register_header_tag('meta',array(
				'property' => 'og:image',
				'content' => $site_image
			));
		}
		Event::fire('build_header_tags');
		$head_tags = $this->build_meta_tags();
		$head_tags .= '
	<title>'.$this->Page->full_title().'</title>';
		$this->set_view_var("header_tags",$head_tags);
	}
	/**
	 * Find site image in theme, if present, and return it's fully qualified URL
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function site_image_url() {
		$site_image = null;
		$image_base_path = $this->Page->full_theme_path(true).'/images/site-image';
		if (file_exists(SITE_ROOT.$image_base_path.'.jpg')) {
			$site_image = $image_base_path.'.jpg';
		} else if (file_exists(SITE_ROOT.$image_base_path.'.gif')) {
			$site_image = $image_base_path.'.gif';
		} else if (file_exists(SITE_ROOT.$image_base_path.'.png')) {
			$site_image = $image_base_path.'.png';
		}
		return STANDARD_URL.$site_image;
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
	 * Set a variable to be local to the view at render time, replacing the value if it already exists
	 *
	 * @param string $key Name of the variable
	 * @param string $value Value of the variable
	 * @return mixed Value of the variable
	 * @author Peter Epp
	 * @author Lee O'Mara
	 */
	public function set_view_var($key,$value) {
		$this->_view_vars[$key] = $value;
		return $value;
	}
	/**
	 * Update an existing view var with an additional value, setting it if it doesn't exist. Takes into account different data types.
	 *
	 * @param string $key Name of the variable
	 * @param string $value Value of the variable
	 * @return mixed Value of the variable
	 * @author Peter Epp
	 */
	public function append_view_var($key,$value) {
		if (!isset($this->_view_vars[$key])) {
			$this->_view_vars[$key] = $value;
		} else {
			$this->_view_vars[$key] .= $value;
		}
	}
	/**
	 * Set the body title
	 *
	 * @param string $title The body title
	 * @return void
	 **/
	public function set_title($title) {
		$this->Page->set_title($title);
	}
	/**
	 * Whether or not the request is bad
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public function request_is_bad() {
		return $this->_request_is_bad;
	}
	/**
	 * Get a list of all directories needed by modules for writing and ensure that they are created. Throw an exception if any directories cannot be created.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function module_and_extension_directory_setup() {
		Event::fire("directory_setup");
		$failed_directories = array();
		foreach ($this->_module_and_extension_directory_list as $directory) {
			if (!Crumbs::ensure_directory($directory)) {
				$failed_directories[] = $directory;
			}
		}
		if (!empty($failed_directories)) {
			throw new ModuleException("Cannot create all directories needed by modules for the requested page. Please either create the following directories manually, or enable appropriate write permissions on their parent directories:\n\n-".implode("\n-",$failed_directories));
		}
	}
	/**
	 * Add a directory to the list
	 *
	 * @param string $directory 
	 * @return void
	 * @author Peter Epp
	 */
	public function add_to_directory_list($directory) {
		$this->_module_and_extension_directory_list[] = $directory;
	}
	/**
	 * Return all the common and known special rewrite rules used by the framework and it's core modules.
	 *
	 * @return array
	 * @author Peter Epp
	 */
	public function rewrite_rules() {
		$patterns = array(
			'/^ping\/([0-9]+)$/',
			'/^captcha\/([0-9]+)$/',
			'/^reset-password$/',
			'/^([^\.]+)\/show_([a-z_]+)\/([0-9]+)\/(.+)$/',
			'/^([^\.]+)\/show\/([0-9]+)\/(.+)$/',
			'/^([^\.]+)\/(show|new|edit|delete)_([a-z_]+)\/([0-9]+)$/',
			'/^([^\.]+)\/(new|index|resort)_([a-z_]+)$/',
			'/^([^\.]+)\/(show|new|edit|delete)\/([0-9]+)$/',
			'/^([^\.]+)\/(new|resort)$/'
		);
		$replacements = array(
			'page_slug=index&ping_time=$1',
			'page_slug=captcha',
			'page_slug=reset-password&action=reset_password',
			'page_slug=$1&action=show_$2&id=$3',
			'page_slug=$1&action=show&id=$2',
			'page_slug=$1&action=$2_$3&id=$4',
			'page_slug=$1&action=$2_$3',
			'page_slug=$1&action=$2&id=$3',
			'page_slug=$1&action=$2'
		);
		$rewrite_rules = array();
		foreach ($patterns as $index => $pattern) {
			$rewrite_rules[] = array(
				'pattern' => $pattern,
				'replacement' => $replacements[$index]
			);
		}
		return $rewrite_rules;
	}
}	// END Biscuit class
?>
