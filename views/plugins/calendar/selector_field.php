<input type="text" name="<?php echo $fieldname?>" id="<?php echo $field_id?>" maxlength="255" value="<?php echo $default_date?>" class="date_field <?php echo $css_class?>"<?php if (!empty($attributes)) { echo " ".$attributes; } ?>>
<?php
if (!empty($custom_btn_img) && file_exists(SITE_ROOT.$custom_btn_img)) {
	Console::log("Using custom calendar button: ".$custom_btn_img);
	$img_info = getimagesize(SITE_ROOT.$custom_btn_img);
	?><input type="button" name="actuator_<?php echo $fieldname?>" id="actuator_<?php echo $field_id?>" value="Pick" src="<?php echo $custom_btn_img?>" style="width: <?php echo $img_info[0]?>; height: <?php echo $img_info[1]?>">
<?php
}
else {
	?><input type="button" name="actuator_<?php echo $fieldname?>" id="actuator_<?php echo $field_id?>" value="Pick"><?php
}
?>
<script type="text/javascript" charset="utf-8" language="javascript">
<?php
if (!Request::is_ajax()) {
?>	$(document).observe("dom:loaded",function() {
<?php
}
?>
		$('<?php echo $field_id ?>').observe("focus",function(event) {
			Event.stop(event);
			$Calendar.open('<?php echo $field_id?>','<?php echo $date_format?>');
		});
		$$('#actuator_<?php echo $field_id ?>').each(function(el) {
			el.observe("click",function(event) {
				Event.stop(event);
				$Calendar.open('<?php echo $field_id?>','<?php echo $date_format?>');
			});
		});
<?php
if (!Request::is_ajax()) {
?>	});
<?php
}
?>
</script>