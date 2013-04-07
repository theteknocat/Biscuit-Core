<?php
	$item = &$PhotoGalleryManager->item;
	$album = &$PhotoGalleryManager->album;
	if ($item->id() != null) {
		$submit_url = $PhotoGalleryManager->url('edit_photo',$item->id());
	}
	else {
		$submit_url = $PhotoGalleryManager->url('new_photo',$PhotoGalleryManager->params['album_id']);
	}
?>
<form name="photo_editor" id="photo_editor" method="post" accept-charset="utf-8" action="<?php echo $submit_url?>" enctype="multipart/form-data">
	<?php echo RequestTokens::render_token_field(); ?>
	<input type="hidden" name="MAX_FILE_SIZE" value="5000000">
	<input type="hidden" name="album_id" value="<?php echo $album->id() ?>">
	<p><label for="item_title">Title:</label><input type="text" name="photo[title]" id="item_title" value="<?php echo $item->title()?>"></p>
	<p><label for="item_description">Description:</label><textarea name="photo[description]" id="item_description" rows="6" cols="20"><?php echo $item->description()?></textarea></p>
	<p><label for="item_sort">Sort Order:</label><input type="text" size="4" name="photo[sort_order]" id="item_sort" value="<?php echo $item->sort_order()?>"></p>
<?php
	$finfo = $PhotoGalleryManager->file_info($item);
	if ($finfo !== false) { 
		?>
		<p>
			<label>Current Photo:</label>
			<a href="<?php echo $PhotoGalleryManager->url('download', $item) ;?>" class="lightview"><?php echo $item->filename(); ?></a> <span class="minor">(<?php echo  $finfo['file_size'] ?>, <?php echo  $finfo['file_date'] ?>)</span>
		</p>
		<p>
			<label for="photo_file">New Photo:</label>		
			<input type="file" name="photo_filename" value="" id="photo_file"><br>
			You can upload JPEG, GIF and PNG images up to 5MB.
		</p>
<?php
	}
	else {
		?>
		<p>
			<label for="photo_file">Photo:</label>
			<input type="file" name="photo_filename" value="" id="photo_file"><br>
			You can upload JPEG, GIF and PNG images up to 5MB.
		</p>
<?php
	}
	?><p class="controls">
		<input type="submit" name="SubmitButton" id="SubmitButton" class="SubmitButton" value="Save">
<?php
	if ($item->id() != null) /* if not new */ {
		?><a href="<?php echo $PhotoGalleryManager->url('delete_photo', $item->id()); ?>" class="delete_photo" class="delete">Delete</a><?php
	}
	?></p>
	<p><a href="<?php echo $PhotoGalleryManager->url('show_album',$album->id()); ?>">&larr; <?php echo $album->title()?></a></p>
</form>
