<?php
$migration_success = DB::query("CREATE TABLE IF NOT EXISTS `string_translations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locale` char(5) NOT NULL,
  `msgid` longtext NOT NULL,
  `msgstr` longtext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
