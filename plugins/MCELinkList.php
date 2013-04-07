<?php
/**
 * Generate the Javascript code for Tiny MCE containing a list of all links in the site. Other plugins can add their own items to the list by responding
 * to the "build_mce_link_list" event.  They must accept a reference to this object as a second parameter, and call the "add_to_list" method of this
 * object passing it an array of associative arrays, each of which contains a title and a URL.
 *
 * @package Plugins
 * @author Peter Epp
 */
class MCELinkList extends AbstractPluginController {
	/**
	 * Build the link list and render it
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_index() {
		$this->link_list = '
	["Home","/"],'.$this->get_page_links();
		$orphans = $this->get_page_links(999999);
		if (!empty($orphans)) {
			$this->link_list .= ",".$orphans;
		}
		EventManager::notify("build_mce_link_list");
		$this->set_view_var("link_list",$this->link_list);
		$this->Biscuit->content_type("text/javascript");
		$this->Biscuit->render_with_template(false);
		$this->render();
	}
	/**
	 * Compile a list of links for pages in the site belonging to a given parent page. Starting default parent is 0 (top-level).
	 *
	 * @param int $parent_id The ID of the parent page to find links within
	 * @param int $max_user_level Maximum allowed user level for the pages included in the list
	 * @param int $indent The indent level
	 * @return void
	 * @author Peter Epp
	 */
	function get_page_links($parent_id = 0,$max_user_level = PUBLIC_USER,$indent = 0) {
		$menu_data = BiscuitModel::find_by_parent($parent_id);
		if ($menu_data !== false) {
			$list_content = array();
			for ($i=0;$i < count($menu_data);$i++) {
				if ((int)$menu_data[$i]['access_level'] <= $max_user_level && $menu_data[$i]['shortname'] != "index") {
					$list_content[] = '
	["'.str_repeat("--",$indent).$menu_data[$i]['title'].'","'.Navigation::url($menu_data[$i]['shortname']).'"]';
					$subs = $this->get_page_links($menu_data[$i]['id'],$max_user_level,($indent+1));
					if (!empty($subs)) {
						$list_content[] = $subs;
					}
				}
			}
			return implode(",",$list_content);
		}
		return null;
	}
	/**
	 * Add links to the list
	 *
	 * @param array $list_items An indexed array of associative arrays in the format: array(0 => array("title" => "My Title", "url" => "http://mydomain.com/my_page"))
	 * @param string $section_title Optional - title for the section of links in the list. If not provided, it will be separated by a row of dashes.
	 * @return void
	 * @author Peter Epp
	 */
	function add_to_list($list_items,$section_title = null) {
		if (empty($list_items)) {
			return;
		}
		Console::log($list_items);
		if (!empty($section_title)) {
			$this->link_list .= ',
	["--- '.$section_title.' ---",""],';
		}
		else {
			$this->link_list .= ',
	["-------------------------",""],';
		}
		$list_content = array();
		foreach ($list_items as $item) {
			$list_content[] = '
	["'.$item['title'].'","'.$item['url'].'"]';
		}
		$this->link_list .= implode(",",$list_content);
	}
}
?>