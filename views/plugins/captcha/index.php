<?php
/*
	Setting Custom Attributes

	The captcha library has the same set_attribute() and set_attributes() methods as AbstractModel.  As such, setting the configuration prior to
	creating the captcha image is simple syntax:

	$Captcha->tool->set_attributes(array(
		'width'        => 400,
		'height'       => 25,
		'Xamplitude'   => 12,
		'blur'         => true
	));
	
	Refer to the property declarations at the top of the SimpleCaptcha class in /framework/plugins/captcha/captcha.php.
*/
$Captcha->tool->CreateImage();
?>
