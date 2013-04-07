<?php
/**
 * Model for dealing with the page_index table for reading and writing data.  At this time it does not support insertion or deletion, only updating existing.
 * It knows about the BiscuitModel and makes use of it for retrieving page data.  Unlike most models in the framework, this one does not return an instantiated copy
 * from the find functions, only the raw data.  The PageContent model deals with instantiating copies of this model when it needs to.
 *
 * @package Plugins
 * @author Peter Epp
 */
class PageIndex extends AbstractModel {
	var $parent;
	var $shortname;
	var $title;
	var $description;
	var $keywords;
	var $sort_order;
	var $ext_link;
	var $hidden;
	var $access_level;
	var $force_secure;
	var $template_name;
	var $allow_print;

	function parent()        {	return $this->get_attribute('parent');			}
	function shortname()     {	return $this->get_attribute('shortname');		}
	function title()         {	return $this->get_attribute('title');			}
	function description()   {	return $this->get_attribute('description');		}
	function keywords()      {	return $this->get_attribute('keywords');		}
	function sort_order()    {	return $this->get_attribute('sort_order');		}
	function ext_link()      {	return $this->get_attribute('ext_link');		}
	function hidden()        {	return $this->get_attribute('hidden');			}
	function access_level()  {	return $this->get_attribute('access_level');	}
	function force_secure()  {	return $this->get_attribute('force_secure');	}
	function template_name() {	return $this->get_attribute('template_name');	}
	function allow_print()   {	return $this->get_attribute('allow_print');		}

	function validate() {
		if (!$this->title()) {
			$this->set_error("Please enter a title for this page");
		}
		return (!$this->errors());
	}
	/**
	 * Find one page
	 *
	 * @param string $id 
	 * @return void
	 * @author Peter Epp
	 */
	function find($id) {
		$id = (int)$id;
		return PageIndex::model_from_query('PageIndex',"SELECT * FROM `page_index` WHERE `id` = ".$id);
	}
	/**
	 * Find all public pages that are part of the normal menu hierarchy
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function find_all($exclude_path=null) {
		$extra_condition = "";
		if (!empty($exclude_path)) {
			$extra_condition = " AND `shortname` NOT LIKE '".$exclude_path."%'";
		}
		$query = "SELECT * FROM `page_index` WHERE `parent` < 999999 AND `access_level` = 0".$extra_condition." ORDER BY `shortname`, `sort_order`";
		return PageIndex::models_from_query('PageIndex',$query);
	}
	function save() {
		$old_shortname = $this->shortname();
		$parent_path = '';
		// Find the parent page's path:
		$parent = $this->parent();
		if ($parent != 0) {
			$parent_path = DB::fetch_one("SELECT `shortname` FROM `page_index` WHERE `id` = ".$parent);
			if (!empty($parent_path)) {
				$parent_path .= '/';
			}
		}
		if ($old_shortname != "index") {
			// If not saving the home (index) page, rename the slug based on the title
			$title = $this->title();
			$new_shortname = str_replace(' ','-',strtolower($title));
			$new_shortname = preg_replace('/[^a-z0-9_-]/si','-',$new_shortname);	// Strip all but numbers, letters, hyphens and underscores
			$new_shortname = $parent_path.$new_shortname;
			$new_shortname = $this->ensure_unique_slug($new_shortname);
			$this->set_attribute('shortname',$new_shortname);
			$last_sort_order = DB::fetch_one("SELECT `sort_order` FROM `page_index` WHERE `parent` = {$parent} ORDER BY `sort_order` DESC LIMIT 1");
			$new_sort_order = (int)$last_sort_order+10;
			$this->set_attribute('sort_order',$new_sort_order);
		} else {
			$new_shortname = 'index';
		}
		$is_new = $this->is_new();
		$save_success = parent::save();
		if ($save_success) {
			if ($is_new) {
				// Add the page content manager plugin to the new page:
				$cms_plugin_id = DB::fetch_one("SELECT `id` FROM `plugins` WHERE `name` = 'Page Content Manager'");
				$page_mg_plugin_id = DB::fetch_one("SELECT `id` FROM `plugins` WHERE `name` = 'Page Manager'");
				DB::query("INSERT INTO `plugin_pages` (`plugin_id`,`page_name`,`is_primary`) VALUES ({$cms_plugin_id},'{$new_shortname}',1),({$page_mg_plugin_id},'{$new_shortname}',0)");
			} else if ($new_shortname != $old_shortname) {
				// Find all the sub-pages and update their shortnames as well as the plugin_pages table:
				$my_id = $this->id();
				$sub_pages = PageIndex::models_from_query('PageIndex',"SELECT * FROM `page_index` WHERE `id` != {$my_id} AND `shortname` LIKE '{$old_shortname}%'");
				if (!empty($sub_pages)) {
    				foreach ($sub_pages as $index => $sub_page) {
						$old_subpage_shortname = $sub_page->shortname();
						$subpage_shortname = substr($old_subpage_shortname,strlen($old_shortname)+1);	// Everything after the current shortname plus a "/"
						$new_subpage_shortname = $new_shortname."/".$subpage_shortname;
    					DB::query("UPDATE `page_index` SET `shortname` = '{$new_subpage_shortname}' WHERE `id` = ".$sub_page->id());
    					DB::query("UPDATE `plugin_pages` SET `page_name` = '{$new_subpage_shortname}' WHERE `page_name` = '{$old_subpage_shortname}'");
    				}
				}
				// Update the plugin_pages table with the new shortname for the current page:
				DB::query("UPDATE `plugin_pages` SET `page_name` = '{$new_shortname}' WHERE `page_name` = '{$old_shortname}'");
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
	function ensure_unique_slug($slug) {
		$unique_slug = $slug;
		$index = 1;
		$query = "SELECT `id` FROM `page_index` WHERE `shortname` = '%s'";
		if (!$this->is_new()) {
			$query .= " AND `id` != ".$this->id();
		}
		while (DB::fetch(sprintf($query,$unique_slug))) {
			$unique_slug = $slug.'-'.$index;
			$index++;
		}
		return $unique_slug;
	}
	/**
	 * Delete a page, its content and remove associated plugins
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function delete() {
		// First delete the content for the page:
		$page_content = PageContent::find($this->id());
		$page_content->delete();
		if ($page_content->delete()) {
			// If successful, delete the page itself:
			parent::delete();
			// And then remove any plugins installed on the page:
			DB::query("DELETE FROM `plugin_pages` WHERE `page_name` = '".$this->shortname()."'");
			return true;
		}
		return false;
	}
	function db_tablename() {
		return 'page_index';
	}
}
?>