<?php
$migration_success = DB::query("DELETE FROM `extensions` WHERE `name` IN ('PrototypeJs','ScriptaculousJs','Jquery','LightviewJs')");
?>