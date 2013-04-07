<?php
require_once(dirname(__FILE__).'/../config/system_globals.php');
require_once(SITE_ROOT.'/config/global.php');
require_once(FW_ROOT.'/bootstrap.php');
require_once(FW_ROOT.'/biscuit-core/i18n.php');

error_reporting(E_ERROR & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING & ~E_NOTICE);

session_name('BiscuitInstaller');
session_start();

require_once('install/installer-class.php');

if (file_exists(SITE_ROOT.'/install/custom-installer-class.php')) {
	require_once(SITE_ROOT.'/install/custom-installer-class.php');
	$installer = new CustomInstall();
} else {
	$installer = new Install();
}

$installer->run();
