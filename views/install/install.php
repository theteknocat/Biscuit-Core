<?php
if (empty($error_messages)) {
	?>
<div class="notice"><strong>Biscuit does not appear to be fully installed on "<?php echo $host_name; ?>".<?php
	if ($has_host_config) {
		?> There is a configuration file for this host<?php
		if (!$db_can_connect) {
			?>, but I am not able to connect to the database.<?php
		} else if (!$db_tables_installed) {
			?> and I can connect to the database, but one or more of the tables needed for Biscuit are not installed:<?php
			echo '<ul><li>'.implode('</li><li>',$missing_table_names).'</li></ul>';
		}
	} else {
		?> There is no configuration file for this host.<?php
	}
	?></strong></div>
<?php
} else {
	?>
<div class="error">
	<p><strong>Please correct the following problems:</strong></p>
	<?php print $error_messages; ?>
</div>
	<?php
}
?>
<form name="install-form" id="install-form" action="" method="POST" accept-charset="UTF-8" enctype="multipart/form-data">
	<fieldset>
		<legend>Installation Type</legend>
		<?php
		if ($can_import_sql) {
		?>
		<p class="odd">
			<?php echo Form::radios(array(array('label' => 'Clean - New Website', 'value' => 'clean'), array('label' => 'Import - Existing Website', 'value' => 'migration')),'install_type','install_data[install_type]','Database',$installer->install_data('install_type'),$installer->attr_is_required('install_type'),$installer->attr_is_valid('install_type')); ?>
			<span class="instructions">A <strong>clean install</strong> is for first-time installation of a new site ONLY. <strong>Choose import</strong> if you are setting up an existing site on another server and have an export of the database.</span>
		</p>
		<div id="migration-upload-field" style="display: none">
			<p class="odd">
				<?php echo Form::file('sql_file','sql_file','SQL File',null,true,$installer->attr_is_valid('sql_file')); ?>
			</p>
		</div>
		<script type="text/javascript">
			$(document).ready(function() {
				if ($('#attr_install_type1').attr('checked')) {
					$('#migration-upload-field').show();
				}
				$('#attr_install_type0').click(function() {
					if ($('#migration-upload-field').css('display') == 'block') {
						$('#migration-upload-field').slideUp('fast');
					}
				});
				$('#attr_install_type1').click(function() {
					if ($('#migration-upload-field').css('display') != 'block') {
						$('#migration-upload-field').slideDown('fast');
					}
				});
			});
		</script>
		<?php
		} else {
			?><input type="hidden" name="install_type" value="clean">
		<div class="notice"><strong>Note:</strong> You can only perform a clean install because the uploads directory is not writable. If you need to perform a server migration install, please enable write access to the uploads folder so you can import your SQL file.</div><?php
		}
		?>
		<p class="even">
			<?php
			$server_types = array(
				array('label' => 'Development', 'value' => 'LOCAL_DEV'),
				array('label' => 'Staging', 'value' => 'TESTING'),
				array('label' => 'Production', 'value' => 'PRODUCTION')
			);
			print Form::select($server_types,'server_type','install_data[server_type]','Server Type',$installer->install_data('server_type'),$installer->attr_is_required('server_type'),$installer->attr_is_valid('server_type'));
			?>
			<span class="instructions"><strong>Development: </strong> Caching off, logs everything, server info bar displays at bottom, critical errors show full details on screen.<br>
				<strong>Staging: </strong> Caching on, logs errors only, server info bar displays at bottom, critical errors show full details on screen and error reports are emailed.<br>
				<strong>Production: </strong> Caching on, log errors only, no server info bar, critical errors show friendly message and error reports are emailed.</span>
		</p>
	</fieldset>
	<fieldset>
		<legend>Database Access</legend>
		<p class="odd">
			<?php echo Form::text('db_host','install_data[db_host]','Hostname',$installer->install_data('db_host'),$installer->attr_is_required('db_host'),$installer->attr_is_valid('db_host')) ?>
		</p>
		<p class="even">
			<?php echo Form::text('db_name','install_data[db_name]','Database Name',$installer->install_data('db_name'),$installer->attr_is_required('db_name'),$installer->attr_is_valid('db_name')) ?>
		</p>
		<p class="odd">
			<?php echo Form::text('db_username','install_data[db_username]','Username',$installer->install_data('db_username'),$installer->attr_is_required('db_username'),$installer->attr_is_valid('db_username')) ?>
		</p>
		<p class="even">
			<?php echo Form::text('db_password','install_data[db_password]','Password',$installer->install_data('db_password'),$installer->attr_is_required('db_password'),$installer->attr_is_valid('db_password')) ?>
		</p>
	</fieldset>
	<fieldset>
		<legend>Email Configuration</legend>
		<p class="odd">
			<?php print Form::radios(array(array('label' => 'Sendmail', 'value' => 'no'), array('label' => 'SMTP', 'value' => 'yes')),'use_smtp','install_data[use_smtp]','Send Mail Using',$installer->install_data('use_smtp'),$installer->attr_is_required('use_smtp'),$installer->attr_is_valid('use_smtp')); ?>
		</p>
		<div id="smtp-extra-config" style="display: none">
			<p class="even">
				<?php print Form::text('smtp_host','install_data[smtp_host]','SMTP Host',$installer->install_data('smtp_host'),true,$installer->attr_is_valid('smtp_host')); ?>
			</p>
			<p class="odd">
				<?php print Form::radios(array(array('label' => 'No', 'value' => 'no'), array('label' => 'Yes', 'value' => 'yes')),'use_smtp_auth','install_data[use_smtp_auth]','Use Authentication',$installer->install_data('use_smtp_auth'),$installer->attr_is_required('use_smtp_auth'),$installer->attr_is_valid('use_smtp_auth')); ?>
			</p>
			<div id="smtp-auth-fields" style="display: none">
				<p class="even">
					<?php print Form::text('smtp_user','install_data[smtp_user]','SMTP Username',$installer->install_data('smtp_user'),true,$installer->attr_is_valid('smtp_user')); ?>
				</p>
				<p class="odd">
					<?php print Form::text('smtp_password','install_data[smtp_password]','SMTP Password',$installer->install_data('smtp_password'),true,$installer->attr_is_valid('smtp_password')); ?>
				</p>
			</div>
		</div>
		<script type="text/javascript">
			$(document).ready(function() {
				if ($('#attr_use_smtp1').attr('checked')) {
					$('#smtp-extra-config').show();
				}
				$('#attr_use_smtp0').click(function() {
					if ($('#smtp-extra-config').css('display') == 'block') {
						$('#smtp-extra-config').slideUp('fast');
					}
				});
				$('#attr_use_smtp1').click(function() {
					if ($('#smtp-extra-config').css('display') != 'block') {
						$('#smtp-extra-config').slideDown('fast');
					}
				});
				if ($('#attr_use_smtp_auth1').attr('checked')) {
					$('#smtp-auth-fields').show();
				}
				$('#attr_use_smtp_auth0').click(function() {
					if ($('#smtp-auth-fields').css('display') == 'block') {
						$('#smtp-auth-fields').slideUp('fast');
					}
				});
				$('#attr_use_smtp_auth1').click(function() {
					if ($('#smtp-auth-fields').css('display') != 'block') {
						$('#smtp-auth-fields').slideDown('fast');
					}
				});
			});
		</script>
		<p class="even">
			<?php print Form::text('email_tech_contact','install_data[email_tech_contact]','Email Technical Contact',$installer->install_data('email_tech_contact'),true,$installer->attr_is_valid('email_tech_contact')); ?>
			<span class="instructions">This will be used for sending error reports or other system notifications intended for the developer.</span>
		</p>
	</fieldset>
	<div class="controls">
		<input type="submit" name="SubmitButton" class="SubmitButton" value="Install">
	</div>
</form>
<script type="text/javascript">
	$(document).ready(function() {
		$('#install-form').submit(function() {
			Biscuit.Crumbs.Forms.DisableSubmit('install-form');
		});
	});
</script>
