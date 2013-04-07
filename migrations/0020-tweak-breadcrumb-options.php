<?php

$migration_success = DB::query("UPDATE `system_settings` SET `value_type` = 'radios{Yes|No}' WHERE `constant_name` = 'BREADCRUMB_SHOW_CURRENT_PAGE_AS_LAST'");
