<div class="notice"><strong>Database was successfully installed, however I was not able to create configuration files for you since the config directory is not writable.</strong> Here is the information you need to create the configuration files manually. Once you have done this your site will be ready to use.</div>
<form name="dummy" action="#" method="get">
	<fieldset>
		<legend>Host Configuration</legend>
		<p><strong>Create this configuration file:</strong> [webroot]<strong><?php echo $host_config_file; ?></strong></p>
		<p><strong>Paste in the following content:</strong></p>
		<p>
			<textarea name="host-configuration" rows="20" cols="100" style="width: 888px; height: 500px" readonly="readonly"><?php echo $host_configuration; ?></textarea>
		</p>
	</fieldset>
	<fieldset>
		<legend>Global Configuration</legend>
		<p><strong>Open this configuration file:</strong> [webroot]<strong>/config/global.php</strong></p>
		<p><strong>Replace with the following content:</strong></p>
		<p>
			<textarea name="global-configuration" rows="20" cols="100" style="width: 888px; height: 500px" readonly="readonly"><?php echo $global_configuration; ?></textarea>
		</p>
	</fieldset>
</form>
