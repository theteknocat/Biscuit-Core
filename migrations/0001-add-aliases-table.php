<?php
$migration_success = DB::query("CREATE TABLE IF NOT EXISTS `page_aliases` (
  `id` int(11) NOT NULL auto_increment,
  `old_slug` text NOT NULL,
  `current_slug` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
?>