<?php

$migration_success = false;
$query = "INSERT INTO `system_settings` (`constant_name`,`friendly_name`,`description`,`value`,`value_type`,`required`,`group_name`) VALUES
	('BREADCRUMB_HOME_LABEL','Breadcrumb Home label','The label to use for the home link in the breadcrumb trail. Defaults to \"Home\" if left blank.','','',0,'Breadcrumbs'),
	('BREADCRUMB_SEPARATOR','Breadcrumb Separator','Character for separating breadcrumbs. Enter special character as HTML entity.','','',0,'Breadcrumbs'),
	('BREADCRUMB_SHOW_CURRENT_PAGE_AS_LAST','Current Page as Last Crumb','Show the current page as the last breadcrumb (will not be a link). Defaults to Yes.','','select{Yes|No}',0,'Breadcrumbs')";
if (DB::query($query)) {
	if (DB::query("UPDATE `system_settings` SET `group_name` = 'Image Dimensions' WHERE `constant_name` LIKE 'IMG_%' OR `constant_name` LIKE 'THUMB_%'")) {
		$migration_success = DB::query("ALTER TABLE `system_settings` MODIFY COLUMN `value` VARCHAR(255) DEFAULT NULL");
	}
}
