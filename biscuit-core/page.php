<?php
/**
 * Model the page_index table
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: page.php 14602 2012-03-21 21:52:53Z teknocat $
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
	 * Special attribute labels
	 *
	 * @var array
	 */
	protected $_attr_labels = array(
		'ext_link' => 'Redirect URL'
	);
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
	 * Whether or not current page is the home page
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public function is_home() {
		return ($this->slug() == 'index');
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
	 * Decide which attribute to use for the friendly slug - nav label if present, otherwise title
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function slug_attribute() {
		if ($this->has_navigation_label() && $this->navigation_label()) {
			return 'navigation_label';
		} else {
			return 'title';
		}
	}
	/**
	 * Validate the page title by checking that it will not result in an empty friendly slug
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public function title_is_valid() {
		$slug = $this->friendly_slug();
		return ($this->title() && !empty($slug));
	}
	/**
	 * Error message for invalid page title
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function title_error_message() {
		return __('Enter a title of at least one word, 1 or more characters long containing one or more letters or numbers that are not common short words/prepositions.');
	}
	/**
	 * Return the appropriate title for navigation, breadcrumbs browser title etc.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function navigation_title() {
		if ($this->has_navigation_label() && $this->navigation_label()) {
			return $this->navigation_label();
		}
		return $this->title();
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
		if ($this->has_navigation_label() && $this->navigation_label()) {
			$page_title = $this->navigation_label();
		} else {
			$page_title = $this->title();
		}
		$action = $this->user_input('action');
		if ($this->slug() == "index" && empty($action)) {
			if (HOME_TITLE != '') {
				$title_string = __(HOME_TITLE);
			} else {
				$title_string = __($page_title);
			}
		} else {
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
			$title_string = __($page_title).$separator.__(SITE_TITLE);
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

			$base_with_locale = "views/".I18n::instance()->locale()."/".$this->slug();

			$base = "views/".$this->slug();

			// Make a list of all the files we want to look for, in order of preference:
			$view_files = array(
				'locale_html_file' => array(
					'relative'  => $base_with_locale.'.html',
					'full_path' => Crumbs::file_exists_in_load_path($base_with_locale.".html")
				),
				'locale_php_file' => array(
					'relative'  => $base_with_locale.'.php',
					'full_path' => Crumbs::file_exists_in_load_path($base_with_locale.".php")
				),
				'locale_txt_file' => array(
					'relative'  => $base_with_locale.'.txt',
					'full_path' => Crumbs::file_exists_in_load_path($base_with_locale.".txt")
				),
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
				if (!empty($view_file['full_path']) && !stristr($view_file['full_path'],FW_ROOT)) {
					$use_view_file = $view_file['relative'];
					break;
				}
			}
			if (empty($use_view_file)) {
				// None found in site root, grab the first one that was found - it'll be in the framework root if it wasn't found in the site root
				foreach ($view_files as $type => $view_file) {
					if (!empty($view_file['full_path'])) {
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
	 * Set the template name
	 *
	 * @param string $template_name 
	 * @return void
	 * @author Peter Epp
	 */
	public function set_template($template_name) {
		$this->set_template_name($template_name);
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
	 * Set default sort order on save
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function _set_attribute_defaults() {
		$old_parent = null;
		if (!$this->is_new()) {
			$old_parent = (int)DB::fetch_one("SELECT `parent` FROM `page_index` WHERE `id` = ?",$this->id());
		}
		if (!$this->sort_order() || $this->sort_order() == 0 || $old_parent != $this->parent()) {
			$this->set_sort_order(ModelFactory::instance('Page')->next_highest('sort_order',1,'`parent` = '.$this->parent()));
		}
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
	public function save($bypass_validation = false) {
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
		$save_success = parent::save($bypass_validation);
		if ($save_success) {
			if (!$is_new && $new_slug != $old_slug) {
				// Find all the sub-pages and update their slugs in the page_index and module_pages tables:
				$my_id = $this->id();
				$sub_pages = ModelFactory::instance('Page')->models_from_query("SELECT * FROM `page_index` WHERE `id` != ? AND `slug` LIKE ?",array($my_id,$old_slug."/%"));
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
				ModelFactory::instance('PageAlias')->update($old_slug,$new_slug,$sub_pages);
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
			ModelFactory::instance('PageAlias')->update(null,$this->slug());
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
