<?php if ($NewsAndEventsManager->user_can_create()) {
	?><p><a href="<?php echo $NewsAndEventsManager->url('new')?>">Add a new News item</a>.</p><?php
}
if ($NewsAndEventsManager->items) {
	foreach ($NewsAndEventsManager->items as $news_item) {
		if ($NewsAndEventsManager->user_can_edit()) {
			?><span style="float: right; margin-top: 1em">[<a href="<?php echo $NewsAndEventsManager->url('edit', $news_item->id()); ?>">edit</a> | <a href="<?php echo $NewsAndEventsManager->url('delete', $news_item->id()); ?>" class="delete_item">delete</a>]</span><?php
		} ?><h2><?php echo $news_item->title();
			if ($news_item->is_expired()) {
				?> <span class="minor">[Expired]</span><?php
			}?></h2>

		<h3 class="date">Posted: <?php echo Crumbs::date_format($news_item->date(), 'F j, Y'); ?></h3>

		<?php echo $news_item->text()?>

		<?php
		$finfo = $NewsAndEventsManager->file_info($news_item);
		if ($finfo !== false) { ?>
	    <p>File Attachment: <a href="<?php echo  $NewsAndEventsManager->url('download', $news_item) ;?>" class="lightview"><?php echo $news_item->attachment(); ?></a> <span class="minor">(<?php echo $finfo['file_size'] ?>, <?php echo $finfo['file_date'] ?>)</span></p>
	<?php	}
	}
}
else { ?>
	<p>No news available at this time.</p>
	<?php
}
?>
