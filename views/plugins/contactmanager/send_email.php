<?php
	$contact = &$ContactManager->contact;
?>
<h2>Send Email to <?php echo $contact->full_name()?></h2>
<form name="contact_form" id="contact_form" action="<?php echo $ContactManager->url('send_email',$contact->id())	?>" method="post" accept-charset="utf-8">
	<?php echo RequestTokens::render_token_field(); ?>
	<input type="hidden" name="validation_type" value="email">
	<p><label for="sender_name">Your Name:</label><input type="text" class="text" name="contact[sender_name]" id="sender_name" value="<?php echo $ContactManager->params['contact']['sender_name'] ?>" maxlength="255"></p>
	<p><label for="sender_email">Your Email:</label><input type="text" class="text" name="contact[sender_email]" id="sender_email" value="<?php echo $ContactManager->params['contact']['sender_email'] ?>" maxlength="255"></p>
	<p><label for="subject">Subject:</label><input type="text" class="text" name="contact[subject]" id="subject" value="<?php echo $ContactManager->params['contact']['subject'] ?>" maxlength="255"></p>
	<p><label for="message_body">Message:</label><textarea name="contact[message_body]" id="message_body" cols="40" rows="10"><?php echo $ContactManager->params['contact']['message_body'] ?></textarea></p>
	<p><label>&nbsp;</label><?php Captcha::render_widget()?></p>
	<p><label for="security_code">Security Code:</label><input type="text" class="text" name="security_code" id="security_code" value="" maxlength="10"></p>
	<p class="controls"><a class="leftfloat" href="<?php echo $ContactManager->url('index')?>" id="cancel-btn">Cancel</a><input type="submit" value="Send"></p>
</form>
