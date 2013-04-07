<?php
	$event_item = &$NewsAndEventsManager->item;
?>
<form name="news_event_edit_form" id="news_event_edit_form" method="post" accept-charset="utf-8" action="" enctype="multipart/form-data" class="admin">
<?php echo RequestTokens::render_token_field(); ?>
<p>
  <label for="item_title">Title</label><input type="text" name="news_event[title]" maxlength="255" id="item_title" value="<?php echo $event_item->title()?>" class="formbox"></p>
<p>
  <label for="item_text">Details</label><textarea name="news_event[text]" id="item_text" cols="60" rows="20" class="formbox"><?php echo $event_item->text()?></textarea>
</p>
<p><label for="item_date">Date</label><?php echo $Calendar->render_date_field('news_event[date]',array("default_date" => $event_item->date(),"field_id" => "item_date"))?>
</p>
<p><label for="item_expiry">Expiry Date</label><?php echo $Calendar->render_date_field('news_event[expiry]',array("default_date" => $event_item->expiry(),"field_id" => "item_expiry"))?><span class="minor">(Optional)</span>
</p>
<?php
	$finfo = $NewsAndEventsManager->file_info($event_item);
	if ($finfo !== false) { 
		?><p>
			<label>Current File: </label>
			<a href="<?php echo $NewsAndEventsManager->url('download', $event_item) ;?>" class="lightview"><?php echo $event_item->attachment(); ?></a> <span class="minor">(<?php echo  $finfo['file_size'] ?>, <?php echo  $finfo['file_date'] ?>)</span>
		</p>
		<p><input type="checkbox" name="news_event[remove_attachment]" id="remove_attachment" value="1"><label for="remove_attachment">Remove Attachment</label></p>
		<p>
			<label for="file_attachment">New File</label>
			<input type="file" name="item_filename" value="" id="file_attachment"><span class="minor">(optional)</span>
		</p>
<?php
	}
	else {
		?><p>
		  <label for="file_attachment">File Attachment</label>
		  <input type="file" name="item_filename" value="" id="file_attachment"><span class="minor">(optional)</span>
		</p>
<?php
	}
	?><p class="controls">
		<input type="submit" name="SubmitButton" id="SubmitButton" class="SubmitButton" value="Save">
<?php
	if ($event_item->id() != null) /* if not new */ {
		?><a href="<?php echo $NewsAndEventsManager->url('delete', $event_item->id()); ?>" id="delete_item" class="delete">delete</a><?php
	}
	?></p>
	<p><a href="<?php echo $NewsAndEventsManager->url('index'); ?>">&larr; Event List</a></p>
</form>
<script language="javascript" type="text/javascript">
	document.observe("dom:loaded",function() {
		Biscuit.Session.KeepAlive.init_form_observer();
	});
</script>
