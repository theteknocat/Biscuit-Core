<table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px">
	<tr>
		<td style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px">
			<h3 style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 14px; font-weight: bold">This message is to inform you of a Biscuit Application error.</h3>
			<table width="100%" cellpadding="5" cellspacing="0" border="0">
				<tr>
					<td valign="top" width="155" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px; border-bottom: 1px solid #ddd; padding: 5px;"><strong>URL:</strong></td>
					<td valign="top" style="font-family: Courier, monospace; font-size: 12px; border-bottom: 1px solid #ddd; background: #eee; padding: 5px;"><?php echo $full_url; ?></td>
				</tr>
				<tr>
					<td valign="top" width="155" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px; border-bottom: 1px solid #ddd; padding: 5px;"><strong>Date/Time:</strong></td>
					<td valign="top" style="font-family: Courier, monospace; font-size: 12px; border-bottom: 1px solid #ddd; background: #eee; padding: 5px;"><?php echo $error_date; ?></td>
				</tr>
				<tr>
					<td valign="top" width="155" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px; border-bottom: 1px solid #ddd; padding: 5px;"><strong>Active User:</strong></td>
					<td valign="top" style="font-family: Courier, monospace; font-size: 12px; border-bottom: 1px solid #ddd; background: #eee; padding: 5px;"><?php echo $username; ?></td>
				</tr>
				<?php
				if (!empty($post_data)) {
					?>
				<tr>
					<td valign="top" width="155" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px; border-bottom: 1px solid #ddd; padding: 5px;"><strong>Submitted Form Data:</strong></td>
					<td valign="top" style="font-family: Courier, monospace; font-size: 12px; border-bottom: 1px solid #ddd; background: #eee; padding: 5px;"><pre style="font-family: Courier, monospace; font-size: 12px; line-height: 20px; background: none; padding: 0; margin: 0;"><?php print_r($post_data); ?></pre></td>
				</tr>
					<?php
				}
				?>
				<tr>
					<td valign="top" width="155" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px; border-bottom: 1px solid #ddd; padding: 5px;"><strong>Error Message:</strong></td>
					<td valign="top" style="font-family: Courier, monospace; font-size: 12px; border-bottom: 1px solid #ddd; background: #eee; padding: 5px;"><?php echo $error_message; ?></td>
				</tr>
				<tr>
					<td valign="top" width="155" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px; border-bottom: 1px solid #ddd; padding: 5px;"><strong>File:</strong></td>
					<td valign="top" style="font-family: Courier, monospace; font-size: 12px; border-bottom: 1px solid #ddd; background: #eee; padding: 5px;"><?php echo $error_file; ?></td>
				</tr>
				<tr>
					<td valign="top" width="155" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px; border-bottom: 1px solid #ddd; padding: 5px;"><strong>Line:</strong></td>
					<td valign="top" style="font-family: Courier, monospace; font-size: 12px; border-bottom: 1px solid #ddd; background: #eee; padding: 5px;"><?php echo $error_line; ?></td>
				</tr>
				<tr>
					<td valign="top" width="155" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px; padding: 5px;"><strong>Backtrace:</strong></td>
					<td valign="top" style="font-size: 12px; background: #eee; font-family: Courier, monospace; font-size: 12px; padding: 5px;"><pre style="font-family: Courier, monospace; font-size: 12px; line-height: 20px; background: none; padding: 0; margin: 0;"><?php echo $backtrace; ?></pre></td>
				</tr>
			</table>
			<div style="padding: 5px 0; margin: 10px 0; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; color: #888; font-size: 12px; font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif">
				Sent from <?php echo STANDARD_URL; ?>
			</div>
		</td>
	</tr>
</table>
