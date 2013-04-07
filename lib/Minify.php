<?php
// Include framework core
require_once(dirname(__FILE__)."/../../scripts/config.php");
require_once("lib/core.php");
Session::start();
Console::log("-----   PHP Minify   -----");
if (Session::get("minify_groups")) {
	$minify_gps = array('groups' => Session::get("minify_groups"));
	Console::log("Minifying groups: ".print_r($minify_gps,true));
	ini_set("include_path",FW_ROOT."/lib/minify/lib:".ini_get("include_path"));
	require_once('Minify.php');
	Minify::setCache(); // in 2.0 was "useServerCache"
	Minify::serve('Groups', $minify_gps);
}
else {
	Console::log("No Groups Found to Minify!");
}
Console::log("----- End PHP Minify -----");
?>