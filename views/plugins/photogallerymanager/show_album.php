<h2>Album: <?php echo $PhotoGalleryManager->album->title()?></h2>
<?php if ($PhotoGalleryManager->user_can_add_photo()) {
	?><p><a href="<?php echo $PhotoGalleryManager->url('new_photo',$PhotoGalleryManager->params['id'])?>">Add Photo</a></p><?php
}
if ($PhotoGalleryManager->photos) {
	foreach ($PhotoGalleryManager->photos as $photo) {
		$finfo = $PhotoGalleryManager->file_info($photo);
		?>
			<div class="photo"><a href="<?php echo $PhotoGalleryManager->url('download', $photo) ;?>" class="lightview" rel="gallery[<?php echo $PhotoGalleryManager->album->id()?>]" title="<?php echo $photo->title() ?>"><img src="<?php echo $PhotoGalleryManager->url('thumbnail', $photo) ;?>" <?php echo $finfo['thumb_attributes'] ?> alt="<?php echo $photo->title() ?>" border="0"><br><?php echo $photo->title() ?></a>
		<?php
		if ($PhotoGalleryManager->user_can_edit_photo() || $PhotoGalleryManager->user_can_delete_photo()) {
			?><br>[<?php
			if ($PhotoGalleryManager->user_can_edit_photo()) {
				?><a href="<?php echo $PhotoGalleryManager->url('edit_photo',$photo->id()); ?>">Edit</a><?php
				if ($PhotoGalleryManager->user_can_delete_photo()) {
					?> | <?php
				}
			}
			if ($PhotoGalleryManager->user_can_delete_photo()) {
				?><a href="<?php echo $PhotoGalleryManager->url('delete_photo',$photo->id()); ?>" class="delete_photo">Delete</a><?php
			}
			?>]<?php
		}
		?></div><?php
	}
}
else { ?>
	<p>There are currently no photos in this album.</p>
<?php
}
?>
<p><a href="<?php echo $PhotoGalleryManager->url(); ?>">&larr; Gallery Home</a></p>
