<?php
	if (!class_exists('Biscuit')) {
		require_once(dirname(__FILE__).'/../../config/global.php');
		session_name(SESSION_NAME);
		session_start();
		if (!empty($_SESSION['error_output'])) {
			$error_output = $_SESSION["error_output"];
		}
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta http-equiv="Style-Content-Type" content="text/css; charset=utf-8">
		<meta name="robots" content="noindex,nofollow">
		<title>Biscuit Application Error</title>
		<!-- Website icon -->
		<link rel="shortcut icon" href="/framework/themes/default/favicon.ico">

		<link rel="stylesheet" type="text/css" href="/framework/extensions/blue_print_css/css/screen.css" media="screen, projection">
		<link rel="stylesheet" type="text/css" href="/framework/extensions/blue_print_css/css/print.css" media="print">
		<link rel="stylesheet" type="text/css" href="/framework/themes/default/css/styles_screen.css" media="screen, projection">
	</head>
	<body class="error-page">
		<!-- Start Main Container -->
		<div id="header">
			<div id="banner">
				<h1>Biscuit - The Rapid Application Development Framework</h1>
			</div>
		</div>
		<div id="body-wrap">
			<div id="body" class="indexable-content">
				<h1>Application Error</h1>
				<?php
				if (!empty($error_output['message']) && $error_output['is_debug_mode']) {	// Show message for the programmer
				?>
				<div class="error">
					<?php echo $error_output['message'] ?>
				</div>

				<?php
					if ($error_output['is_debug_mode']) {
						?>
				<p><strong>Backtrace:</strong></p>
				<pre style="font-size: 12px" class="backtrace"><?php
				echo htmlentities($error_output['backtrace']);
						?></pre><?php
					}
				} else {		// Show a friendly message for everyone else
					?>
				<div class="error">
					<p>We apologize for the inconvenience, but a critical internal error has occurred and the page had to be terminated. The details of the error have been logged, and a system administrator will attend to the issue as soon as possible.</p>
					<?php
					if (!empty($error_output['report_sent'])) {
						?><p>A detailed report of the error has been sent to the webmaster, who will address it as soon as possible. We apologize for any inconvenience this may cause, and ask for your understanding while we resolve the problem.</p><?php
					} else if (!empty($error_output['contact_email'])) {
						?>
					<p>If you continually receive this notice when you try to access this, please <a href="mailto:<?php echo $error_output['contact_email']; ?>">contact the webmaster</a> to report the problem.</p>
						<?php
					}
					?>
				</div>
					<?php
				}
				?>
			</div>
			<div class="clearance">&nbsp;</div>
		</div>
		<!-- End Main Body -->
		<!-- Start Page Footer -->
		<div id="footer">
			<div id="footer-content">
				&nbsp;
			</div>
		</div>
	</body>
</html>
<?php
	if (!empty($_SESSION) && !empty($_SESSION['error_output'])) {
		unset($_SESSION['error_output']);
	}
?>