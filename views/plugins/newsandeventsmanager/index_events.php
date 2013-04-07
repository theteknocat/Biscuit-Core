<?php if ($NewsAndEventsManager->user_can_create()) {
	?><p><a href="<?php echo $NewsAndEventsManager->url('new')?>">Add a new Event</a>.</p><?php
}
if ($NewsAndEventsManager->items) {
	foreach ($NewsAndEventsManager->items as $event) {
		if ($NewsAndEventsManager->user_can_edit()) {
			?><span style="float: right; margin-top: 1em">[<a href="<?php echo $NewsAndEventsManager->url('edit', $event->id()); ?>">edit</a>]</span><?php
		} ?><h2><?php echo $event->title();
			if ($event->is_expired()) {
				?> <span class="minor">[Expired]</span><?php
			}?></h2>

		<h3 class="date">Posted: <?php echo Crumbs::date_format($event->date(), 'F j, Y'); ?></h3>

		<?php echo $event->text()?>

		<?php
		$finfo = $NewsAndEventsManager->file_info($event);
		if ($finfo !== false) { ?>
	    <p>File Attachment: <a href="<?php echo  $NewsAndEventsManager->url('download', $event) ;?>" class="lightview"><?php echo $event->attachment(); ?></a> <span class="minor">(<?php echo $finfo['file_size'] ?>, <?php echo $finfo['file_date'] ?>)</span></p>
	<?php	}
	}
}
else { ?>
	<p>No events available at this time.</p>
	<?php
}
?>
