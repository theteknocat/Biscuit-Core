<div class="controls">
	<a href="<?php echo $PageManager->url("new")?>" class="rightfloat">New Page</a>
	Administration
</div>
<p><strong>Note:</strong> The order in which pages are listed here do not necessarily reflect the order in which they are displayed in the main menu.</p>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<th>Page Title</th>
		<th width="80">Actions</th>
	</tr>
	<?php
	$last_indent = 0;
	$last_page = end($pages);
	reset($pages);
	foreach ($pages as $index => $page) {
		$shortname_bits = explode("/",$page->shortname());
		$indent = count($shortname_bits)-1;
		$indent_str = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;',$indent);
		if ($index%2 == 0) {
			$row_class = "even";
		} else {
			$row_class = "odd";
		}
		?>
	<tr class="<?php echo $row_class ?>">
		<td><?php echo $indent_str ?><a href="<?php echo Navigation::url($page->shortname()) ?>"><?php echo $page->title() ?></a></td>
		<td width="80"><span class="admin-bttns">
			<a href="<?php echo $PageContentManager->url('edit',$page->id()) ?>" class="admin-bttn bttn-edit">Edit</a>
			<a href="<?php echo $PageManager->url("delete",$page->id()) ?>" class="admin-bttn bttn-del">Delete</a>
		</span></td>
	</tr>
		<?php
	}
	?>
</table>
<script type="text/javascript" charset="utf-8">
	PageManager.AddIndexHandlers();
</script>