<div id="install-tabs">
	<ul>
		<li><a href="#section-install">Install</a></li>
		<li><a href="#section-help">Setup Instructions</a></li>
	</ul>
	<div id="section-install">
		<?php
		if (empty($error_messages)) {
			?>
		<div class="error"><strong><?php
			if ($has_host_config) {
				if (!$db_can_connect) {
					?>Unable to connect to the database. Please ensure the database configuration is correct.<?php
				} else if (!$db_tables_installed) {
					?>Database is missing the following data tables:</strong><?php
					echo '<ul><li>'.implode('</li><li>',$missing_table_names).'</li></ul>';
					?><strong>You must perform either a clean or import install, or populate the database manually.<?php
				}
			} else {
				?>There is no configuration for this server. Please provide all the necessary configuration information to install Biscuit now.<?php
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
				<legend>Database Setup</legend>
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
				<?php
				$install_options = array(
					array('label' => '<strong>None</strong> - The database is already populated', 'value' => 'none'),
					array('label' => '<strong>Clean</strong> - I want to setup a fresh database for a new site', 'value' => 'clean')
				);
				if ($can_import_sql) {
					$install_options[] = array('label' => '<strong>Import</strong> - I want to import an SQL file for an existing site', 'value' => 'import');
				} else {
					?><div class="notice">
						<p><strong>Note:</strong> You cannot import an SQL file for an existing site because the <em><strong>var/uploads</strong></em> directory is not writable.</p>
						<p>You can setup a new site or import your SQL file into the database manually before proceeding.</p>
					</div><?php
				}
				?>
				<p class="odd">
					<?php echo Form::radios($install_options,'data_install_type','install_data[data_install_type]','Data Installation',$installer->install_data('data_install_type'),$installer->attr_is_required('data_install_type'),$installer->attr_is_valid('data_install_type')); ?>
					<span id="data-wipe-warning" class="instructions" style="display: none"><strong>WARNING:</strong> Any existing data will be overwritten.</span>
				</p>
				<?php
				if ($can_import_sql) {
				?>
				<div id="migration-upload-field" style="display: none">
					<p class="odd">
						<?php echo Form::file('sql_file','sql_file','Select SQL File',null,true,$installer->attr_is_valid('sql_file')); ?>
					</p>
				</div>
				<?php
				}
				?>
				<script type="text/javascript">
					$(document).ready(function() {
						if ($('#attr_data_install_type1').attr('checked')) {
							$('#admin-user-setup').show();
						}
						if ($('#attr_data_install_type2').attr('checked')) {
							$('#migration-upload-field').show();
						}
						if ($('#attr_data_install_type1').attr('checked') || $('#attr_data_install_type2').attr('checked')) {
							$('#data-wipe-warning').show();
						}
						$('#attr_data_install_type0, #attr_data_install_type1').click(function() {
							if ($('#migration-upload-field').css('display') == 'block') {
								$('#migration-upload-field').slideUp('fast');
							}
						});
						$('#attr_data_install_type1').click(function() {
							if ($('#admin-user-setup').css('display') != 'block') {
								$('#admin-user-setup').slideDown('fast');
							}
						});
						$('#attr_data_install_type2').click(function() {
							if ($('#migration-upload-field').css('display') != 'block') {
								$('#migration-upload-field').slideDown('fast');
							}
						});
						$('#attr_data_install_type0, #attr_data_install_type2').click(function() {
							if ($('#admin-user-setup').css('display') == 'block') {
								$('#admin-user-setup').slideUp('fast');
							}
						});
						$('#attr_data_install_type1, #attr_data_install_type2').click(function() {
							if ($('#data-wipe-warning').css('display') != 'block') {
								$('#data-wipe-warning').show();
							}
						});
						$('#attr_data_install_type0').click(function() {
							if ($('#data-wipe-warning').css('display') == 'block') {
								$('#data-wipe-warning').hide();
							}
						});
						<?php
						if ($db_can_connect && !$db_tables_installed) {
							// If db tables are not installed, prevent selection of no DB install and ensure that other options are also not checked
							?>
						$('#attr_data_install_type0').attr('disabled',true).attr('checked', false);
						$('#attr_data_install_type1, #attr_data_install_type2').attr('checked', false);
							<?php
						}
						?>
					});
				</script>
				<div id="admin-user-setup" style="display: none;">
					<p class="even">
						<?php print Form::text('admin_username','install_data[admin_username]','Super Admin Username',$installer->install_data('admin_username'),true,$installer->attr_is_valid('admin_username'), array('autocomplete' => 'off')); ?>
					</p>
					<p class="odd">
						<?php print Form::text('admin_password','install_data[admin_password]','Super Admin Password',$installer->install_data('admin_password'),true,$installer->attr_is_valid('admin_password'), array('autocomplete' => 'off')); ?>
						<span id="pwd-strength">
							<span id="pwd-meter-container"><span id="pwd-strength-meter">&nbsp;&nbsp;<span id="pwd-strength-text"><?php echo __('Strength'); ?></span></span></span>
						</span>
						<span class="instructions">Must be at least 8 characters long. Use the strength meter as a guide to help you set a strong password. <a href="http://www.microsoft.com/security/online-privacy/passwords-create.aspx" target="_blank">Tips on creating a strong password</a></span>
						<script type="text/javascript">
							$(document).ready(function() {
							    $('#attr_admin_password').pwdstr('#pwd-strength');
							});
						</script>
					</p>
				</div>
			</fieldset>
			<fieldset>
				<legend>Server Configuration</legend>
				<p class="odd">
					<?php
					$server_types = array(
						array('label' => 'Development', 'value' => 'LOCAL_DEV'),
						array('label' => 'Staging', 'value' => 'TESTING'),
						array('label' => 'Production', 'value' => 'PRODUCTION')
					);
					print Form::select($server_types,'server_type','install_data[server_type]','Server Type',$installer->install_data('server_type'),$installer->attr_is_required('server_type'),$installer->attr_is_valid('server_type'));
					?>
					<span class="instructions"><strong>Development: </strong>Caching off, logs everything, server info bar displays at bottom, critical errors show full details on screen.<br>
						<strong>Staging: </strong>Caching on, logs errors only, server info bar displays at bottom, critical errors show full details on screen and error reports are emailed.<br>
						<strong>Production: </strong>Caching on, log errors only, no server info bar, critical errors show friendly message and error reports are emailed.<br><br>
						<strong>Note:</strong> for both <em>Development</em> and <em>Staging</em>, the mail handler will always send ALL emails to the technical contact you configure below, regardless of the actual recipients.</span>
				</p>
				<p class="even">
					<?php print Form::radios(array(array('label' => 'Sendmail', 'value' => 'no'), array('label' => 'SMTP', 'value' => 'yes')),'use_smtp','install_data[use_smtp]','Send Mail Using',$installer->install_data('use_smtp'),$installer->attr_is_required('use_smtp'),$installer->attr_is_valid('use_smtp')); ?>
				</p>
				<div id="smtp-extra-config" style="display: none">
					<p class="odd">
						<?php print Form::text('smtp_host','install_data[smtp_host]','SMTP Host',$installer->install_data('smtp_host'),true,$installer->attr_is_valid('smtp_host')); ?>
					</p>
					<p class="even">
						<?php print Form::radios(array(array('label' => 'No', 'value' => 'no'), array('label' => 'Yes', 'value' => 'yes')),'use_smtp_auth','install_data[use_smtp_auth]','Use Authentication',$installer->install_data('use_smtp_auth'),$installer->attr_is_required('use_smtp_auth'),$installer->attr_is_valid('use_smtp_auth')); ?>
					</p>
					<div id="smtp-auth-fields" style="display: none">
						<p class="odd">
							<?php print Form::text('smtp_user','install_data[smtp_user]','SMTP Username',$installer->install_data('smtp_user'),true,$installer->attr_is_valid('smtp_user')); ?>
						</p>
						<p class="even">
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
				<p class="odd">
					<?php print Form::text('email_tech_contact','install_data[email_tech_contact]','Email Technical Contact',$installer->install_data('email_tech_contact'),true,$installer->attr_is_valid('email_tech_contact')); ?>
					<span class="instructions">This will be used for sending error reports or other system notifications intended for the developer.</span>
				</p>
				<p class="even">
					<?php print Form::text('session_cookie_name','install_data[session_cookie_name]','Session Cookie Name',$installer->install_data('session_cookie_name'),true,$installer->attr_is_valid('session_cookie_name')); ?>
					<span class="instructions">This setting gives the session cookie for your site a unique name. A unique name is more secure.</span>
				</p>
			</fieldset>
			<div class="controls">
				<input type="submit" name="SubmitButton" class="SubmitButton" value="<?php if (!$has_host_config) { ?>Install<?php } else { ?>Update Configuration<?php } ?>">
			</div>
		</form>
		<script type="text/javascript">
			$(document).ready(function() {
				$('#install-form').submit(function() {
					// Cycle through all elements with the classname of "SubmitButton" and disable them
					window.top.$('#install-form .SubmitButton').each(function() {
						$(this).blur();
						$(this).attr('disabled','disabled');
						$(this).addClass('working');
						$(this).val(" ");
					});
					// Apply "busy" mouse cursor to body:
					$('body').css({
						'cursor': 'progress'
					});
				});
			});
		</script>
	</div>
	<div id="section-help">
		<h3>Before You Begin</h3>
		<ol>
			<li>Create a database and make note of the username and password. You can optionally import data from an existing SQL file if you are setting up an existing site.</li>
			<li>Make sure the <em><strong>var</strong></em> directory in the web root and all it's sub-folders and files have permissions to allow the web server to write to them.<br>
				<strong>Note:</strong> this is required if you want the automatic import option to be available in the installer. Otherwise you can do it later, but you'll have to import your database manually if you are setting up an existing site.</li>
			<li><strong>Optional:</strong> enable write permissions on the <em><strong>config</strong></em> directory. This makes for completely automated installation, but is not necessary. If it is not writable, you will be given instructions on how to manually create the necessary configuration files and provided with the code to copy and paste into them.</li>
		</ol>
	</div>
</div>
<script type="text/javascript" charset="utf-8">
	$(document).ready(function() {
		$('#install-tabs').tabs();
	});
</script>