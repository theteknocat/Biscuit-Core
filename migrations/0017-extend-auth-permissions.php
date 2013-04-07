<?php

$migration_success = DB::query("INSERT INTO `permissions` (`permission_string`, `access_level`) VALUES ('authenticator:delete_access_level',99), ('authenticator:edit_access_level',99), ('authenticator:index_access_level',99), ('authenticator:new_access_level',99)");
