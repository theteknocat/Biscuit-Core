<?php
for ($i=0;$i < count($menu_data);$i++) {
	if (Permissions::can_access((int)$menu_data[$i]['access_level'])) {
		echo '<a href="'.Navigation::url($menu_data[$i]['shortname']).'" id="link_'.$menu_data[$i]['shortname'].'">'.$menu_data[$i]['title'].'</a>';
		if ($i < count($menu_data)-1) {
			?> &bull; <?php
		}
	}
}
?> &bull; <?php
if (Authenticator::user_is_logged_in()) {
	echo '<a href="'.Navigation::logout_url($Biscuit->page_name).'">Logout</a>';
}
else {
	?><a href="/login?ref_page=/<?php echo $Biscuit->full_page_name; ?>" id="login">Login</a><?php
}
?>
