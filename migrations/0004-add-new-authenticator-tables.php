<?php
$migration_success = false;
$first_success = DB::query("CREATE TABLE IF NOT EXISTS `user_email_verifications` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(8) NOT NULL,
  `hash` text NOT NULL,
  `verified` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

if ($first_success) {
	$migration_success = DB::query("CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
	  `id` int(11) NOT NULL auto_increment,
	  `user_id` int(8) NOT NULL,
	  `token` varchar(255) NOT NULL,
	  `created` bigint(14) NOT NULL default '0',
	  PRIMARY KEY  (`id`),
	  KEY `user_id` (`user_id`),
	  CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
}
?>