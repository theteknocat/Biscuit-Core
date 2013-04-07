<?php
// Data needed for drop-downs:
$months = array("January","February","March","April","May","June","July","August","September","October","November","December");
$curr_year = (int)Crumbs::date_format($Calendar->cal_date,"Y");
$this_year = (int)Crumbs::date_format("","Y");
$start_year = $curr_year-20;
$end_year = $curr_year+20;
?>
<div id="calendar_contents">
    <div id="calendar-loading" style="display: none">One moment please...</div>
	<table id="calendar-table" border="0" cellpadding="0" cellspacing="0" width="100%" class="calendar">
	<?php	if ($Calendar->calendar_type == "popup") {
			// Show the "Pick a Date" title
	?>
		<tr class="cal_titlebar">
			<td colspan="7" style="padding: 2px" align="left"><input type="image" src="/framework/images/Calendar/cal_close_bttn.gif" onMouseOver="this.src='/framework/images/Calendar/cal_close_bttn_over.gif'" onMouseOut="this.src='/framework/images/Calendar/cal_close_bttn.gif'" onClick="$Calendar.close();return false" style="float: right; margin-top: 2px; margin-right: 2px"><b>Pick a Date:</b></td>
		</tr>
	<?php	}
	?>
		<tr class="cal_title">
			<td colspan="7" style="padding: 2px">
				<div>
					<input type="image" src="/framework/images/Calendar/calbutton_prev_off.gif" style="width: 26px; height: 16px; cursor: default; float: left" value="&lt;" name="prev_month" onMouseDown="this.src='/framework/images/Calendar/calbutton_prev_on.gif'" onClick="return false" onMouseUp="this.src='/framework/images/Calendar/calbutton_prev_off.gif';$Calendar.change('<?php echo $Calendar->div_id?>','<?php echo $Calendar->calendar_type?>','<?php echo $Calendar->sel_mode?>','<?php echo $Calendar->prev_date?>','<?php echo $Calendar->sel_date?>','<?php echo $Calendar->output_format?>','<?php echo $Calendar->form_fieldname?>')">
					<input type="image" src="/framework/images/Calendar/calbutton_next_off.gif" style="width: 26px; height: 16px; cursor: default; float: right" class="small" value="&gt;" name="next_month" onMouseDown="this.src='/framework/images/Calendar/calbutton_next_on.gif'" onClick="return false" onMouseUp="this.src='/framework/images/Calendar/calbutton_next_off.gif';$Calendar.change('<?php echo $Calendar->div_id?>','<?php echo $Calendar->calendar_type?>','<?php echo $Calendar->sel_mode?>','<?php echo $Calendar->next_date?>','<?php echo $Calendar->sel_date?>','<?php echo $Calendar->output_format?>','<?php echo $Calendar->form_fieldname?>')">
					<img src="/framework/images/spacer.gif" width="9" height="9" border="0">
					<select name="picker_month" id="picker_month" class="formbox" onChange="$Calendar.change('<?php echo $Calendar->div_id?>','<?php echo $Calendar->calendar_type?>','<?php echo $Calendar->sel_mode?>',$F('picker_year')+'-'+$F('picker_month')+'-01','<?php echo $Calendar->sel_date?>','<?php echo $Calendar->output_format?>','<?php echo $Calendar->form_fieldname?>','<?php echo $cal_page?>')">
<?php
			for ($i=0;$i < 12;$i++) {
				$val = ((($i+1) < 10) ? "0" : "").($i+1);
?>
						<option value="<?php echo ($val)?>"<?php
				if ($Calendar->calmonth == $val) {
					echo " selected";
				}
						?>><?php echo $months[$i]?></option>
<?php
			}
?>
					</select>
					<img id="<?php echo $Calendar->div_id?>_tr_placeholder" src="/framework/images/spacer.gif" width="9" height="9" border="0" style="display: inline-block"><img id="<?php echo $Calendar->div_id?>_throbber" style="display: none" src="/framework/images/Calendar/cal_div_tr_on.gif" width="9" height="9" border="0">
				</div>
				<div>
					<input type="image" src="/framework/images/Calendar/calbutton_prev_off.gif" style="width: 26px; height: 16px; cursor: default; float: left" value="&lt;" name="prev_month" onMouseDown="this.src='/framework/images/Calendar/calbutton_prev_on.gif'" onClick="return false" onMouseUp="this.src='/framework/images/Calendar/calbutton_prev_off.gif';$Calendar.change('<?php echo $Calendar->div_id?>','<?php echo $Calendar->calendar_type?>','<?php echo $Calendar->sel_mode?>','<?php echo $Calendar->prev_year_date?>','<?php echo $Calendar->sel_date?>','<?php echo $Calendar->output_format?>','<?php echo $Calendar->form_fieldname?>')">
					<input type="image" src="/framework/images/Calendar/calbutton_next_off.gif" style="width: 26px; height: 16px; cursor: default; float: right" class="small" value="&gt;" name="next_month" onMouseDown="this.src='/framework/images/Calendar/calbutton_next_on.gif'" onClick="return false" onMouseUp="this.src='/framework/images/Calendar/calbutton_next_off.gif';$Calendar.change('<?php echo $Calendar->div_id?>','<?php echo $Calendar->calendar_type?>','<?php echo $Calendar->sel_mode?>','<?php echo $Calendar->next_year_date?>','<?php echo $Calendar->sel_date?>','<?php echo $Calendar->output_format?>','<?php echo $Calendar->form_fieldname?>')">
					<select name="picker_year" id="picker_year" class="formbox" onChange="$Calendar.change('<?php echo $Calendar->div_id?>','<?php echo $Calendar->calendar_type?>','<?php echo $Calendar->sel_mode?>',$F('picker_year')+'-'+$F('picker_month')+'-01','<?php echo $Calendar->sel_date?>','<?php echo $Calendar->output_format?>','<?php echo $Calendar->form_fieldname?>','<?php echo $cal_page?>')">
<?php
			for ($i = $end_year; $i >= $start_year; $i--) {
?>
						<option value="<?php echo $i?>"<?php
				if ($Calendar->calyear == $i) {
					echo "selected";
				}
						?>><?php echo $i?></option>
<?php
			}
?>
					</select>
				</div>
			</td>
		</tr>
		<tr class="cal_heading">
			<th align="center" style="padding: 2px"><b>S</b></td>
			<th align="center" style="padding: 2px"><b>M</b></td>
			<th align="center" style="padding: 2px"><b>T</b></td>
			<th align="center" style="padding: 2px"><b>W</b></td>
			<th align="center" style="padding: 2px"><b>T</b></td>
			<th align="center" style="padding: 2px"><b>F</b></td>
			<th align="center" style="padding: 2px"><b>S</b></td>
		</tr>
	<?php	$i = 0;
			foreach ($Calendar->weeks as $key => $week) {	// Iterate through the weeks array
	?>
		<tr id="<?php echo $Calendar->week_info[$key]['id']?>" class="<?php echo $Calendar->week_info[$key]['css_class']?>"<?php echo $Calendar->week_info[$key]['event_handlers']?>>
	<?php	foreach ($week as $index => $d) {		// Iterate through the week's array of days
	?>
			<td id="<?php echo $Calendar->day_info[$i]['id']?>" class="<?php echo $Calendar->day_info[$i]['css_class']?>"<?php echo $Calendar->day_info[$i]['event_handlers']?>>
				<div class="<?php echo $Calendar->day_info[$i]['content_class']?>"><?php echo $d?></div>
			</td>
	<?php	$i++;
				}
	?>
		</tr>
	<?php	}
	?>
	</table>
</div>
