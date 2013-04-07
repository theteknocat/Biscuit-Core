<table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px">
	<tr>
		<td style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px">
			<h3 style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 14px; font-weight: bold">This message is to inform you of a Biscuit Application error.</h3>
			<table width="100%" cellpadding="5" cellspacing="0" border="0">
				<tr>
					<td valign="top" width="100" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px"><strong>URL:</strong></td>
					<td valign="top" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px"><?php echo $full_url; ?></td>
				</tr>
				<tr>
					<td valign="top" width="100" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px"><strong>Date/Time:</strong></td>
					<td valign="top" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px"><?php echo $error_date; ?></td>
				</tr>
				<tr>
					<td valign="top" width="100" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px"><strong>Active User:</strong></td>
					<td valign="top" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px"><?php echo $username; ?></td>
				</tr>
				<tr>
					<td valign="top" width="100" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px"><strong>File:</strong></td>
					<td valign="top" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px"><?php echo $error_file; ?></td>
				</tr>
				<tr>
					<td valign="top" width="100" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px"><strong>Line:</strong></td>
					<td valign="top" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px"><?php echo $error_line; ?></td>
				</tr>
				<tr>
					<td valign="top" width="100" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px"><strong>Error Message:</strong></td>
					<td valign="top" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px"><?php echo $error_message; ?></td>
				</tr>
				<tr>
					<td valign="top" width="100" style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif; font-size: 12px"><strong>Backtrace:</strong></td>
					<td valign="top" style="font-size: 12px; background: #eee; font-family: Courier, monospace; font-size: 12px;"><pre style="font-family: Courier, monospace; font-size: 12px; line-height: 20px; background: none; padding: 5px; margin: 0;"><?php echo $backtrace; ?></pre></td>
				</tr>
			</table>
			<div style="padding: 5px 0; margin: 10px 0; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; color: #888; font-size: 12px; font-family: 'Lucida Grande', 'Lucida Sans Unicode', sans-serif">
				Sent from <?php echo STANDARD_URL; ?>
			</div>
		</td>
	</tr>
</table>
