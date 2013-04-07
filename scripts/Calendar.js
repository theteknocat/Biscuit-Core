/**
 * Calendar widget controller. Required by Calendar plugin.
 *
 * @version $Id: Calendar.js 480 2007-12-03 23:40:46Z peter $
 * @copyright Kellett Communications, April 3, 2008
 * @package Plugins
 * @author Peter Epp
 *
 */
var $Calendar = {
	// Configuration
	field_id: '',
	field_val: '',
	shadow_opacity_start: 0.4,
	shadow_opacity_step: 0.1,
	is_opening: false,
	is_open: false,
	// Control Functions
	open: function(field_id,date_format) {
		if (!this.is_opening && !this.is_open) {
			this.opening = true;
			this.field_id = field_id;
			this.field_val = $F(field_id);
			$(field_id).value = 'One moment...';
			$(field_id).disabled = true;
			if (date_format == undefined || date_format == "") {
				date_format = "d-M-Y";
			}
			if (!$('calendar_div')) {
				// Insert the calendar container div at the top of the body if it's not already there
				$$('body')[0].insert({
					'top': '<div id="calendar_div" style="display: none"></div>'
				});
			}
			// Note: when opening with AJAX, callback is always set_cal_date()
			new Ajax.Updater('calendar_div','/index.php',{
				method: 'get',
				requestHeaders: Biscuit.Ajax.RequestHeaders('update'),
				parameters: {
					page: 'date_selector',
					cal_type: 'popup',
					div_id: 'calendar_div',
					output_format: date_format,
					cal_date: this.field_val,
					sel_date: this.field_val,
					fieldname: this.field_id
				},
				evalScripts: true,
				onComplete: function() {
					$Calendar.is_opening = false;
					$($Calendar.field_id).blur();
					var dimensions	= $($Calendar.field_id).getDimensions();
					var normal_pos	= $($Calendar.field_id).cumulativeOffset();
					//var scroll_pos	= $($Calendar.field_id).cumulativeScrollOffset();
					var real_x_pos	= normal_pos[0];
					var real_y_pos	= normal_pos[1];
					$('calendar_div').setStyle( {
						left: real_x_pos+"px",
						top: real_y_pos+dimensions.height +"px"
					});
					new Effect.Appear('calendar_div',{duration: 0.5,afterFinish: function() {
						$($Calendar.field_id).value = $Calendar.field_val;
						$($Calendar.field_id).disabled = false;
						$Calendar.is_open = true;
					}});
				}
			});
		}
	},
	close: function() {
		$Calendar.is_open = false;
		new Effect.Fade('calendar_div',{duration: 0.5,afterFinish: function() {
			$('calendar_div').update('');
			$('calendar_div').hide();
		}});
	},
	set_date: function(field_id,date) {
		// This function is for AJAX popup calendars to set the value of the date form field to the selected date
		$(field_id).value = date;
		this.close('calendar_div');
	},
	change: function(div_id,cal_type,sel_mode,cal_date,sel_date,date_format,fieldname) {
		// Change the calendar to a new month or year
		if (fieldname == undefined || fieldname == "") {
			fieldname = '';
		}
		new Ajax.Updater(div_id,'/index.php',{
			method: 'get',
			requestHeaders: Biscuit.Ajax.RequestHeaders('update'),
			parameters: {
				page: 'date_selector',
				cal_type: cal_type,
				sel_mode: sel_mode,
				div_id: div_id,
				output_format: date_format,
				cal_date: cal_date,
				sel_date: sel_date,
				fieldname: fieldname
			},
			evalScripts: true,
			onCreate: function() {
			    $('calendar-loading').show();
			    $('calendar-table').hide();
                // $(div_id+"_tr_placeholder").hide();  // Hide the placeholder
                // $(div_id+"_throbber").show();        // Show the throbber for the current calendar
			}
		});
	},
	// Customizable Inline Calendar Callback Functions
	//
	// To customize these functions:
	//
	// 1.	Make a new JS file in your project's "scripts" folder
	// 2.	Include it in the page template after all plugin JS files have been included
	// 3.	Insert one or both of the following function defintions into it and fill them with your custom scripts:
	//
	//		$Calendar.week_select = function(date) {
				// Enter your custom script here
	//		}
	//		$Calendar.day_select = function(date) {
				// Enter your custom script here
	//		}
	//
	week_select: function(date) {
		// Customize this code
		alert('You selected the week of '+date);
	},
	day_select: function(date) {
		// Customize this code
		alert('You selected '+date);
	}
}
