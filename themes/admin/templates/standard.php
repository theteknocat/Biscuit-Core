<?php
	/**
	 * Biscuit Template. You can use this as the starting point for new sites.
	 *
	 * If using the SiteSearch module, ensure that you put a class of "indexable-content" on the element containing the content you want indexed. Note that only the first
	 * element with a class of "indexable-content" will get indexed.
	 *
	 * @author Peter Epp
	 */
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
	<?php print $header_tags; ?>

	<?php print $header_includes; ?>

	</head>
	<body id="<?php echo $body_id ?>">
		<!-- Start Main Container -->
		<div id="header">
			<div id="banner">
				<h1>Biscuit - The Rapid Application Development Framework</h1>
				<div id="login-box">
					<?php print $login_link; ?>
				</div>
			</div>
		</div>
		<div id="body-wrap">
			<div id="left-bar">
				<h2>Main Menu</h2>
				<?php print $list_menu; ?>
			</div>
			<div id="body" class="indexable-content">
				<?php print $user_messages; ?>
				<p class="small"><?php print $breadcrumbs ?></p>
				<h1><?php print $page_title; ?></h1>
				<?php print $page_content; ?>
			</div>
			<div class="clearance">&nbsp;</div>
		</div>
		<!-- End Main Body -->
		<!-- Start Page Footer -->
		<div id="footer">
			<div id="footer-content">
				<div id="copyright"><?php print $copyright_notice; ?></div>
				<div id="hitcounter"><?php print $hit_counter; ?></div>
				<?php print $text_mainmenu; ?>
			</div>
		</div>
		<?php
			print $footer;
		?>
		<!-- End Page Footer -->
	</body>
</html>