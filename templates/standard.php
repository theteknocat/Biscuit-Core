<?php	/**
	 * Base Template. Use this as the starting point for new sites
	 *
	 * @author Peter Epp
	 */
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<?php echo $Biscuit->render_header_tags(); ?>

	<?php echo $Biscuit->render_header_includes(); ?>

</head>
<body>
<!-- Start Main Container -->
<div id="wrap"><!-- Start Main Body -->
	<div id="body">
<?php	if (Session::flash_isset("user_message")) {
?>
	<!-- Start Notification -->
			<p><?php echo Session::flash_html_dump("user_message")?></p>
	<!-- End Notification -->
<?php	}
	// Include the page content file, catching and handling an error if one occurred with the include:
	$return = include $Biscuit->viewfile;
	Crumbs::include_response($return,"View file","The page cannot be displayed because the view file could not be found. Please contact the system administrator immediately.");
?>
	</div>
<!-- End Main Body -->
<!-- Start Page Footer -->
	<div id="footer">
		<div id="textmenu"><?php $Navigation->render_text_menu(); ?></div>
		<div id="copyright"><?php echo $Biscuit->copyright_notice()?></div>
		<div id="hitcounter"><?php echo $HitCounter->counter?>&nbsp;Visit<?php echo (($HitCounter->counter > 1) ? 's' : '')?>&nbsp;Since&nbsp;<?php echo $HitCounter->since_date?></div>
	</div>
<!-- End Page Footer -->
</div>
<!-- End Main Container -->
<!-- Start Print Header -->
<div id="site_notice" class="print_only">
	<p class="last"><?php echo SITE_TITLE?> - <?php echo STANDARD_URL?></p>
</div>
<!-- End Print Header -->
<?php
	Console::render_debug_info();
?>
</body>
</html>