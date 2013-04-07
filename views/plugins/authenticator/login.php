<form action="" method="POST" accept-charset="utf-8">
	<?php echo RequestTokens::render_token_field(); ?>
	<input type="hidden" name="action" value="login" id="action">
<?php
	if (Session::flash_isset('login_redirect')) {
?>
	<input type="hidden" name="login_redirect" value="<?php echo Session::flash_get('login_redirect')?>">
<?php
	}
?>
	<p>
		<label>Username: <input type="text" name="login_info[username]" value="" id="login_info_username"></label>
	</p>
	<p>
		<label>Password: <input type="password" name="login_info[password]" value="" id="login_info_password"></label>
	</p>

	<p><input type="submit" value="Login &rarr;"></p>
</form>