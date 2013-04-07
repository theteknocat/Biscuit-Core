<form name="delete-confirmation-form" action="" method="post" accept-charset="utf-8">
	<?php echo RequestTokens::render_token_field(); ?>
	<div class="controls">
		<a href="<?php echo $cancel_url ?>">Cancel</a>
		<input type="submit" name="SubmitButton" id="SubmitButton" class="SubmitButton" value="Confirm">
	</div>
</form>