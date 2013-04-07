<?php
/**
 * Static class for handling migration functions
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: migrations.php 14196 2011-09-01 19:08:39Z teknocat $
 */
class Migrations {
	private function __construct() {
		// Prevent instantiation
	}
	/**
	 * Run any migration scripts found in /framework/migrations and /migrations that haven't been run since the last migration or initial installation
	 *
	 * @param string $table_name 
	 * @return void
	 * @author Peter Epp
	 */
	public static function run() {
		if (!DB::table_exists('migrations')) {
			Migrations::install_status_table();
		}
		if (!file_exists(FW_ROOT."/migrations") && !file_exists(SITE_ROOT."/migrations")) {
			Console::log("        No migrations to run");
			return;
		}
		$last_fw_migration = DB::fetch_one("SELECT `file_number` FROM `migrations` WHERE `type` = 'framework'");
		$last_site_migration = DB::fetch_one("SELECT `file_number` FROM `migrations` WHERE `type` = 'site'");
		$fw_migration_files = FindFiles::ls("/framework/migrations",array(
			'match_pattern' => "[0-9]{4}\-([^\.]+)\.php"
		));
		$site_migration_files = FindFiles::ls("/migrations",array(
			'match_pattern' => "[0-9]{4}\-([^\.]+)\.php"
		));
		Migrations::execute_set('framework', $fw_migration_files, $last_fw_migration, FW_ROOT."/migrations");
		Migrations::execute_set('site', $site_migration_files, $last_site_migration, SITE_ROOT."/migrations");
	}
	/**
	 * Run through a list of migration files and execute them
	 *
	 * @param array $migration_files List of migration files
	 * @param string $last_number The number of the last migration in the set that was run
	 * @return void
	 * @author Peter Epp
	 */
	private static function execute_set($type, $migration_files, $last_number, $file_path) {
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
					Console::log("            Migrations reported success!");
					Migrations::update_status($type,$curr_number);
				}
				else {
					Console::log("            Migration reported failure!");
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
	 * Update the migrations status in the database
	 *
	 * @param string $type Type of migration - "framework" or "site"
	 * @param int $number The migration number
	 * @return void
	 * @author Peter Epp
	 */
	private static function update_status($type,$number) {
		DB::query("DELETE FROM `migrations` WHERE `type` = ?", $type);
		DB::insert("INSERT INTO `migrations` SET `file_number` = ?, `type` = ?", array($number, $type));
	}
	/**
	 * Install the migration tracking table
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private static function install_status_table() {
		$query = "CREATE TABLE `migrations` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `run_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  `file_number` int(8) NOT NULL DEFAULT 0,
		  `type` enum('framework','site') DEFAULT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		DB::query($query);
	}
}
?>