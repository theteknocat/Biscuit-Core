<?php
	$item = &$PhotoGalleryManager->item;
	if ($item->id() != null) {
		$submit_url = $PhotoGalleryManager->url('edit_album',$item->id());
	}
	else {
		$submit_url = $PhotoGalleryManager->url('new_album');
	}
?>
<form name="album_editor" id="album_editor" method="post" accept-charset="utf-8" action="<?php echo $submit_url?>">
	<?php echo RequestTokens::render_token_field(); ?>
	<p><label for="item_title">Title</label><input type="text" name="album[title]" id="item_title" value="<?php echo $item->title()?>"></p>
	<p><label for="item_sort">Sort Order</label><input type="text" size="4" name="album[sort_order]" id="item_sort" value="<?php echo $item->sort_order()?>"></p>
	<p class="controls">
		<input type="submit" name="SubmitButton" id="SubmitButton" class="SubmitButton" value="Save">
<?php
	if ($item->id() != null) /* if not new */ {
		?><a href="<?php echo $PhotoGalleryManager->url('delete_album', $item->id()); ?>" class="delete_album" class="delete">Delete</a><?php
	}
	?></p>
	<p><a href="<?php echo $PhotoGalleryManager->url(); ?>">&larr; Gallery Home</a></p>
</form>
