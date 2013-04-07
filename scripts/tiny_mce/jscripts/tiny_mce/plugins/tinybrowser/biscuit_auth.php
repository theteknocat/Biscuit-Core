<?php
require_once(realpath(dirname(__FILE__).'/../../../../../../../framework/core.php'));
Console::log("Performing Biscuit authentication for TinyBrowser file manager");
// Set the session ID from GET params. Session::set_id() will take care of only setting it if the value isn't empty. This is for the Flash session
// bug workaround, which must be done here not in the TB config file as it needs to be in place prior to Bootstrapping the framework.
Session::set_id(Request::query_string('sessidpass'));
Console::log("Session ID from query string: ".Request::query_string('sessidpass'));
$Biscuit = new Biscuit();
// Instantiate the Biscuit authenticator plugin
$biscuit_user_can_upload         = false;
$biscuit_user_can_edit           = false;
$biscuit_user_can_delete         = false;
$biscuit_user_can_modify_folders = false;
if (Authenticator::user_is_logged_in()) {
	$logged_user_data = Session::get('auth_data');
	// Get an instance of the user object:
	$Biscuit->plugins['Authenticator']->user = User::find($logged_user_data['id']);
	$current_user_level = $Biscuit->plugins['Authenticator']->user->user_level();
	Console::log("Current user level: ".$current_user_level);
	if (defined('TINYBROWSER_ACCESS_LEVEL')) {
		if ($current_user_level >= TINYBROWSER_ACCESS_LEVEL) {
			Session::set('tiny_browser_access_enabled',true);
		} else {
			Session::unset_var('tiny_browser_access_enabled');
		}
		$biscuit_user_can_upload         = (defined('TINYBROWSER_UPLOAD_LEVEL') && $current_user_level >= TINYBROWSER_UPLOAD_LEVEL);
		$biscuit_user_can_edit           = (defined('TINYBROWSER_EDIT_LEVEL') && $current_user_level >= TINYBROWSER_EDIT_LEVEL);
		$biscuit_user_can_delete         = (defined('TINYBROWSER_DELETE_LEVEL') && $current_user_level >= TINYBROWSER_DELETE_LEVEL);
		$biscuit_user_can_modify_folders = (defined('TINYBROWSER_FOLDER_MODIFY_LEVEL') && $current_user_level >= TINYBROWSER_FOLDER_MODIFY_LEVEL);
	} else {
		Session::unset_var('tiny_browser_access_enabled');
	}
} else {
	Session::unset_var('tiny_browser_access_enabled');
}
?>