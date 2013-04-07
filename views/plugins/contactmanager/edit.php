<?php
	$contact = &$ContactManager->contact;
?>
<form name="contact_edit_form" id="contact_edit_form" action="" method="post" accept-charset="utf-8">
	<?php echo RequestTokens::render_token_field(); ?>
	<input type="hidden" name="validation_type" value="admin_edit">
	<input type="hidden" name="primary_plugin" value="nwtsacontactmanager">
	<p><label for="first_name">First Name:</label><input type="text" class="text" name="contact_data[first_name]" id="first_name" value="<?php echo $contact->first_name() ?>" maxlength="255"></p>
	<p><label for="last_name">Last Name:</label><input type="text" class="text" name="contact_data[last_name]" id="last_name" value="<?php echo $contact->last_name() ?>" maxlength="255"></p>
	<p><label for="title">Title/Position:</label><input type="text" class="text" name="contact_data[title]" id="title" value="<?php echo $contact->title() ?>" maxlength="255"></p>
	<p><label for="company">Organization:</label><input type="text" class="text" name="contact_data[company]" id="company" value="<?php echo $contact->company() ?>" maxlength="255"></p>
	<p><label for="city">Community:</label><input type="text" class="text" name="contact_data[city]" id="city" value="<?php echo $contact->city() ?>" maxlength="255"></p>
	<p><label for="home_phone">Home Phone:</label><input type="text" class="text" name="contact_data[home_phone]" id="home_phone" value="<?php echo $contact->home_phone() ?>" maxlength="255"></p>
	<p><label for="work_phone">Work Phone:</label><input type="text" class="text" name="contact_data[work_phone]" id="work_phone" value="<?php echo $contact->work_phone() ?>" maxlength="255"></p>
	<p><label for="mobile_phone">Mobile Phone:</label><input type="text" class="text" name="contact_data[mobile_phone]" id="mobile_phone" value="<?php echo $contact->mobile_phone() ?>" maxlength="255"></p>
	<p><label for="toll_free_phone">Toll Free Phone:</label><input type="text" class="text" name="contact_data[toll_free_phone]" id="toll_free_phone" value="<?php echo $contact->toll_free_phone() ?>" maxlength="255"></p>
	<p><label for="email">Email Address:</label><input type="text" class="text" name="contact_data[email]" id="email" value="<?php echo $contact->email() ?>" maxlength="255"></p>
	<div class="controls">
		<input type="submit" value="Save" class="rightfloat SubmitButton"><a class="leftfloat" href="<?php echo $ContactManager->url('index')?>">Cancel</a>
	</div>
</form>