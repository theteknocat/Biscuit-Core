<?php
$success1 = DB::query("CREATE TABLE IF NOT EXISTS `locales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `iso_639_lang_code` varchar(3) NOT NULL,
  `iso_3166_country_code` varchar(3) NOT NULL,
  `friendly_name` varchar(255) NOT NULL,
  `short_name` varchar(255) DEFAULT NULL,
  `is_default` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8");
if ($success1) {
	DB::query("DELETE FROM `locales`");
	$migration_success = DB::query("INSERT INTO `locales` (`id`, `iso_639_lang_code`, `iso_3166_country_code`, `friendly_name`, `short_name`, `is_default`) VALUES (1, 'en', 'CA', 'Canadian English', 'English', 1)");
}
