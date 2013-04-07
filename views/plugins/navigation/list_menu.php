<ul>
<?php
// Iterate through the menu items:
for ($i=0;$i < count($page_data);$i++) {
	$menu_item = $page_data[$i];
?>
	<li><a href="<?php echo Navigation::url($menu_item['shortname']); ?>"<?php echo $link_extra?>><?php echo $menu_item['title']; ?></a>
<?php
	$this->render_list_menu($menu_item['id']);
?></li><?php
}
// Close the bulleted list:	?>
</ul>
