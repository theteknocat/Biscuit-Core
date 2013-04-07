<?php
if (!DB::column_exists_in_table('navigation_label','page_index')) {
	$migration_success = DB::query("ALTER TABLE `page_index` ADD COLUMN `navigation_label` varchar(255) default NULL AFTER `title`");
} else {
	$migration_success = true;
}
