<?php
/**
 * Model the page_index table
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 **/
class Page extends AbstractModel {
	/**
	 * Path and file of the view used for the current page
	 *
	 * @var string
	 */
	protected $_view_file;
	/**
	 * Whether or not the default view file is being used to render the page content
	 *
	 * @var bool
	 */
	protected $_using_default_view_file = false;
	/**
	 * Place to cache the name of the primary module to reduce DB queries
	 *
	 * @var string
	 */
	private $_primary_module;
	/**
	 * Return the name of the access level for the current page
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function access_level_name() {
		$access_levels = Biscuit::instance()->ModuleAuthenticator()->access_levels();
		foreach ($access_levels as $access_level) {
			if ($access_level->id() == $this->access_level()) {
				return $access_level->name();
				break;
			}
		}
		return 'Public';
	}
	/**
	 * Whether or not the current user can access the current page
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public function user_can_access() {
		return ($this->access_level() == PUBLIC_USER || (Biscuit::instance()->ModuleAuthenticator()->user_is_logged_in() && Biscuit::instance()->ModuleAuthenticator()->active_user()->user_level() >= $this->access_level()));
	}
	/**
	 * Fetch all the data for all the modules associated with the current page
	 *
	 * @return array Indexed array of associative arrays of page data
	 * @author Peter Epp
	 **/
	public function find_modules() {
		$page_slug = $this->slug();
		$query =	"SELECT m.*, mp.`is_primary`
					 FROM `modules` m
					 LEFT JOIN `module_pages` mp ON (m.`id` = mp.`module_id`)
					 WHERE m.`installed` = 1
					 AND (mp.`page_name` = ?
					 OR (mp.`page_name` = '*' AND mp.`module_id` NOT IN (
						 SELECT m.`id`
						 FROM `modules` m
						 LEFT JOIN `module_pages` mp ON (m.`id` = mp.`module_id`)
						 WHERE m.`installed` = 1 AND mp.`page_name` = ?
					 )))
					 ORDER BY m.`sort_order`";
		return DB::fetch($query,array($page_slug,$page_slug));
	}
	/**
	 * Return the name of the primary module for the current page:
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function primary_module_name() {
		if (empty($this->_primary_module)) {
			$page_slug = $this->slug();
			$query = 	"SELECT `m`.`name`
						FROM `modules` `m`
						LEFT JOIN `module_pages` `mp` ON (`m`.`id` = `mp`.`module_id`)
						WHERE `mp`.`page_name` = ? AND `mp`.`is_primary` = 1";
			$this->_primary_module = DB::fetch_one($query,$page_slug);
		}
		return $this->_primary_module;
	}
	/**
	 * Return the last part of the slug
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function short_slug() {
		$slug_bits = explode("/",$this->slug());
		return end($slug_bits); // eg "links"
	}
	/**
	 * Return a specified segment of the page slug
	 *
	 * @param string $segment_num The number of the segment to return. If invalid, method returns blank string
	 * @return void
	 * @author Peter Epp
	 */
	public function slug_segment($segment_num) {
		$slug_bits = explode('/',$this->slug());
		if (!is_int($segment_num) || $segment_num < 1 || $segment_num > count($slug_bits)) {
			return '';
		}
		return $slug_bits[$segment_num-1];
	}
	/**
	 * Always validate the page slug since it is generated automatically from the title on save and never needs to be provided by the user.
	 *
	 * @return bool Always true
	 * @author Peter Epp
	 */
	public function slug_is_valid() {
		return true;
	}
	/**
	 * Validate the page title by checking that it will not result in an empty friendly slug
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public function title_is_valid() {
		$slug = $this->friendly_slug();
		if (!$this->title() || empty($slug)) {
			$this->set_error('title','Enter a title of at least one word, 1 or more characters long containing one or more letters or numbers that are not common short words/prepositions.');
			return false;
		}
		return true;
	}
	/**
	 * Return a slug with the slashes turned to hyphens
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function hyphenized_slug() {
		return str_replace("/","-",$this->slug());
	}
	/**
	 * Fetch the full title of the current page starting with it's top-level parent
	 *
	 * @param int $page_id The id of the page whose title you want to fetch
	 * @param string $separator Optional - the string to separate the titles with. Defaults to ":"
	 * @return void
	 * @author Peter Epp
	 */
	public function full_title($separator = null) {
		if ($this->slug() == "index") {
			$title_string = HOME_TITLE;
		}
		else {
			if ($separator !== null) {
				// Take supplied argument if present
				$separator = ' '.$separator.' ';
			} else if (defined('BROWSER_TITLE_SEPARATOR')) {
				// Otherwise separator defined in system settings, if present:
				$separator = ' '.BROWSER_TITLE_SEPARATOR.' ';
			} else {
				// Otherwise default:
				$separator = ' :: ';
			}
			$title_string = $this->title().$separator.SITE_TITLE;
		}
		return $title_string;
	}
	/**
	 * Return the fully qualified URL for the page
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function url($with_logout = false) {
		if ($this->ext_link()) {
			return $this->ext_link();
		} else {
			if ($this->slug() == 'index') {
				$slug = '/';
			} else {
				$slug = '/'.$this->slug();
			}
			$page_url = $slug;
			if ($with_logout) {
				if ($this->slug() == 'index') {
					$page_url .= 'logout';
				} else {
					$page_url .= '/logout';
				}
			}
			return $page_url;
		}
	}
	/**
	 * Return the page's normal content view file
	 * 
	 * @return string
	 **/
	public function view_file() {
		if (empty($this->_view_file)) {
			$base = "views/".$this->slug();

			// Make a list of all the files we want to look for, in order of preference:
			$view_files = array(
				'html_file' => array(
					'relative'  => $base.'.html',
					'full_path' => Crumbs::file_exists_in_load_path($base.".html")
				),
				'php_file' => array(
					'relative'  => $base.'.php',
					'full_path' => Crumbs::file_exists_in_load_path($base.".php")
				),
				'txt_file' => array(
					'relative'  => $base.'.txt',
					'full_path' => Crumbs::file_exists_in_load_path($base.".txt")
				)
			);

			// Look for the first one that's found in the site root:
			foreach ($view_files as $type => $view_file) {
				// We check that it's NOT in the FW_ROOT, since the path will always contain the SITE_ROOT
				if ($view_file['full_path'] && !stristr($view_file['full_path'],FW_ROOT)) {
					$use_view_file = $view_file['relative'];
					break;
				}
			}
			if (empty($use_view_file)) {
				// None found in site root, check in framework root:
				foreach ($view_files as $type => $view_file) {
					if ($view_file['full_path'] && stristr($view_file['full_path'],FW_ROOT)) {
						$use_view_file = $view_file['relative'];
						break;
					}
				}
			}
			if (empty($use_view_file)) {
				$use_view_file = 'views/default.html';
			}
			$this->_view_file = $use_view_file;
		}
		return $this->_view_file;
	}
	/**
	 * Whether or not the page is using the default view file
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public function using_default_view() {
		return ($this->view_file() == 'views/default.html');
	}
	/**
	 * Shortcut to return a logout URL
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function logout_url() {
		return $this->url(true);
	}
	/**
	 * Return the name of the theme to use for the page
	 *
	 * @return string The computer-readable name of the theme
	 * @author Peter Epp
	 */
	public function theme_name()	{
		if (Request::query_string('theme_name')) {
			// If a theme name is provided in the query string it supercedes everything else, so use it to over-ride the page's defined theme:
			$this->set_theme_name(Request::query_string('theme_name'));
			Request::clear_query('theme_name');		// We won't need this any more
		}
		$theme_name = $this->_get_attribute('theme_name');
		if (!$theme_name) {
			$theme_name = "default";
			$this->set_theme_name($theme_name);
		}
		if (!Request::query_string('theme_name')) {
			// If no theme name was supplied in the query string, fire an event to allow other modules or extensions to over-ride the theme if desired.
			// We pass the event handler the theme name in addition to the object because if the observer calls this method again in order to find the
			// normal theme name it'll go into an infinite loop.
			Event::fire('get_theme_name',$this,$theme_name);
		}
		return $this->_get_attribute('theme_name');
	}
	/**
	 * Over-ride the page's theme. This is the method to call from another module or extension acting on the get_theme_name event in order to over-ride
	 * the page's regular theme
	 *
	 * @param string $theme_name Name of the theme you want to use
	 * @return void
	 * @author Peter Epp
	 */
	public function override_theme($theme_name) {
		$this->set_theme_name($theme_name);
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
		if (Request::is_ajax()) {
			// For ajax requests, template files should be prefixed with "ajax-"
			$filename_prefix = 'ajax-';
		} else {
			$filename_prefix = '';
		}
		$path_prefix = $this->full_theme_path() . '/templates/';
		// First look for template based on module/action name:
		$primary_module_name = $this->primary_module_name();
		if (!empty($primary_module_name)) {
			$path_friendly_module_name = AkInflector::underscore($primary_module_name);
			// Look for template specific to both the primary module and current action name:
			$action = $this->user_input('action');
			if (empty($action)) {
				$action = 'index';
			}
			$module_action_template = $filename_prefix . 'module-'.$path_friendly_module_name.'-'.$action;
			$module_action_template_path = $path_prefix . $module_action_template.'.php';
			if (file_exists($module_action_template_path)) {
				return $module_action_template;
			} else {
				// Else look for template specific just to the primary module:
				$module_template = $filename_prefix . 'module-'.$path_friendly_module_name;
				$module_template_path = $path_prefix . $module_template.'.php';
				if (file_exists($module_template_path)) {
					return $module_template;
				}
			}
		}
		// Else see if a template_name is explicitly defined for this page in the database:
		$template_name = $filename_prefix.$this->_get_attribute('template_name');
		if (!empty($template_name) && (file_exists($this->full_theme_path().'/templates/' . $template_name . '.php'))) {
			// If defined in the database then the attribute will contain a value
			return $template_name;
		} else {
			// Look for template named by page slug
			$template = $filename_prefix . $this->hyphenized_slug();
			if (file_exists($this->theme_dir().'/templates/'. $template .'.php')) {
				return $template;
			} else {
				// Otherwise resort to default template.

				// Backwards compatibility note: Default templates used to be "standard" for normal requests and "ajax" for ajax requests.
				// However we want to change that to "default" or "ajax-default" for semantic reasons. As such, only use "default" or "ajax-default"
				// if present otherwise "standard" or "ajax" depending on request type.
				$default_template = $filename_prefix . 'default';
				if (file_exists($this->full_theme_path().'/templates/' . $default_template . '.php')) {
					return $default_template;
				} else {
					if (Request::is_ajax()) {
						return "ajax";
					}
					return "standard";
				}
			}
		}
	}
	/**
	 * Whether or not the page has children
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public function has_children() {
		if (!$this->is_new()) {
			$child_count = (int)DB::fetch_one("SELECT COUNT(*) FROM `page_index` WHERE `parent` = ?",$this->id());
			return ($child_count > 0);
		}
		return false;
	}
	/**
	 * Select a template file for rendering
	 *
	 * @return string The path and filename of the template to render relative to either the project or framework root 
	 * @author Peter Epp
	 */
	public function select_template() {
		return $this->theme_dir()."/templates/".$this->template_name().".php";
	}
	/**
	 * Return the URL for the theme's favicon
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function theme_favicon_url() {
		if (file_exists($this->full_theme_path()."/favicon.ico")) {
			if (stristr($this->full_theme_path(),FW_ROOT)) {
				$favicon_url = '/framework/'.$this->theme_dir().'/favicon.ico';
			} else {
				$favicon_url = '/'.$this->theme_dir().'/favicon.ico';
			}
			return $favicon_url;
		}
		return false;
	}
	/**
	 * Determine the last time any files in the theme were updated
	 *
	 * @return int Unix timestamp
	 * @author Peter Epp
	 */
	public function latest_theme_update() {
		$theme_path  = $theme_path = Crumbs::file_exists_in_load_path($this->theme_dir(),true);
		$theme_files = FindFiles::ls($theme_path, array(), false, true);
		if (!empty($theme_files)) {
			foreach ($theme_files as $theme_file) {
				$timestamps[] = $theme_file->getMTime();
			}
			rsort($timestamps);
			return reset($timestamps);
		}
		return false;
	}
	/**
	 * Return the timestamp of when the template or the page's static view (if present) was last updated, whichever is newer
	 *
	 * @return int
	 * @author Peter Epp
	 */
	public function latest_update() {
		$timestamps = array();
		$page_index_info = DB::fetch_one("SHOW TABLE STATUS LIKE 'page_index'");
		if ($page_index_info) {
			$timestamps[] = strtotime($page_index_info['Update_time']);
		}
		$template_file = $this->select_template();
		if ($full_template_path = Crumbs::file_exists_in_load_path($template_file)) {
			$timestamps[] = filemtime($full_template_path);
		}
		if ($full_view_path = Crumbs::file_exists_in_load_path($this->view_file())) {
			$timestamps[] = filemtime($full_view_path);
		}
		$template_codefile = $this->full_theme_path().'/template.php';
		if (file_exists($template_codefile)) {
			$timestamps[] = filemtime($template_codefile);
		}
		if (empty($timestamps)) {
			return false;
		}
		rsort($timestamps);
		return reset($timestamps);
	}
	/**
	 * Return the full path to cache file for current page
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function cache_file() {
		$request_uri = substr(Request::uri(),1);	// Everything after preceding "/"
		if (empty($request_uri)) {
			$request_uri = '/index';
		} else if (substr($request_uri,-1) == '?') {
			$request_uri = substr($request_uri,0,-1);
		}
		$hyphenized_uri = str_replace('/','-',$request_uri);
		$full_file_path = SITE_ROOT.'/page_cache/'.$this->theme_name().'-'.$this->template_name().'-'.$hyphenized_uri;
		if (Request::is_ajax()) {
			$full_file_path .= '-ajax';
		}
		$full_file_path .= '.cache';
		return $full_file_path;
	}
	/**
	 * Write page content to the page cache file
	 *
	 * @param string $page_content 
	 * @return void
	 * @author Peter Epp
	 */
	public function cache_write($page_content) {
		if (!file_put_contents($this->cache_file(),$page_content)) {
			throw new CoreException('Failed to write cache file: '.$this->cache_file());
		}
	}
	/**
	 * Return the timestamp when the cache file was last modified, or a large negative number if not found
	 *
	 * @return int Unix timestamp
	 * @author Peter Epp
	 */
	public function cache_file_time() {
		$cache_file = $this->cache_file();
		if (file_exists($cache_file)) {
			$cache_time = filemtime($cache_file);
			Console::log("        Page cache last modified on: ".gmdate(GMT_FORMAT, $cache_time));
		} else {
			$cache_time = -999999999;
			Console::log("        Page cache file not found");
		}
		return $cache_time;
	}
	/**
	 * Return cached page content
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function cached_content() {
		return file_get_contents($this->cache_file());
	}
	/**
	 * Set the template name
	 *
	 * @param string $template_name 
	 * @return void
	 * @author Peter Epp
	 */
	public function set_template($template_name) {
		$this->set_template_name( $template_name);
	}
	/**
	 * Name of the database table
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function db_tablename() {
		return 'page_index';
	}
	/**
	 * Set the page slug based on the title.  If the slugs has changed since last time we must also update the module_pages table
	 * so things don't break.
	 *
	 * TODO: as and when other modules are developed that change a page's title, thereby changing the slug, this should be updated to rename the view file as well, if
	 * one is present.  For now this isn't an issue as currently the only module that can change a page's title is PageContent
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function save() {
		$old_slug = $this->slug();
		$parent = $this->parent();
		if ($this->is_new()) {
			$old_parent = null;
		} else {
			$old_parent = (int)DB::fetch_one("SELECT `parent` FROM `page_index` WHERE `id` = ?",$this->id());
		}
		$parent_path = '';
		if ($parent != 0) {
			$parent_path = DB::fetch_one("SELECT `slug` FROM `page_index` WHERE `id` = ?",$parent);
			if (!empty($parent_path)) {
				$parent_path .= '/';
			}
		}
		if ($old_slug != "index") {
			$title = $this->title();
			$new_slug = $parent_path.$this->friendly_slug();
			$new_slug = $this->ensure_unique_slug($new_slug);
			$this->set_slug($new_slug);
			if ($old_parent != $parent) {
				$page_input = $this->user_input('page');
			}
		} else {
		    $new_slug = $old_slug;
		}
		$is_new = $this->is_new();
		$save_success = parent::save();
		if ($save_success) {
			if (!$is_new && $new_slug != $old_slug) {
				// Find all the sub-pages and update their slugs in the page_index and module_pages tables:
				$my_id = $this->id();
				$Page = new ModelFactory('Page');
				$sub_pages = $Page->models_from_query("SELECT * FROM `page_index` WHERE `id` != ? AND `slug` LIKE ?",array($my_id,$old_slug."/%"));
				if (!empty($sub_pages)) {
					foreach ($sub_pages as $index => $sub_page) {
						$old_subpage_slug = $sub_page->slug();
						$subpage_slug = substr($old_subpage_slug,strlen($old_slug)+1);	// Everything after the current new slug plus a "/"
						$new_subpage_slug = $new_slug."/".$subpage_slug;
						DB::query("UPDATE `page_index` SET `slug` = ? WHERE `id` = ?",array($new_subpage_slug,$sub_page->id()));
						DB::query("UPDATE `module_pages` SET `page_name` = ? WHERE `page_name` = ?",array($new_subpage_slug,$old_subpage_slug));
					}
				}
				// Update the module_pages table with the new slug for the current page:
				DB::query("UPDATE `module_pages` SET `page_name` = ? WHERE `page_name` = ?",array($new_slug,$old_slug));
				Event::fire('page_slug_changed',$old_slug,$new_slug);
				// Update the access_levels table if the renamed page is set as a login or home URL for one or more access levels. Technically we should use
				// the access levels model, but that's a lot more code and more DB queries to accomplish.
				$access_level_old_url = '/'.$old_slug;
				$access_level_new_url = '/'.$new_slug;
				DB::query("UPDATE `access_levels` SET `login_url` = ? WHERE `login_url` = ?",array($access_level_new_url,$access_level_old_url));
				DB::query("UPDATE `access_levels` SET `home_url` = ? WHERE `home_url` = ?",array($access_level_new_url,$access_level_old_url));
				// Create/update aliases as needed per the page slug change
				$alias_factory = new PageAliasFactory();
				$alias_factory->update($old_slug,$new_slug,$sub_pages);
				Biscuit::instance()->empty_cache();
			} else if ($is_new) {
				// Add the page content module to the new page:
				$module_id = DB::fetch_one("SELECT `id` FROM `modules` WHERE `name` = 'PageContent'");
				DB::insert("INSERT INTO `module_pages` SET `module_id` = ?, `page_name` = ?, `is_primary` = 1",array($module_id,$new_slug));
			}
		}
		return $save_success;
	}
	/**
	 * Ensure unique page slug by adding a number to the slug if it's not already unique until it is unique
	 *
	 * @param string $slug 
	 * @return string
	 * @author Peter Epp
	 */
	private function ensure_unique_slug($slug) {
		$unique_slug = $slug;
		$index = 1;
		$query = "SELECT `id` FROM `page_index` WHERE `slug` = ?";
		if (!$this->is_new()) {
			$query .= " AND `id` != ".$this->id();
		}
		while (DB::fetch($query,$unique_slug)) {
			$unique_slug = $slug.'-'.$index;
			$index++;
		}
		return $unique_slug;
	}
	/**
	 * Delete module associations after page is removed
	 *
	 * @return bool Success
	 * @author Peter Epp
	 */
	public function delete() {
		if (parent::delete()) {
			$alias_factory = new PageAliasFactory();
			$alias_factory->update(null,$this->slug());
			DB::query("DELETE FROM `module_pages` WHERE `page_name` = ? OR `page_name` LIKE ?",array($this->slug(),$this->slug().'/%'));
			return true;
		}
		return false;
	}
	/**
	 * The representation of a page object as a string.
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function __toString() {
		return 'the &ldquo;'.$this->title().'&rdquo; Page';
	}
}
?>
