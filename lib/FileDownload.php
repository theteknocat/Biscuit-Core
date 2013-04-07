<?php
// FIXME 2008-06-20 this file is a security risk -- must check that user input is within allowed directory
require_once(dirname(__FILE__)."/../scripts/globals.php");
require_once("lib/core.php");
Session::start();
Response::header("Cache-control: private");
Response::header('Content-type: '.Request::query_string('mime_type'));
Response::header('Content-Disposition: attachment; filename="'.Request::query_string('filename').'"');
echo file_get_contents(SITE_ROOT.Request::query_string('path')."/".Request::query_string('filename'));
if (isset(Request::query_string('ref_page')) && Request::query_string('ref_page') != "") {
	Response::redirect("/".Request::query_string('ref_page').".html");
}
?>