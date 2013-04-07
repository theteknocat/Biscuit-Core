<?php

$migration_success = true;

// Update system admin permissions per update in revision 13555 of the system admin module
Permissions::add('SystemAdmin',array('index' => 99, 'configuration' => 99, 'log_viewer' => 99),true);
