<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta http-equiv="Style-Content-Type" content="text/css; charset=utf-8">
		<meta name="robots" content="noindex,nofollow">
		<title><?php print $page_title; ?></title>
		<!-- Website icon -->
		<link rel="shortcut icon" href="/framework/themes/sea_biscuit/favicon.ico">

		<link rel="stylesheet" type="text/css" href="/framework/extensions/blue_print_css/css/screen.css" media="screen, projection">
		<link rel="stylesheet" type="text/css" href="/framework/extensions/blue_print_css/css/print.css" media="print">
		<link rel="stylesheet" type="text/css" href="/framework/themes/sea_biscuit/css/forms.css" media="screen, projection">
		<link rel="stylesheet" type="text/css" href="/framework/themes/sea_biscuit/css/jquery-ui.css" media="screen, projection">
		<link rel="stylesheet" type="text/css" href="/framework/themes/sea_biscuit/css/clean-page-dialog.css" media="screen, projection">
		<link rel="stylesheet" type="text/css" href="/framework/modules/authenticator/css/pwd_strength.css" media="screen, projection">
		<style type="text/css" media="screen">
			#attr_admin_password {
				float: left;
			}
		</style>
		<script type="text/javascript" charset="utf-8" src="/framework/js/jquery.min.js"></script>
	</head>
	<body id="clean-page-dialog">
		<div id="body-wrap">
			<h2 id="main-heading"><?php print $page_title; ?></h2>
			<div id="body-content">
				<?php print $page_content; ?>
			</div>
		</div>
		<script type="text/javascript" charset="utf-8" src="/framework/js/jquery-ui.min.js"></script>
		<script type="text/javascript" charset="utf-8" src="/framework/views/install/pwd_strength.js"></script>
	</body>
</html>
