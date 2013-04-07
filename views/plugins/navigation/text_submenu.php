<?php
for ($i=0;$i < count($menu_data);$i++) {
	if (Permissions::can_access((int)$menu_data[$i]['access_level'])) {
		echo '<a href="'.Navigation::url($menu_data[$i]['shortname']).'" id="link_'.$menu_data[$i]['shortname'].'">'.$menu_data[$i]['title'].'</a>';
		if ($i < count($menu_data)-1) {
			?> &bull; <?php
		}
	}
}
?>
