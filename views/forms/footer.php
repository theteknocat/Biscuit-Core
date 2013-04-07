<?php
$model_name = get_class($model);
if (!empty($custom_cancel_url)) {
	$cancel_url = $custom_cancel_url;
} else {
	$cancel_url = $controller->return_url($model_name);
}
if ($has_del_button) {
	$delete_action = 'delete';
	if ($model_name != $controller->primary_model()) {
		$delete_action .= '_'.AkInflector::underscore($model_name);
	}
}
?>
	<div class="controls">
		<?php
		if ($cancel_url != 'no-cancel-button') {
			?>
		<a href="<?php echo $cancel_url; ?>" id="form-cancel-bttn">Cancel</a>
			<?php
		}
		?>
		<?php
		if ($has_del_button) {
			?><a class="delete-button"<?php echo $del_rel ?> href="<?php echo $controller->url($delete_action, $model->id()); ?>">Delete</a><?php
		}
		?>
		<input type="submit" name="SubmitButton" id="SubmitButton" class="SubmitButton" value="<?php echo $submit_label ?>">
	</div>
</form>