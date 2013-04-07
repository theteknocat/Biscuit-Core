<?php
	if (!class_exists('Biscuit')) {
		require_once(dirname(__FILE__).'/../config/system_globals.php');
		require_once(SITE_ROOT.'/config/global.php');
		$hostname = $_SERVER['HTTP_HOST'];
		session_name(SESSION_NAME);
		session_start();
		if (!empty($_SESSION['error_output'])) {
			$error_output = $_SESSION["error_output"];
		}
	}
	if (!empty($error_output['message']) && $error_output['is_debug_mode']) {
		$content_width = 960;
	} else {
		$content_width = 700;
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta http-equiv="Style-Content-Type" content="text/css; charset=utf-8">
		<meta name="robots" content="noindex,nofollow">
		<title>Critical Error</title>

		<link rel="stylesheet" type="text/css" href="/framework/extensions/blue_print_css/css/screen.css" media="screen, projection">
		<link rel="stylesheet" type="text/css" href="/framework/extensions/blue_print_css/css/print.css" media="print">
		<link rel="stylesheet" type="text/css" href="/framework/themes/sea_biscuit/css/forms.css" media="screen, projection">
		<link rel="stylesheet" type="text/css" href="/framework/themes/sea_biscuit/css/clean-page-dialog.css" media="screen, projection">
	</head>
	<body id="clean-page-dialog">
		<div id="body-wrap" class="alert" style="width: <?php echo $content_width; ?>px">
			<h2 id="main-heading">Critical Error<?php if (!empty($error_output['message']) && $error_output['is_debug_mode']) { ?> <small>[debug mode]</small><?php } ?></h2>
			<div id="body-content">
				<?php
				if (!empty($error_output['message']) && $error_output['is_debug_mode']) {	// Show message for the programmer
					echo $error_output['message'];
					if ($error_output['is_debug_mode']) {
						?>
				<h3>Backtrace:</h3>
				<pre id="error-backtrace"><?php
				echo htmlentities($error_output['backtrace']);
						?></pre><?php
					}
				} else {		// Show a friendly message for everyone else
					?>
				<p>Something has horribly crashed and burned. Don't worry, it wasn't your fault, but it might be best if you wait a while before trying to access this page, or do the last thing you did, again.</p>
				<p><?php
				if (!empty($error_output['report_sent'])) {
					?>An error report has been sent off to the developers, who will look into the problem and fix it as soon as they can.<?php
				} else {
					?>We will look into the problem and endeavour to resolve it as soon as possible.<?php
				}
				?> We sincerely apologize for the inconvenience it has and may continue to cause in the meantime.</p>
				<h3>Want to help?</h3>
				<p>If there was something specific you were doing on the site when this error occurred and you want to help, please send an email to <a href="mailto:<?php echo TECH_EMAIL; ?>?subject=Error on <?php echo $hostname; ?>"><?php echo TECH_EMAIL; ?></a> and explain in detail <strong>exactly</strong> what you did up to the point the error occurred. It could be most helpful in troubleshooting the problem. If you did nothing, just leave it with us and rest assured we'll sort it out.</p>
					<?php
				}
				?>
				<div class="controls"><?php if (!empty($error_output['is_debug_mode'])) { ?><a href="/framework/install"><strong>Run Installer</strong></a><?php } ?><a href="/"><strong>Return to Home Page</strong></a></div>
			</div>
		</div>
		<!-- End Main Body -->
	</body>
</html>
<?php
	if (!empty($_SESSION) && !empty($_SESSION['error_output'])) {
		unset($_SESSION['error_output']);
	}
?>