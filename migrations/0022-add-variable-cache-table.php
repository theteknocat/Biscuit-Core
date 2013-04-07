<?php 

$migration_success = DB::query("CREATE TABLE IF NOT EXISTS `cache_variable` (
  `variable_name` varchar(255) NOT NULL DEFAULT '',
  `scope` varchar(255) NOT NULL,
  `variable_value` blob NOT NULL,
  PRIMARY KEY (`variable_name`,`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
