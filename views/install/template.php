<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta http-equiv="Style-Content-Type" content="text/css; charset=utf-8">
		<meta name="robots" content="noindex,nofollow">
		<title><?php print $page_title; ?></title>
		<!-- Website icon -->
		<link rel="shortcut icon" href="/framework/themes/default/favicon.ico">

		<link rel="stylesheet" type="text/css" href="/framework/extensions/blue_print_css/css/screen.css" media="screen, projection">
		<link rel="stylesheet" type="text/css" href="/framework/extensions/blue_print_css/css/print.css" media="print">
		<link rel="stylesheet" type="text/css" href="/framework/themes/default/css/styles_screen.css" media="screen, projection">
		<link rel="stylesheet" type="text/css" href="/framework/themes/default/css/forms.css" media="screen, projection">
		<script type="text/javascript" charset="utf-8" src="/framework/js/jquery.min.js"></script>
	</head>
	<body class="single-column">
		<!-- Start Main Container -->
		<div id="header">
			<div id="banner">
				<h1><a name="page-title">Biscuit - The Rapid Application Development Framework</a></h1>
			</div>
		</div>
		<div id="body-wrap">
			<div id="body-top"></div>
			<div id="body" class="indexable-content">
				<h2><?php print $page_title; ?></h2>
				<?php print $page_content; ?>
			</div>
			<div class="clearance">&nbsp;</div>
		</div>
		<!-- End Main Body -->
		<!-- Start Page Footer -->
		<div id="footer">
			<div id="copyright">Biscuit <?php echo Biscuit::version(); ?> Copyright &copy; <?php echo date('Y'); ?> Peter Epp</div>
		</div>
		<!-- End Page Footer -->
		<script type="text/javascript" charset="utf-8" src="/framework/js/jquery-ui.min.js"></script>
		<script type="text/javascript" charset="utf-8" src="/framework/js/jquery.i18n.properties.min.js"></script>
		<script type="text/javascript" charset="utf-8" src="/framework/js/framework.js"></script>
	</body>
</html>
