<?php
	@session_start();
	if (!empty($_SESSION['error_info'])) {
		$error_info = $_SESSION['error_info'];
	}
	header("HTTP/1.1 500 Internal Server Error");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>500 - Internal Server Error</title>
	<link rel="stylesheet" type="text/css" href="/framework/css/blueprint/screen.css" media="screen, projection">
	<link rel="stylesheet" type="text/css" href="/framework/css/blueprint/print.css" media="print">
	<link rel="stylesheet" type="text/css" href="/framework/css/error_pages.css" media="screen">
</head>
<body>
	<div id="wrap">
		<div id="content">
			<div id="title"><h1>Error 500 - Internal Server Error</h1></div>
			<div id="body" style="overflow: auto">
<?php	if (!empty($error_info['message']) && $error_info['is_debug_mode']) {	// Show message for the programmer
?>
				<p><strong>Error Details:</strong></p>
				<p><?php echo $error_info['message']?></p>
<?php	}
			if ($error_info['is_debug_mode']) {
				echo '<pre style="font-size: 10px">'.$error_info['backtrace'].'</pre>';
			}
		else {		// Show a friendly message for everyone else
?>
				<p>We apologize for the inconvenience, but a critical internal error has occurred and the page had to be terminated. The details of the error have been logged, and a system administrator will attend to the issue as soon as possible.</p>
<?php
			if ($error_info['report_sent']) {
				?><p>A detailed report of the error has been sent to the webmaster, who will address it as soon as possible. We apologize for any inconvenience this may cause, and ask for your understanding while we resolve the problem.</p><?php
			}
			else if (!empty($error_info['contact_email'])) {
?>
				<p>If you continually receive this notice when you try to access this, please <a href="mailto:<?php echo $error_info['contact_email']; ?>">contact the webmaster</a> to report the problem.</p>
<?php
			}
		}
?>
			</div>
			<div id="footer"></div>
		</div>
	</div>
</body>
</html>
<?php
	unset($_SESSION['error_info']);
?>