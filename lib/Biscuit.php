<?php
/**
 * The little engine that could
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.1
 **/
// TODO Add a mechanism for handling certain file types, like images, in a special when an error 404 occurs.  For example send a "no_image.gif".
class Biscuit extends ModuleCore implements Singleton {
	/**
	 * Biscuit version number
	 *
	 * @var float
	 */
	protected static $_version = 2.1;
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
	 * Reference to Theme object
	 *
	 * @var Theme
	 */
	public $Theme;
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

			$this->Theme = new Theme();

			// Start the fragment cache invalidation observer
			new FragmentCacheInvalidator();

			// Setup page factory
			$this->page_factory = new ModelFactory("Page");

			// Initialize global extensions
			$this->init_global_extensions();

			$this->load_shared_models();

			I18n::instance()->load_translations();

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

			$this->request_token_check();

		} catch (CoreException $e) {
			trigger_error("Core Exception: ".$e->getMessage(),E_USER_ERROR);
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
				$this->Theme->register_queued_includes();
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
		if (!RequestTokens::check($this->Page->hyphenized_slug())) {
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
		Console::log("Parsing request URI");
		$request_uri = trim(I18n::instance()->request_uri_without_locale(),'/');
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
	 * Register a JS file in the theme. This method exists for backwards compatibility
	 *
	 * @return void
	 * @see Theme::register_js()
	 * @param string $js_file The name of the file relative to the "scripts" folder
	 * @param string $queue_for_later Optional - whether or not to add the file to the end of the list. This queues it to be registered after all module JS files have been registered
	 * @author Peter Epp
	 **/
	public function register_js($position,$filename,$stand_alone = false,$queue_for_end = false) {
		$this->Theme->register_js($position,$filename,$stand_alone = false,$queue_for_end = false);
	}
	/**
	 * Add a CSS file to the list of ones to include in the page. This method exists for backwards compatibility
	 *
	 * @see Theme::register_css()
	 * @return void
	 * @param string $css_file An associative array containing 'filename' and 'media' values, where media is the CSS media type (ie. "screen" or "print"). The filename must be relative to the "css" folder.
	 * @param string $queue_for_later Optional - whether or not to add the file to the end of the list. This queues it to be registered after all module CSS files have been registered
	 * @author Peter Epp
	 **/
	public function register_css($file,$queue_for_later = false) {
		$this->Theme->register_css($file,$queue_for_later);
	}
	/**
	 * Register an IE-specific CSS file. This method exists for backwards compatibility
	 *
	 * @see Theme::register_ie_css()
	 * @param string $file 
	 * @return void
	 * @author Peter Epp
	 */
	public function register_ie_css($file) {
		$this->Theme->register_ie_css($file);
	}
	/**
	 * Register an IE6-specific CSS file. This method exists for backwards compatibility
	 *
	 * @see Theme::register_ie6_css()
	 * @param string $file 
	 * @return void
	 * @author Peter Epp
	 */
	public function register_ie6_css($file) {
		$this->Theme->register_ie6_css($file);
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
				$this->Theme->set_header_tags();
				// Set JS and CSS HTML includes
				$this->Theme->set_js_and_css_includes();
			}
			// Set Language to use for Javascript:
			$locale = I18n::instance()->locale();
			$lang_setting_js = <<<HTML
<script type="text/javascript" charset="utf-8">
	Biscuit.Language = "$locale";
</script>
HTML;
			$this->append_view_var('footer',$lang_setting_js);
			$view_vars = array();
			$view_vars['lang']   = I18n::instance()->html_lang();
			$view_vars['locale'] = I18n::instance()->locale();
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
				$template_file = $this->Theme->select_template();
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
			if (defined('MINIFY_OUTPUT') && MINIFY_OUTPUT == true && Response::is_html()) {
				$this->_compiled_content = MinifyHtml::minify($this->_compiled_content);
			}
			if ($this->page_cache_allowed() && Crumbs::ensure_directory(SITE_ROOT."/page_cache")) {
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
			$output = '<script type="text/javascript" language="javascript" charset="utf-8">'.$javascript.'</script>';
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
	 * Find site image in theme, if present, and return it's fully qualified URL. This method exists for backwards compatibility.
	 *
	 * @see Theme::site_image_url()
	 * @return void
	 * @author Peter Epp
	 */
	public function site_image_url() {
		return $this->Theme->site_image_url();
	}
	/**
	 * Register the HTML code for an extra header tag. This method exists for backwards compatibility.
	 *
	 * @see Theme::register_header_tag()
	 * @param string $tag_html 
	 * @return void
	 * @author Peter Epp
	 */
	public function register_header_tag($tag_type,$tag_attributes,$tag_content = '') {
		$this->Theme->register_header_tag($tag_type,$tag_attributes,$tag_content);
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
		if ($this->Page->has_navigation_label()) {
			$this->Page->set_navigation_label($title);
		}
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
	/**
	 * Return version number
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function version() {
		return self::$_version;
	}
}	// END Biscuit class
