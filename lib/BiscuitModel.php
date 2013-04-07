<?php
/**
 * Provides database retrieval functions for the Biscuit class
 *
 * @package Core
 * @author Peter Epp
 **/
class BiscuitModel {
	/**
	 * Fetch all data for all pages in the database
	 *
	 * @return array Indexed array of associative arrays of database row data
	 * @author Peter Epp
	 * @param $sort_col string Optional - The column by which you want the results sorted
	 * @param $sort_dir string Optional - 
	 **/
	function find($sort_col,$sort_dir) {
		$query = "SELECT * FROM `page_index`";
		if (!empty($sort_col)) {
			$query .= " ORDER BY `".$sort_col."`";
		}
		if (!empty($sort_dir) && !empty($sort_col)) {
			$query .= " ".$sort_dir;
		}
		return DB::fetch($query);
	}
	/**
	 * Fetch all data for one page in the database by the page's short name
	 *
	 * @return array An associative array of the page data
	 * @author Peter Epp
	 * @param $shortname string Optional - The short name of the page you wish to fetch the data for. If left blank, the name of the current page will be used
	 **/
	function find_one($shortname) {
		$page_data = DB::fetch_one("SELECT * FROM `page_index` WHERE `shortname` = '".DB::escape($shortname)."'");
		if ($page_data !== false) {
			$page_data['page_id'] = $page_data['id'];
			$page_data['pagetitle'] = $page_data['title'];
			$page_data['page_parent'] = $page_data['parent'];
			unset($page_data['id'],$page_data['title'],$page_data['parent']);
		}
		return $page_data;
	}
	/**
	 * Fetch all data for one page in the database by the page's row id
	 *
	 * @return array Associative array of the page data
	 * @author Peter Epp
	 * @param $id int The id of the database row you want to retrieve
	 **/
	function find_by_id($id) {
		return DB::fetch_one("SELECT * FROM `page_index` WHERE `id` = ".DB::escape($id));
	}
	/**
	 * Fetch all data for all pages matching a specific parent id
	 *
	 * @return array Indexed array of associative arrays of page data
	 * @author Peter Epp
	 * @param $parent_id int The id of the parent page you wish to fetch data for
	 **/
	function find_by_parent($parent_id,$sorting = 'ORDER BY `sort_order`') {
		if (Authenticator::user_is_logged_in()) {
			$auth_data = Session::get('auth_data');
		}
		$query = "SELECT * FROM page_index WHERE parent = ".DB::escape($parent_id)." AND (access_level = 0".((!empty($auth_data)) ? " OR access_level <= ".$auth_data['user_level'] : "").") AND shortname NOT LIKE '%login%' ".$sorting;
		return DB::fetch($query);
	}
	function find_installed_plugins() {
		$query = "SELECT * FROM plugins WHERE installed = 1 ORDER BY sort_order";
		return DB::fetch($query);
	}
	/**
	 * Fetch all the data for all the plugins associated with the current page
	 *
	 * @return array Indexed array of associative arrays of page data
	 * @author Peter Epp
	 **/
	function find_page_plugins($shortname) {
		$query =	"SELECT p.`id`
					 FROM `plugins` p
					 LEFT JOIN `plugin_pages` pp ON (p.`id` = pp.`plugin_id`)
					 WHERE p.`installed` = 1 AND pp.`page_name` = '".DB::escape($shortname)."'
					 ORDER BY p.`sort_order`";
		$page_specific_ids = DB::fetch($query);
		if (empty($page_specific_ids)) {
			$page_specific_ids = -1;
		}
		else {
			$page_specific_ids = implode(', ',$page_specific_ids);
		}
		$query =	"SELECT p.*, pp.`is_primary`
					 FROM `plugins` p
					 LEFT JOIN `plugin_pages` pp ON (p.`id` = pp.`plugin_id`)
					 WHERE p.`installed` = 1
					 AND (pp.`page_name` = '".DB::escape($shortname)."'
					 OR (pp.`page_name` = '*' AND pp.`plugin_id` NOT IN ({$page_specific_ids})))
					 ORDER BY p.`sort_order`";
		return DB::fetch($query);
	}
	/**
	 * Fetch the ID of the top-level parent of any given page
	 *
	 * @param int $page_id The id of the page whose top-level parent id you want to find
	 * @return void
	 * @author Peter Epp
	 */
	function trace_root($page_id) {
		$curr_parent = DB::fetch_one("SELECT parent FROM page_index WHERE id = ".DB::escape($page_id));
		while ($curr_parent != 0) {
			$my_parent = DB::fetch_one("SELECT parent FROM page_index WHERE id = ".DB::escape($page_id));
			if ($my_parent == 0) {
				break;
			}
			else {
				$curr_parent = $my_parent;
			}
		}
		return ($curr_parent == 0 || $curr_parent == 999999) ? $page_id : $curr_parent;
	}
	/**
	 * Fetch the name of the top-level parent of any given page
	 *
	 * @param int $page_id The id of the page whose top-level parent name you want to find
	 * @return void
	 * @author Peter Epp
	 */
	function root_pagename($page_id) {
		return DB::fetch_one("SELECT shortname FROM page_index WHERE id = ".DB::escape(BiscuitModel::trace_root($page_id)));
	}
	/**
	 * Fetch an array of id's from any given page down to a specified root id
	 *
	 * @param int $page_id The id of the page to start at
	 * @param int $root_id The id of the root id to stop at
	 * @return void
	 * @author Peter Epp
	 */
	function trace_path($page_id,$root_id) {
		$page_path = array($page_id);
		$my_parent = DB::fetch_one("SELECT parent FROM page_index WHERE id = ".DB::escape($page_id));
		if ($my_parent != $root_id) {
			array_push($page_path,$my_parent);
			array_merge($page_path,BiscuitModel::trace_path($my_parent,$root_id));
		}
		return $page_path;
	}
	/**
	 * Fetch the title of any given page by id
	 *
	 * @param int $page_id The id of the page whose title you want to fetch
	 * @return void
	 * @author Peter Epp
	 */
	function title($page_id) {
		return DB::fetch_one("SELECT title FROM page_index WHERE id = ".DB::escape($page_id));
	}
	/**
	 * Fetch the title of any given page by shortname
	 *
	 * @param string $shortname The shortname of the page whose title you want to fetch
	 * @return void
	 * @author Peter Epp
	 */
	function title_by_name($shortname) {
		return DB::fetch_one("SELECT title FROM page_index WHERE shortname = '".DB::escape($shortname)."'");
	}
	/**
	 * Fetch the full title of any given page starting with it's top-level parent
	 *
	 * @param int $page_id The id of the page whose title you want to fetch
	 * @param string $separator Optional - the string to separate the titles with. Defaults to ":"
	 * @return void
	 * @author Peter Epp
	 */
	function full_title($page_id, $shortname, $separator = ":") {
		if ($shortname == "index") {
			$title_string = HOME_TITLE;
		}
		else {
			do {
				$page_info = DB::fetch_one("SELECT shortname, title, parent FROM page_index WHERE id = ".DB::escape($page_id));
				$page_id = $page_info['parent'];
				$title_array[$page_id] = $page_info['title'];
			} while ($page_id != 0);
			$title_array = array_reverse($title_array);
			$title_array = array_values($title_array);
			$title_string = "";
			foreach ($title_array as $page_title) {
				if ($page_title != "") {
					$title_string .= (($title_string != "") ? " : " : "").$page_title;
				}
			}
			$title_string .= ' - '.SITE_TITLE;
		}
		return $title_string;
	}
	/**
	 * Define system settings found in the database as global variables
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function define_system_settings() {
		// Read system settings from database:
		$settings = DB::fetch("SELECT shortname, value FROM system_settings");
		for ($i=0;$i < count($settings);$i++) {
			define($settings[$i]['shortname'], $settings[$i]['value']);
		}
	}
	/**
	 * Run any migration scripts found in /framework/migrations and /migrations that haven't been run since the last migration or initial installation
	 *
	 * @param string $table_name 
	 * @return void
	 * @author Peter Epp
	 */
	function run_migrations() {
		if (!DB::table_exists('migrations')) {
			BiscuitModel::install_migration_table();
		}
		$last_fw_migration = DB::fetch_one("SELECT `file_number` FROM `migrations` WHERE `type` = 'framework'");
		$last_site_migration = DB::fetch_one("SELECT `file_number` FROM `migrations` WHERE `type` = 'site'");
		if (!file_exists(FW_ROOT."/migrations") && !file_exists(SITE_ROOT."/migrations")) {
			Console::log("        No migrations to run");
			return;
		}
		$fw_migration_files = Crumbs::ls("/framework/migrations",array(
			'match_pattern' => "[0-9]{4}\-([^\.]+)\.php"
		));
		$site_migration_files = Crumbs::ls("/migrations",array(
			'match_pattern' => "[0-9]{4}\-([^\.]+)\.php"
		));
		BiscuitModel::execute_migration_set('framework', $fw_migration_files, $last_fw_migration, FW_ROOT."/migrations");
		BiscuitModel::execute_migration_set('site', $site_migration_files, $last_site_migration, SITE_ROOT."/migrations");
	}
	/**
	 * Run through a list of migration files and execute them
	 *
	 * @param array $migration_files List of migration files
	 * @param string $last_number The number of the last migration in the set that was run
	 * @return void
	 * @author Peter Epp
	 */
	function execute_migration_set($type, $migration_files, $last_number, $file_path) {
		if (empty($migration_files)) {
			Console::log("        No ".$type." migration files found");
			return;
		}
		$skip_count = 0;
		foreach($migration_files as $migration) {
			preg_match_all("/([0-9]{4})\-/",$migration,$matches);
			$curr_number = (int)$matches[1][0];
			if ($curr_number > (int)$last_number) {
				unset($migration_success);
				Console::log("        RUNNING: ".$file_path."/".$migration);
				include_once($file_path."/".$migration);
				if (!isset($migration_success)) {
					trigger_error("The migration in ".$file_path."/".$migration." was included, but I can't tell if it was successful and therefore cannot record it in the database.", E_USER_ERROR);
				} elseif ($migration_success) {
					Console::log("            Migration reported success!");
					DB::query("DELETE FROM `migrations` WHERE `type` = '{$type}' ");
					DB::insert("INSERT INTO `migrations` SET `file_number` = {$curr_number}, `type` = '{$type}'");
				}
				else {
					Console::log("            ".$type." migration (".$migration.") reported failure!");
				}
				Console::log("        END");
			} else {
				$skip_count += 1;
			}
		}
		if ($skip_count == count($migration_files)) {
			Console::log("        No ".$type." migrations to run");
		}
	}
	/**
	 * Install the migration tracking table
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function install_migration_table() {
		$mysql_version = DB::version();
		if ($mysql_version >= 4.1) {
			$query = "CREATE TABLE `migrations` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `run_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `file_number` int(8) NOT NULL DEFAULT 0,
			  `type` enum('framework','site') DEFAULT NULL,
			  PRIMARY KEY (`id`)
			)";
		}
		else {
			$query = "CREATE TABLE `migrations` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `run_date` timestamp NOT NULL,
			  `file_number` int(8) NOT NULL DEFAULT 0,
			  `type` enum('framework','site') DEFAULT NULL,
			  PRIMARY KEY (`id`)
			)";
		}
		if (!DB::query($query)) {
			trigger_error("Unable to create migrations database table! Migrations could not run. SQL Error: ".DB::error(),E_USER_WARNING);
			return false;
		}
	}
}
?>
