<?php
$migration_success = false;
if (!DB::column_exists_in_table('value_type','system_settings')) {
	DB::query("ALTER TABLE `system_settings` ADD COLUMN `value_type` varchar(255) DEFAULT NULL AFTER `value`");
	DB::query("UPDATE `system_settings` SET `value_type` = 'timezone' WHERE `constant_name` = 'TIME_ZONE'");
	DB::query("UPDATE `system_settings` SET `value_type` = 'year' WHERE `constant_name` = 'LAUNCH_YEAR'");
}
if (!DB::column_exists_in_table('required','system_settings')) {
	DB::query("ALTER TABLE `system_settings` ADD COLUMN `required` tinyint(4) NOT NULL DEFAULT 1 AFTER `value_type`");
}
if (!DB::column_exists_in_table('group_name','system_settings')) {
	DB::query("ALTER TABLE `system_settings` ADD COLUMN `group_name` varchar(255) NOT NULL DEFAULT 'Site' AFTER `required`");
}
$migration_success = true;
?>
