<h3>Manual Configuration Required</h3>
<div class="notice">
	<p><strong>The <em><strong>config</strong></em> folder in the web root, and/or the <em><strong>config/global.php</strong></em> file, is not writable preventing me from automatically creating/updating the configuration files.</strong></p>
	<p class="last">Following is the information you need to do this manually. Once you have done that, you can <a href="/?empty_caches=1">visit your website</a>.</p>
</div>
<form name="dummy" action="#" method="get">
	<fieldset>
		<legend>Host Configuration</legend>
		<?php
		if ($update_existing) {
			?>
		<p><strong>Open this configuration file:</strong> <?php echo SITE_ROOT.$host_config_file; ?></p>
		<p><strong>Replace with the following content:</strong></p>
			<?php
		} else {
			?>
		<p><strong>Create this configuration file:</strong> <?php echo SITE_ROOT.$host_config_file; ?></p>
		<p><strong>Populate it with the following content:</strong></p>
			<?php
		}
		?>
		<p>
			<textarea name="host-configuration" rows="20" cols="100" style="width: 848px; height: 500px" readonly="readonly"><?php echo $host_configuration; ?></textarea>
		</p>
	</fieldset>
	<fieldset>
		<legend>Global Configuration</legend>
		<p><strong>Open this configuration file:</strong> <?php echo SITE_ROOT; ?>/config/global.php</p>
		<p><strong>Replace with the following content:</strong></p>
		<p>
			<textarea name="global-configuration" rows="20" cols="100" style="width: 848px; height: 500px" readonly="readonly"><?php echo $global_configuration; ?></textarea>
		</p>
	</fieldset>
</form>
<div class="controls"><a href="/"><strong>Go to my site</strong></a></div>
