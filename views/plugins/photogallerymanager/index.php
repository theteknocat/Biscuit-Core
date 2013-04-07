<?php if ($PhotoGalleryManager->user_can_create_album()) {
	?><p><a href="<?php echo $PhotoGalleryManager->url('new_album')?>">Create New Album</a></p><?php
}
if ($PhotoGalleryManager->albums) {
	foreach ($PhotoGalleryManager->albums as $album) {
		?>
			<div class="album"><a href="<?php echo $PhotoGalleryManager->url('show_album', $album->id()); ?>"><?php echo $album->title() ?></a>
		<?php
		if ($PhotoGalleryManager->user_can_edit_album() || $PhotoGalleryManager->user_can_delete_album()) {
			?><br>[<?php
			if ($PhotoGalleryManager->user_can_edit_album()) {
				?><a href="<?php echo $PhotoGalleryManager->url('edit_album', $album->id()); ?>">Edit</a><?php
				if ($PhotoGalleryManager->user_can_delete()) {
					?> | <?php
				}
			}
			if ($PhotoGalleryManager->user_can_delete_album()) {
				?><a href="<?php echo $PhotoGalleryManager->url('delete_album', $album->id()); ?>" class="delete_album">Delete</a><?php
			}
			?>]<?php
		}
		?></div><?php
	}
}
else { ?>
	<p>There are currently no albums in the gallery.</p>
<?php
}
?>
