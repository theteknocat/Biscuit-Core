<?php
/**
 * A collection of rendering functions that draw various navigation widgets
 *
 * @package Plugins
 * @author Peter Epp
 **/

class Navigation extends AbstractPluginController {
	function run() {
		// No-one here but us comment lines. We just don't want to bother running the Abstract controllers run function, since there are no actions to dispatch to.
	}
	function render_list_menu($currentParID) {
		$returnHtml = '';
		// Read all the rows from the database for the current parent menu (0 is the top menu):
		$page_data = BiscuitModel::find_by_parent($currentParID);
		if ($page_data !== false) {
			$Biscuit = &$this->Biscuit;
			include("views/plugins/navigation/list_menu.php");
		}
	}

	/**
	 * Render all top-level pages as a string of text links using a view file
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function render_text_mainmenu($sorting = 'ORDER BY `sort_order`') {
		$menu_data = BiscuitModel::find_by_parent(0,$sorting);
		if ($menu_data !== false) {
			$Biscuit = &$this->Biscuit;
			include("views/plugins/navigation/text_mainmenu.php");
			return;
		}
	}

	/**
	 * Render all sub-pages of the current page (if any exist) as text links using a view file, either with a default or specified filename
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function render_text_submenu($page_id = "", $sorting = 'ORDER BY `sort_order`', $view_file = "text_submenu.php") {
		if (empty($page_id)) {
			$page_id = $this->Biscuit->page_id;
		}
		if ($this->Biscuit->page_id != 0) {
			$menu_data = BiscuitModel::find_by_parent($page_id,$sorting);
			if ($menu_data !== false) {
				$Biscuit = &$this->Biscuit;
				$return = include "views/plugins/navigation/".$view_file;
				Crumbs::include_response($return,"Menu file","The sub-menu view file could not be included.");
				return;
			}
		}
	}

	function render_sitemap($indent,$currentParID) {
		// $result = mysql_query("SELECT id, shortname, title, ext_link FROM page_index WHERE parent = $currentParID AND hidden = 0 ORDER BY sort_order");
		$result = mysql_query("SELECT id, shortname, title, ext_link, parent FROM page_index WHERE parent = $currentParID AND (access_level = ".$this->Biscuit->access_level." OR access_level = ".((isset($_SESSION['auth_data'])) ? $_SESSION['auth_data']['user_level'] : "1").") AND shortname NOT LIKE '%login%' ORDER BY sort_order");
		if (@mysql_num_rows($result) > 0) {
			if ($indent == 0) {
				$navigator .= "\n<!-- start map section -->\n<table width=\"100%\" cellspacing=\"2\" cellpadding=\"2\" border=\"0\"><tr>\n\n";
			}
			else {
				$navigator .= "<div id=\"menu".$currentParID."\" style=\"display: ".(($indent == 1) ? "block" : "none")."\">";
				$navigator .= "<ul class=\"sitemap_reg\">";
			}
			$setpercent = false;
			for ($i=0;$i < @mysql_num_rows($result);$i++) {
				$menu_item = mysql_fetch_assoc($result);
				if ($menu_item['parent'] != 999999) {
					if ($indent == 0) {
						$xval = (@mysql_num_rows($result)-$i);
						if ($setpercent === false) {
							if ($xval < 3 && (@mysql_num_rows($result)%3) != 0 && ($i+1)%3 != 0) {
								$percent = 100/$xval;
								$setpercent = true;
							}
							else {
								$percent = 33;
							}
						}
						$navigator .= "<td width=\"".$percent."%\" valign=\"top\">";
					}
					// Iterate through the menu items:
					$item_title = $menu_item['title'];
					$has_submenu = $this->menu_has_submenu($menu_item['id']);
					if ($has_submenu && $indent != 0) {
						$linkhref = "javascript:toggle_mapmenu('menu{$menu_item['id']}','{$menu_item['shortname']}{$currentParID}','link{$menu_item['shortname']}{$currentParID}')";
					}
					else {
						if ($menu_item['ext_link'] != "") {
							// If a page link has been defined, use it for the href:
							$linkhref = $menu_item['ext_link'];
						}
						else {
							// Otherwise write the href for the page for this menu item:
							if ($menu_item['shortname'] == "index") {
								$linkhref = "/";
							}
							else {
								$linkhref = "/".$menu_item['shortname'].".html";
							}
						}
					}
					// Draw the menu item:
					if ($indent == 0) {
						$navigator .= "<div align=\"center\" class=\"map_title\">";
						$navigator .= "<a name=\"".$menu_item['shortname']."\" href=\"".$linkhref."\" class=\"sitemap_title\">".$item_title."</a>";
						$navigator .= "</div>";
					}
					elseif ($has_submenu) {
						$navigator .= "<li id=\"".$menu_item['shortname'].$currentParID."\" class=\"sitemap_sub_menu\"><a name=\"".$menu_item['shortname']."\" id=\"link".$menu_item['shortname'].$currentParID."\" href=\"".$linkhref."\" class=\"sitemap_sub\">".$item_title."</a>";
					}
					else {
						if ($indent > 1 && $i == 0) {
							$result2 = mysql_query("SELECT id, shortname, title, ext_link FROM page_index WHERE id = $currentParID AND shortname NOT LIKE '%login%'");
							$parent_item = mysql_fetch_assoc($result2);
							@mysql_free_result($result2);
							if ($parent_item['ext_link'] != "") {
								// If a page link has been defined, use it for the href:
								$linkhref2 = $parent_item['ext_link'];
							}
							else {
								$linkhref2 = "/".$parent_item['shortname'].".html";
							}
							$navigator .= "<li class=\"sitemap_sub_item\"><a name=\"".$parent_item['shortname']."_sub\" href=\"".$linkhref2."\" class=\"sitemap_sub\">Main ".$parent_item['title']." Page</a>";
						}
						$navigator .= "<li class=\"sitemap_sub_item\"><a name=\"".$menu_item['shortname']."\" href=\"".$linkhref."\" class=\"sitemap_sub\">".$item_title."</a>";
					}
					if ($has_submenu) {
						// If the current item has a sub-menu, call this function again to draw it:
						// Now call the menu draw function:
						$navigator .= $this->render_sitemap(($indent+1),$menu_item['id']);
					}
					// Close the current map item:
					if ($indent == 0) {
						$navigator .= "</td>\n";
						if (($i+1)%3 == 0 && $i < @mysql_num_rows($result)-1) {
							$navigator .= "\n</tr>\n</table><!-- end map section -->\n";
							$navigator .= "\n<!-- start map section -->\n<table width=\"100%\" cellspacing=\"2\" cellpadding=\"2\" border=\"0\"><tr>\n\n";
						}
					}
					else {
						$navigator .= "</li>";
					}
				}
			}
			// Close the bulleted list:
			if ($indent == 0) {
				$navigator .= "\n</tr>\n</table><!-- end map section -->\n";
			}
			else {
				$navigator .= "</ul>";
				$navigator .= "</div>";
			}
			@mysql_free_result($result);
		}
		return $navigator;
	}

	function render_plain_menu($currentParID) {
		$menu_str = "";
		$result = mysql_query("SELECT id, shortname, title, ext_link FROM page_index WHERE parent = $currentParID ORDER BY sort_order");
		if (@mysql_num_rows($result) > 0) {
			$half = false;
			if (@mysql_num_rows($result) > 4) {
				$half = round(@mysql_num_rows($result)/2,0);
			}
			$menu_str = "

		<!-- sub-section menu // -->
		<div style=\"border: 1px solid #EA2100; padding: 5px; background-color: #E5ECF7; color: #000000\">
			<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
				<tr>
					<td width=\"50%\" style=\"padding: 3px\" valign=\"top\"><ul class=\"submenu\">";
			for ($i=0;$i < @mysql_num_rows($result);$i++) {
				$menu_item = mysql_fetch_assoc($result);
				$item_title = $menu_item['title'];
				$has_submenu = $this->menu_has_submenu($menu_item['id']);
				if ($menu_item['ext_link'] != "") {
					// If a page link has been defined, use it for the href:
					$linkhref = $menu_item['ext_link'];
				}
				else {
					// Otherwise write the href for the page for this menu item:
					$linkhref = "/".$menu_item['shortname'].".html";
				}
				$menu_str .= "
						<li><a name=\"".$menu_item['shortname']."\" href=\"".$linkhref."\"><span style=\"float: left middle; margin-right: 5px; margin-left: 10px; height: 100%\"><img src=\"/images/menu_arrow.gif\" border=\"0\" width=\"9\" height=\"9\" alt=\"".$item_title."\"></span><b>".$item_title."</b></a></li>";
				if ($half !== false && ($i+1) == $half) {
					$menu_str .= "
					</ul></td>
					<td width=\"50%\" style=\"padding: 3px\" valign=\"top\"><ul class=\"submenu\">";
				}
			}
			$menu_str .= "
					</ul></td>
				</tr>
			</table>
		</div>
		<!-- // sub-section menu -->
";
			@mysql_free_result($result);
		}
		return $menu_str;
	}

	function render_submenus() {
		$returnHtml = '';
		$result = mysql_query("SELECT id, shortname FROM page_index WHERE parent = 0 ORDER BY sort_order");
		for ($i=0;$i < @mysql_num_rows($result);$i++) {
			$row = mysql_fetch_assoc($result);
			$result2 = mysql_query("SELECT id, shortname, title FROM page_index WHERE parent = ".$row['id']." ORDER BY sort_order");
			if (@mysql_num_rows($result2) > 0) {
				$returnHtml .= '<div id="'.$row['shortname'].'" style="display: '.(($row['id'] == BiscuitModel::trace_root($this->Biscuit->page_id)) ? 'block' : 'none').'">';
				for ($x=0;$x < @mysql_num_rows($result2);$x++) {
					$row2 = mysql_fetch_assoc($result2);
					$returnHtml .= '<img src="/images/right.png" width="5" height="8" border="0" alt="'.$row2['title'].'">&nbsp;&nbsp;<a href="/'.$row2['shortname'].'.html">'.$row2['title'].'</a>'.(($x < @mysql_num_rows($result2)-1) ? '<img src="/images/spacer.gif" width="15" height="10" border="0">' : '');
				}
				$returnHtml .= '</div>';
			}
			mysql_free_result($result2);
		}
		$returnHtml .= '<div id="blank_menu" style="display: '.(($this->menu_has_submenu(BiscuitModel::trace_root($this->Biscuit->page_id))) ? 'none' : 'block').'">&nbsp;</div>';
		mysql_free_result($result);
		return $returnHtml;
	}

	function menu_has_submenu($id) {
		// Find out if a menu item has a sub-menu:
		if (Authenticator::user_is_logged_in()) {
			$auth_data = Session::get('auth_data');
		}
		$rowcount = DB::fetch_one("SELECT COUNT(*) AS sub_count FROM page_index WHERE parent = $id AND (access_level = 1 OR access_level = ".$this->Biscuit->access_level.((Authenticator::user_is_logged_in()) ? " OR access_level = ".$auth_data['user_level'] : "").") AND shortname NOT LIKE '%login%'");
		return ($rowcount > 0);
	}

	function render_bread_crumbs($separator = ">") {
		$curr_id = $this->Biscuit->page_id;
		$row = DB::fetch_one("SELECT title, parent FROM page_index WHERE id = {$curr_id}");
		$curr_id = $row['parent'];
		$curr_title = $row['title'];
		$crumb_array = array();
		do {
			$last_parent = $curr_id;
			$crumb = DB::fetch_one("SELECT title, shortname, parent FROM page_index WHERE id = {$curr_id}");
			if ($crumb !== false) {
				$last_parent = $curr_id;
				$curr_id = $crumb['parent'];
				$crumb_array[$curr_id]['title'] = $crumb['title'];
				$crumb_array[$curr_id]['link'] = '<a href="'.Navigation::url($crumb['shortname']).'" class="crumb">'.$crumb['title'].'</a>&nbsp;';
			}
			else {
				$curr_id = 0;
			}
		} while ($curr_id != 0);
		$crumb_array = array_reverse($crumb_array);
		$crumb_array = array_values($crumb_array);
		if ($this->Biscuit->page_id != 1 && ($curr_id != 0 || ($curr_id == 0 && isset($last_parent) && $last_parent != 1))) {
			echo '<a href="'.Navigation::url("index").'" class="crumb">Home</a>&nbsp;'.$separator.'&nbsp;';
		}
		if (count($crumb_array) > 0) {
			for ($i=0;$i < count($crumb_array);$i++) {
				echo $crumb_array[$i]['link'];
				echo $separator.'&nbsp;';
			}
		}
		if ($this->Biscuit->page_name != "index") {
			echo '<span>'.$curr_title.'</span>';
		}
		else {
			echo '<span>'.HOME_TITLE.'</span>';
		}
	}

	function render_searchbox($formname,$userquery = "Site Search") {
		$returnHtml = '
<!-- search box // -->
<form name="'.$formname.'" action="'.STANDARD_URL.'/scripts/process_search.php" method="post">
'.RequestTokens::render_token_field().'
<input type="hidden" name="ref_page" value="'.$this->Biscuit->page_name.'">
<table border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td style="padding-right: 10px"><a href="/advanced_search.html">Advanced Search</a></td>
		<td width="10"><img src="/images/searchbox_left.gif" alt="Searchbox" width="10" height="25" border="0"></td>
		<td width="128">
			<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td style="background-image: url(/images/searchbox_bg.gif)"><img src="/images/spacer.gif" alt="Spacer" width="1" height="25" border="0"></td>
					<td style="background-image: url(/images/searchbox_bg.gif); padding-top: 3px"><input name="searchwords" type="text" id="searchwords" value="'.$userquery.'" class="search_box" size="5" onFocus="javascript:check_searchbox(\''.$formname.'\',\'searchwords\',\''.$userquery.'\')" onBlur="javascript:check_searchbox(\''.$formname.'\',\'searchwords\',\''.$userquery.'\')"></td>
				</tr>
			</table></td>
		<td width="11" style="padding-right: 5px"><img src="/images/searchbox_right.gif" alt="Searchbox" width="10" height="25" border="0"></td>
		<td width="23"><input type="image" src="/images/search_go_off.gif" alt="Submit Search Query" style="border: none; margin: 0px; padding: 0px; width:26px; height:35px" onMouseOver="javascript:this.src=\'/images/search_go_on.gif\'" onMouseOut="javascript:this.src=\'/images/search_go_off.gif\'"></td>
	</tr>
</table>
</form>
<!-- // search box -->
';
		return $returnHtml;
	}
	function pagination_widgets($wlisting_pages,$wlisting_limit,$current_page,$colspan,$setnum = 1) {
		// Draw widgets for changing list page and number of items
		// If you use more than one set of widgets on the same page, set the value of $setnum for each of the sets sequentially
		$returnHtml = '
		<tr>
			<td'.$colspan.' style="border-bottom: 1px solid #EA2100; border-top: 1px solid #EA2100; padding: 3px"><table width="100%" border="0" cellspacing="0" cellpadding="0" class="nodisplay">
				<tr>
					<td width="50%" class="small"><select name="list_limit'.$setnum.'" id="list_limit'.$setnum.'" class="formbox" style="font-size: 10px" onChange="javascript:document.location.href=this.options[this.selectedIndex].value">
						<option value="/scripts/change_list.php?refpage='.Request::uri().'&amp;listing_page='.$current_page.'&amp;listing_limit=20"'.(($wlisting_limit == 20) ? ' selected="selected"' : '').'>20 Items</option>
						<option value="/scripts/change_list.php?refpage='.Request::uri().'&amp;listing_page='.$current_page.'&amp;listing_limit=50"'.(($wlisting_limit == 50) ? ' selected="selected"' : '').'>50 Items</option>
						<option value="/scripts/change_list.php?refpage='.Request::uri().'&amp;listing_page='.$current_page.'&amp;listing_limit=100"'.(($wlisting_limit == 100) ? ' selected="selected"' : '').'>100 Items</option>
						</select> <b>Per Page</b></td>
					<td width="50%" align="right"><input type="button" name="first_page'.$setnum.'" id="first_page'.$setnum.'" value="<<" onClick="javascript:document.location.href=\'/scripts/change_list.php?refpage='.Request::uri().'&amp;listing_page=1\'"'.(($current_page == 1) ? ' disabled="disabled"' : '').'><input type="button" name="prev_page'.$setnum.'" id="prev_page'.$setnum.'" value="<" onClick="javascript:document.location.href=\'/scripts/change_list.php?refpage='.Request::uri().'&amp;listing_page='.($current_page-1).'\'"'.(($current_page == 1) ? ' disabled="disabled"' : '').'><select name="page_changer'.$setnum.'" id="page_changer'.$setnum.'" class="formbox" style="font-size: 10px" onChange="javascript:document.location.href=this.options[this.selectedIndex].value">';
		for ($i=0;$i < $wlisting_pages;$i++) {
			$returnHtml .= '<option value="/scripts/change_list.php?refpage='.Request::uri().'&amp;listing_page='.($i+1).'"'.(($current_page == ($i+1)) ? ' selected="selected"' : '').'>Page '.($i+1).'</option>';
		}
		$returnHtml .= '</select><input type="button" name="next_page'.$setnum.'" id="next_page'.$setnum.'" value=">" onClick="javascript:document.location.href=\'/scripts/change_list.php?refpage='.Request::uri().'&amp;listing_page='.($current_page+1).'\'"'.(($current_page == $wlisting_pages) ? ' disabled="disabled"' : '').'><input type="button" name="last_page'.$setnum.'" id="last_page'.$setnum.'" value=">>" onClick="javascript:document.location.href=\'/scripts/change_list.php?refpage='.Request::uri().'&amp;listing_page='.$wlisting_pages.'\'"'.(($current_page == $wlisting_pages) ? ' disabled="disabled"' : '').'></td>
				</tr>
			</table></td>
		</tr>';
		return $returnHtml;
	}
	/**
	 * Return the fully quaified URL for a page in the framework
	 *
	 * @param int|string $page_slug_or_id The canonical ID of the page or the slug (shortname)
	 * @param bool $with_logout Optional. Whether or not to make it a logout URL that will redirect back to the page after logging out
	 * @return string
	**/
	function url($page_slug_or_id,$with_logout = false) {
		if (empty($page_slug_or_id)) {
			return STANDARD_URL.'/';
		}
		$external = false;
		if (is_int($page_slug_or_id)) {
			$page_data = BiscuitModel::find_by_id($page_slug_or_id);
		} else if (is_string($page_slug_or_id)) {
			$page_data = BiscuitModel::find_one($page_slug_or_id);
		}
		if ($page_data['ext_link'] != "") {
			$external = true;
			$page_url = $page_data['ext_link'];
		}
		else {
			$page_url = '/'.$page_data['shortname'];
		}
		if (!$external) {
			if ($with_logout) {
				$page_url = "/logout/".$page_data['shortname'];
			}
			$page_url = (($page_data['force_secure'] == 1) ? SECURE_URL : STANDARD_URL).$page_url;
		}
		return $page_url;
	}
	/**
	 * Return the HTML markup for a link to either login or go to the user's home page, depending on whether or not a user is currently logged in.
	 *
	 * @param $css_class string Optional - a CSS class name (or names separated by spaces) to apply to the anchor tag
	 * @param $inline_style string Optional - inline styles to apply to the anchor tag
	 * @return string Anchor tag markup
	 */
	function login_link($css_class = "",$inline_style = "") {
		if (Authenticator::user_is_logged_in()) {
			$url = Authenticator::user_home_url();
		}
		else {
			$url = Authenticator::login_url();
		}
		$biscuit_page_name = substr($url,1);		// Everything after the slash
		// Use the Biscuit model to get the page link name:
		$page_info = BiscuitModel::find_one($biscuit_page_name);
		$anchor = '<a href="'.(($page_info['force_secure'] == 1) ? SECURE_URL : STANDARD_URL).$url.'"';
		if (!empty($css_class)) {
			$anchor .= ' class="'.$css_class.'"';
		}
		if (!empty($inline_style)) {
			$anchor .= ' style="'.$inline_style.'"';
		}
		$anchor .= '>'.$page_info['pagetitle'].'</a>';
		return $anchor;
	}
	/**
	 * Tell Navigation::url() to build a logout URL
	 *
	 * @param string $page_name 
	 * @param string $title 
	 * @return void
	 * @author Peter Epp
	 **/
	function logout_url($page_name = '') {
		return Navigation::url($page_name,true);
	}
}
?>