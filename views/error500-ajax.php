<?php
if (!empty($error_output['message']) && $error_output['is_debug_mode']) {	// Show message for the programmer
	echo $error_output['message'];
	if ($error_output['is_debug_mode']) {
		?>
<h3>Backtrace:</h3>
<pre style="width: 870px; overflow: auto; margin: 15px 0; background: #333; color: #ddd; padding: 10px 15px; -moz-border-radius: 10px; -webkit-border-radius: 10px; border-radius: 10px; -moz-box-shadow: inset 0 0 5px #ccc; -webkit-box-shadow: inset 0 0 5px #ccc; box-shadow: inset 0 0 5px #ccc; font-size: 14px;"><?php
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
