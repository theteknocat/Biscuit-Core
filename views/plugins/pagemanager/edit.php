<?php
// This form is only for creating new pages.  The PageContent plugin provides the form for updating page content
?>
<form name="page_create_form" id="page_create_form" method="post" accept-charset="utf-8" action="" class="admin">
	<?php echo RequestTokens::render_token_field(); ?>
	<div class="controls">
		<input type="submit" name="page_save_bttn1" value="Save" class="rightfloat SubmitButton">
		<a href="<?php echo $PageManager->url('index') ?>" class="leftfloat">Cancel</a>
	</div>
	<p><strong>Note:</strong> You can add content for the page after you have saved it.</p>
	<fieldset>
		<legend>Basic Page Info</legend>
		<p><label for="page_title">*Title:</label><input type="text" class="text" id="page_title" name="page_data[title]" value="<?php echo $page->title()?>"></p>
		<p><label for="page_parent">*Parent Section:</label><select name="page_data[parent]" id="page_parent">
			<?php echo $PageManager->render_parent_section_options() ?>
		</select></p>
	</fieldset>
	<div class="controls">
		<input type="submit" name="page_save_bttn2" value="Save" class="rightfloat SubmitButton">
		<a href="<?php echo $PageManager->url('index') ?>" class="leftfloat">Cancel</a>
	</div>
</form>
<script type="text/javascript" charset="utf-8">
	PageManager.AddEditHandlers();
</script>