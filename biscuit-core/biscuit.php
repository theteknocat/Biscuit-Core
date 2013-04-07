<?php
/**
 * The little engine that could
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.2 $Id: biscuit.php 14795 2013-03-27 18:31:54Z teknocat $
 **/
// TODO Add a mechanism for handling certain file types, like images, in a special when an error 404 occurs.  For example send a "no_image.gif".
class Biscuit extends ModuleCore implements Singleton {
	/**
	 * Biscuit major version number
	 *
	 * @var float
	 */
	protected static $_version_major = 2.2;
	/**
	 * Biscuit minor version number
	 *
	 * @author Peter Epp
	 */
	protected static $_version_minor = 10;
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
	 * Reference to fragment cache invalidator object
	 *
	 * @var object
	 */
	public $FragmentCacheInvalidator;
	/**
	 * Reference to the variable cache object
	 * @var object
	 */
	public $VariableCache;
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
		Event::add_observer($this);

		// Initialize the theme object
		$this->Theme = new Theme();

		// Start the fragment cache invalidator
		$this->FragmentCacheInvalidator = new FragmentCacheInvalidator();

		$this->VariableCache = new VariableCache();

		// Setup page factory
		$this->page_factory = ModelFactory::instance("Page");
	}
	/**
	 * Initialize Biscuit, including all modules and extensions
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function init() {
		// Initialize global extensions
		$this->init_global_extensions();

		$this->load_shared_models();

		I18n::instance()->load_translations();

		Request::map_uri($this->get_module_uri_mapping_rules());

		// Fire an event as a hook for any extensions that want to do something prior to page initialization. This allows an extension to take over page initialization if desired
		Event::fire('biscuit_initialization',Request::slug());

		if (empty($this->Page)) {	// Only initialize the page now if it wasn't already initialized, in case an extension already took care of it
			$this->initialize_page();
		} else {	// If the page was already initialized, validate the page request
			$this->validate_page_request();
		}

		$this->init_page_modules();

		$this->request_token_check();

		$this->Theme->initialize();
	}
	/**
	 * Run extension or module install or uninstall operation if requested
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function run_install_or_uninstall_ops() {
		if (Request::query_string('install_extension')) {
			$this->install_extension(Request::query_string('install_extension'));
		} else if (Request::query_string('uninstall_extension')) {
			$this->uninstall_extension(Request::query_string('uninstall_extension'));
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
		$this->init();
		$page_found = true;
		try {
			if (Request::slug() == 'cron') {
				$this->set_never_cache(); // Never cache cron page requests
				Console::log("Running cron jobs");
				Cron::add_message($this,"Cron dispatching");
				Event::fire('cron_run');
				$complete_msg = "Cron run complete";
				$message_count = Cron::message_count()-1;
				if ($message_count > 0) {
					$complete_msg .= ", ".$message_count." task".(($message_count != 1) ? 's were' : ' was')." performed";
				} else {
					$complete_msg .= ", no tasks were performed";
				}
				Cron::add_message($this,$complete_msg);
				$output = Cron::messages()."\n";
				Response::content_type('text/plain; charset=utf8');
				$this->render($output);
				Bootstrap::end_program(true);
			}

			$this->run_install_or_uninstall_ops();

			Event::fire("dispatch_request");

			Console::log("\nDispatch Request:");
			$has_post_data = "No";
			if (Request::is_post() && Request::dirty_user_input()) {
				$has_post_data = "Yes";
				if (DEBUG) {
					Console::log_var_dump('Request post data',Request::dirty_user_input());
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
		} catch (RecordNotFoundException $e) {
			// This exception is thrown by a module when a requested DB record cannot be found by the provided ID. Abstract controller throws it on edit, show
			// and delete actions automatically. Remember to throw the exception at the appropriate time in your module in any custom action methods.
			$page_found = false;
		} catch (ViewNotFoundException $e) {
			// This exception gets thrown by the module core when trying to render the requested view file for the module. It ends up in the same result as the above
			// Exception but it's named differently for semantic purposes
			$page_found = false;
		} catch (ActionNotFoundException $e) {
			// This is thrown when an action method cannot be found
			Console::log($e->getMessage());
			$page_found = false;
		}
		if (!$page_found) {
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

		Console::log("        Fetching page: ".Request::slug());
		$this->Page = $this->page_factory->find_by("slug",Request::slug());
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
		$request_slug = Request::slug();
		if (!$this->Page) {
			// See if an alias exists for the requested page
			$alias = ModelFactory::instance('PageAlias')->find_by('old_slug',$request_slug);
			if (empty($alias)) {
				Response::http_status(404);
				$this->Page = $this->page_factory->find_by("slug",'error404');
			} else {
				$base_request_uri = '/'.$request_slug;
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
		} else if (preg_match('/(error[0-9]{3})/',$request_slug)) {
			$http_status = (int)substr($request_slug,-3);
			Response::http_status($http_status);
		} else if ($this->Page->ext_link()) {
			// If the page has an ext_link set, redirect to that:
			Response::redirect($this->Page->ext_link());
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
				$err_msg = __("<p>The form you submitted has expired and can no longer be processed. This is part of a security feature designed to prevent other sites from hacking your site while you are logged in. I apologize for the inconvenience.</p>");
				if (Request::type() == "update") {
					// Just dump the message out into the page:
					$this->render($err_msg);
				} else {
					// Return a JSON object with the error message
					$this->render_json(array('message' => $err_msg));
				}
				Bootstrap::end_program();
			} else if (Request::is_ajaxy_iframe_post()) {
				$this->render_js('Biscuit.Crumbs.Alert("'.__("<p>The form you submitted has expired and can no longer be processed. This is part of a security feature designed to prevent other sites from hacking your site while you are logged in. I apologize for the inconvenience.</p>").'");');
				Bootstrap::end_program();
			} else {
				$this->Page = $this->page_factory->find_by("slug",'error400');
			}
		}
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
		$this->Theme->register_js($position,$filename,$stand_alone,$queue_for_end);
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
	 * Register an IE-specific CSS file. This method exists for backwards compatibility only
	 *
	 * @see Theme::register_ie_css()
	 * @param string $file 
	 * @return void
	 * @author Peter Epp
	 */
	public function register_ie_css($css_info, $ie_version = 'all') {
		$this->Theme->register_ie_css($css_info, $ie_version);
	}
	/**
	 * Register an IE6-specific CSS file. This method exists for backwards compatibility only
	 *
	 * @see Theme::register_ie6_css()
	 * @param string $file 
	 * @return void
	 * @author Peter Epp
	 */
	public function register_ie6_css($css_info) {
		$this->Theme->register_ie_css($css_info, 6);
	}
	/**
	 * Render the web page. Static content passed as an argument takes first precedence, modules that request rendering take second, and the page itself takes third.
	 *
	 * @param string $content Optional - static HTML content to render instead of a template or view file
	 * @return void
	 * @author Peter Epp
	 */
	public function render($content = "") {
		if (!$this->page_rendered) {
			if (Request::is_fire_and_forget()) {
				// Output something to indicate request completion and cause headers to be sent
				$content = 'Request completed';
			}
			Console::log("Rendering:");
			if ($this->browser_cache_allowed()) {
				$last_modified = gmdate(GMT_FORMAT, ((!empty($content)) ? time() : $this->latest_update_timestamp()));
				if (empty($content) && !$this->modified_since_last_request() && Request::method() != 'HEAD') {
					if (Request::type() == 'json') {
						// Make sure we send the right response headers for JSON, when applicable
						Response::content_type("application/json");
						Response::add_header("X-JSON","true");
					}
					Response::http_status(304);
				}
			} else {
				$last_modified = gmdate(GMT_FORMAT, time());
				$expires = gmdate(GMT_FORMAT,(time()-((60*60*24)*365)*10));
				Response::add_header('Expires',$expires);
				Response::add_header('Cache-Control','no-cache, no-store, must-revalidate, proxy-revalidate, max-age=0, post-check=0, pre-check=0');
			}
			Response::add_header("Last-Modified",$last_modified);
			if (!empty($content)) {
				Console::log("    Content source: Static");
				if ($this->page_cache_allowed() && Crumbs::ensure_directory(SITE_ROOT."/var/cache/pages")) {
					$this->cache_write($content);
					Event::fire('page_cached');
				}
			} else {
				if (!$this->modified_since_last_request()) {
					Console::log("    Page not modified since the last request. Skipping render.");
				} else {
					Console::log("    Content source: View file(s)");
					$content = $this->compile_page();
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
			// Send a header that indicates the page was loaded from cache. Handy for troubleshooting
			Response::add_header('X-Biscuit-Cache', 'HIT');
			if (Request::is_json()) {
				// Make sure we send the right response headers for JSON, when applicable
				Response::content_type("application/json");
				Response::add_header("X-JSON","true");
			}
			return $this->cached_content();
		} else {
			Console::log("    Page cache invalid, compiling content");
			if ($this->render_with_template()) {
				$this->set_view_var('body_id','page-'.$this->Page->hyphenized_slug());
				Console::log("    Rendering with template");
				$template_codefile = $this->Theme->full_theme_path().'/template.php';
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
<script type="text/javascript">
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
			$this->_compiled_content = $this->render_module_views($page_content, $view_vars);
			Event::fire('page_body_content_compiled');
			if ($this->render_with_template()) {
				$template_file = $this->Theme->select_template();
				Console::log("    Rendering template: ".$template_file);
				$view_vars['page_content'] = $this->_compiled_content;
				$this->_compiled_content = Crumbs::capture_include($template_file, $view_vars);
			}
			Event::fire('content_compiled');
			// Eliminate all whitespace between HTML tags:
			if (defined('MINIFY_OUTPUT') && MINIFY_OUTPUT == true && Response::is_html()) {
				$this->_compiled_content = MinifyHtml::minify($this->_compiled_content);
			}
			if ($this->page_cache_allowed() && Crumbs::ensure_directory(SITE_ROOT."/var/cache/pages")) {
				$this->cache_write($this->_compiled_content);
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
		if (!Request::is_json()) {
			$this->_view_vars[$key] = $value;
			return $value;
		}
		return null;
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
		if (!Request::is_json()) {
			if (!isset($this->_view_vars[$key])) {
				$this->_view_vars[$key] = $value;
			} else {
				$this->_view_vars[$key] .= $value;
			}
			return $this->_view_vars[$key];
		}
		return null;
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
	 * Return version number
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function version() {
		return self::$_version_major.'.'.self::$_version_minor;
	}
}	// END Biscuit class
