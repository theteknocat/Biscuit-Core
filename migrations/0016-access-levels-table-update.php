<?php

$migration_success = DB::query("ALTER TABLE `access_levels` MODIFY COLUMN `description` text DEFAULT NULL, MODIFY COLUMN `home_url` varchar(255) DEFAULT NULL");
