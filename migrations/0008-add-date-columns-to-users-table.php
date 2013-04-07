<?php
if (!DB::column_exists_in_table('created_at','users') && !DB::column_exists_in_table('updated_at','users')) {
	$migration_success = DB::query("ALTER TABLE `users` ADD COLUMN `created_at` datetime DEFAULT '0000-00-00 00:00:00', ADD COLUMN `updated_at` datetime DEFAULT '0000-00-00 00:00:00'");
} else {
	$migration_success = true;
}
