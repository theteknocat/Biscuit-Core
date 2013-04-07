<?php

$migration_success = true;

// Update system admin permissions per update in revision 13561 of the system admin module
Permissions::add('SystemAdmin',array('index' => 99, 'appearance' => 99, 'activate_theme' => 99, 'configuration' => 99, 'log_viewer' => 99, 'log_delete' => 99, 'manage_modules' => 99, 'manage_extensions' => 99),true);
