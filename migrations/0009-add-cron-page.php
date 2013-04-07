<?php
/**
 * Add the cron page to the page index and install any installed modules on that page as secondary
 *
 * @author Peter Epp
 */

$migration_success = false;
$cron_page = DB::fetch_one("SELECT `id` FROM `page_index` WHERE `slug` = 'cron'");
if ($cron_page) {
	$migration_success = true;
} else {
	$cron_page_id = DB::insert("INSERT INTO `page_index` (`parent`, `slug`, `title`, `exclude_from_nav`) VALUES (9999999, 'cron', 'Cron Task Page', 1)");
	if ($cron_page_id) {
		$installed_module_ids = DB::fetch("SELECT `id` FROM `modules` WHERE `installed` = 1");
		if (!empty($installed_module_ids)) {
			$insert_values = array();
			foreach ($installed_module_ids as $module_id) {
				$insert_values[] = "({$module_id}, 'cron', 0)";
			}
			$sql_insert_values = implode(", ",$insert_values);
			$migration_success = DB::query("INSERT INTO `module_pages` (`module_id`, `page_name`, `is_primary`) VALUES {$sql_insert_values}");
		} else {
			$migration_success = true;
		}
	}
}
