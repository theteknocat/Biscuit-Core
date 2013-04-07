<?php

$migration_success = false;
$query = "INSERT INTO `system_settings` (`constant_name`,`friendly_name`,`description`,`value`,`value_type`,`required`,`group_name`) VALUES
	('BREADCRUMB_SHOW_ON_HOME_PAGE','Show Breadcrumbs on Home Page','You may want to enable this if a module runs on the home page and you want it to show action breadcrumbs.','No','radios{Yes|No}',0,'Breadcrumbs')";
$migration_success = DB::query($query);
