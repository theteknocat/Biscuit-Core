<?php
if (!empty($model)) {
	$model_name = Crumbs::normalized_model_name($model);
} else {
	$model_name = null;
}
if (!empty($custom_cancel_url)) {
	$cancel_url = $custom_cancel_url;
} else {
	$cancel_url = $controller->return_url($model_name);
}
if ($has_del_button) {
	$delete_action = 'delete';
	if (!empty($model_name) && $model_name != $controller->primary_model()) {
		$delete_action .= '_'.AkInflector::underscore($model_name);
	}
}
?>
	<div class="controls">
		<?php
		if ($cancel_url != 'no-cancel-button') {
			?>
		<a class="cancel-button" href="<?php echo $cancel_url; ?>"><?php echo __("Cancel") ?></a>
			<?php
		}
		?>
		<?php
		if ($has_del_button) {
			$url_extra = '';
			if ($cancel_url != 'no-cancel-button') {
				$url_extra = '?return_url='.$cancel_url;
			}
			?><a class="delete-button"<?php echo $del_rel ?> href="<?php echo $controller->url($delete_action, $model->id()).$url_extra; ?>"><?php echo __("Delete") ?></a><?php
		}
		?>
		<input type="submit" name="SubmitButton" class="SubmitButton" value="<?php echo __($submit_label) ?>">
	</div>
</form>