<img src="<?php echo Captcha::url()?>" border="0" alt="Security Captcha" id="captcha_image">
<a href="#refresh_captcha" id="captcha_refresh_button">Refresh</a>
<script type="text/javascript" charset="utf-8" language="javascript">
	$('captcha_refresh_button').observe('click',function(event) {
		Event.stop(event);
		// Generate a new timestamp to use for the captcha url:
		var currTime = new Date();
		var now = currTime.getTime();
		// Set the image source to the new captcha url:
		$('captcha_image').src = "/captcha/"+now;
	});
</script>
