<?php

$migration_success = DB::query("ALTER TABLE `cache_page` ADD COLUMN `session_timeout` INT(4) NOT NULL DEFAULT 0 AFTER `user_id`");
