<?php 

$migration_success = false;

$success1 = DB::query("CREATE TABLE IF NOT EXISTS `cache_page` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `full_url` text NOT NULL,
  `page_id` int(9) unsigned NOT NULL,
  `theme` varchar(255) NOT NULL DEFAULT '',
  `template` varchar(255) NOT NULL DEFAULT '',
  `locale_id` int(11) NOT NULL,
  `request_type` varchar(15) NOT NULL,
  `user_level` int(2) unsigned NOT NULL,
  `user_id` int(8) DEFAULT NULL,
  `content` mediumblob NOT NULL,
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_level` (`user_level`),
  KEY `user_id` (`user_id`),
  KEY `page_id` (`page_id`),
  KEY `theme` (`theme`),
  KEY `template` (`template`),
  KEY `locale_id` (`locale_id`),
  CONSTRAINT `cache_page_ibfk_1` FOREIGN KEY (`user_level`) REFERENCES `access_levels` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cache_page_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cache_page_ibfk_3` FOREIGN KEY (`page_id`) REFERENCES `page_index` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cache_page_ibfk_4` FOREIGN KEY (`locale_id`) REFERENCES `locales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

if ($success1) {
	$migration_success = DB::query("CREATE TABLE IF NOT EXISTS `cache_fragment` (
	  `type` varchar(255) NOT NULL,
	  `id` varchar(255) NOT NULL DEFAULT '',
	  `fragment_name` varchar(255) NOT NULL DEFAULT '',
	  `content` blob NOT NULL,
	  PRIMARY KEY (`type`,`id`,`fragment_name`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
}
