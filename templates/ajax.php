<?php
	if (Session::flash_isset("user_message")) {
?>
	<!-- Start Notification -->
			<p><?php echo Session::flash_html_dump("user_message")?></p>
	<!-- End Notification -->
<?php
	}
	// Include the page content file, catching and handling an error if one occurred with the include:
	$return = include $Biscuit->viewfile;
	Crumbs::include_response($return,"View file","The page cannot be displayed because the view file could not be found. Please contact the system administrator immediately.");
?>
