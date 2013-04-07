<?php

$migration_success = DB::query("ALTER TABLE `system_settings` MODIFY COLUMN `value` text");
