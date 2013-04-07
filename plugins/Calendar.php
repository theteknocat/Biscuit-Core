<?php
/**
 * Standalone calendar widget allowing for day and week selection and custom Javascript callback function.  To use it as a date selector requires the existence
 * of a page called "date_selector" in the page_index table. This ensures that the calendar only renders when called for specifically, so it cannot interfere with
 * the rendering of other AJAX requests.
 *
 * @version $Id: calendar.php 480 2007-12-03 23:40:46Z lee $
 * @copyright Kellett Communications, November 14, 2007
 * @package Plugins
 * @author Peter Epp
 *
 * Dependent files:
 * Calendar.css
 * Calendar.js
 *
 * If calling this only as a popup in a page (not using inline), then the js and css files must be registered by the appropriate plugin.
 * Example found in news and events
 *
 **/
class Calendar extends AbstractPluginController {
	/**
	 * Other plugins this one depends on
	 *
	 * @var array
	 */
	var $dependencies = array('Prototype');
	/**
	 * An array of the offsets of each day from the beginning of the week.  Defined in the constructor function
	 *
	 * @var array
	 */
	var $offset;
	/**
	 * The day or week (depending on selecton mode) to be pre-selected in the calendar. Passed as an argument to the constructor function, or defaults to today's date.
	 *
	 * @var string
	 */
	var $sel_date;
	/**
	 * Either "day" or "week" to indicate whether the user should be able to select an entire week or just one day at a time.  Passed as an argument to the constructor function
	 *
	 * @var string
	 */
	var $selection_mode;
	/**
	 * Any date in YYYY-MM-DD format defining which month the calendar will display.  Defaults to today's date.
	 *
	 * @var string
	 */
	var $cal_date;
	/**
	 * An array of dates that contain one or more events.  A dot will show up on these dates in the calendar indicating that there is something on that day.
	 *
	 * @var array
	 */
	var $cal_events = array();

	function run($params = array()) {
		if ($this->dependencies_met()) {
			$this->Biscuit->register_js('Calendar.js');
			$this->Biscuit->register_css(array('filename' => 'Calendar.css','media' => 'screen'));
			parent::run($params);
		}
		else {
			Console::log("                        Calendar died because it cannot live without Web 2.0");
		}
	}

	function action_index() {
		if (Request::is_ajax() && $this->Biscuit->page_name == 'date_selector') {	// Render only on ajax requests to the right page
			// Configure and render the calendar:
			$this->configure();
			$this->render();
		}
	}
	/**
	 * Setup the properties needed by other functions of the class
	 *
	 * @return void
	 * @param string $cal_date Refer to $cal_date above
	 * @param string $sel_mode Refer to $sel_mode above
	 * @param string $this->sel_date Refer to $this->sel_date above
	 *
	 * TODO Can this function be simplified?
	**/
	function configure() {
		// TODO Review this entire function to see if code can be simplified or improved on
		// This is the hard work for the calendar and was originally written by by Eric Rosebrock, using his
		// calendar tutorial at http://www.phpfreaks.com/tutorials/83/0.php
		// His code has been heavily modified to work with this framework

		// Define the offset of each day from the beginning of the week:
		$this->offset = array("Sun" => 0,"Mon" => 1,"Tue" => 2,"Wed" => 3,"Thu" => 4,"Fri" => 5,"Sat" => 6);
		if (empty($this->calendar_type)) {
			if (!empty($this->params['cal_type'])) {
				$this->calendar_type = $this->params['cal_type'];
			}
			else {
				$this->calendar_type = "inline";			// Assume inline if no cal_type input variable exists
			}
		}
		// Set the selection mode passed by the user:
		if (empty($this->sel_mode)) {
			if (!empty($this->params['sel_mode'])) {
				$this->sel_mode = $this->params['sel_mode'];
			}
			else {
				$this->sel_mode = 'day';
			}
		}
		if ($this->calendar_type == "popup") {
			// For popup calendars you can only select individual days, never whole weeks
			$this->sel_mode = "day";
		}
		if (empty($this->form_fieldname)) {
			if (!empty($this->params['fieldname'])) {
				$this->form_fieldname = $this->params['fieldname'];
			}
			else {
				$this->form_fieldname = '';
			}
		}
		// Set pre-selected date (or week) if provided by user:
		if (empty($this->sel_date)) {
			if (!empty($this->params['sel_date'])) {
				$this->sel_date = $this->params['sel_date'];
			}
			else {
				$this->sel_date = Crumbs::date_format("","Y-m-d");		// Default to today's date if no date is provided
			}
		}
		if (empty($this->div_id)) {
			if (!empty($this->params['div_id'])) {
				$this->div_id = $this->params['div_id'];
			}
			else {
				Console::log("        Calendar: NO DIV ID PROVIDED! Calendar will not function correctly.");
			}
		}
		// Format the current calendar date as a unix timestamp:
		if (empty($this->cal_date)) {
			if (!empty($this->params['cal_date'])) {
				$this->cal_date = $this->params['cal_date'];
			}
			else {
				$this->cal_date = Crumbs::date_format("","Y-m-d");
			}
		}
		if (empty($this->output_format)) {
			if (!empty($this->params['output_format'])) {
				$this->output_format = $this->params['output_format'];
			}
			else {
				$this->output_format = "d-M-Y";
			}
		}
		// Set the javascript callback function:
		if ($this->calendar_type == "popup") {
			$this->js_callback = "\$Calendar.set_date";
		}
		else {
			$this->js_callback = "\$Calendar.".$this->sel_mode."_select";
		}
		$this->calday = Crumbs::date_format($this->cal_date,'d');
		$this->calmonth = Crumbs::date_format($this->cal_date,'m');
		$this->calyear = Crumbs::date_format($this->cal_date,'Y');
		// Get the first day of the month
		$first_day = $this->calyear."-".(($this->calmonth < 10) ? "0" : "").$this->calmonth."-01";
		// Get friendly month name
		$this->month_name = Crumbs::date_format($first_day,'F');
		// Figure out which day of the week
		// the month starts on.
		$this->month_start_day = Crumbs::date_format($first_day,"D");

		// determine how many days are in the last month.
		if($this->calmonth == 1){
			$this->num_days_last = Crumbs::days_in_month(12, ($this->calyear -1));
		} else {
			$this->num_days_last = Crumbs::days_in_month(($this->calmonth -1), $this->calyear);
		}
		// determine how many days are in the current month.
		$this->num_days_current = Crumbs::days_in_month($this->calmonth, $this->calyear);

		// Build an array for the current days
		// in the month
		for($i = 1; $i <= $this->num_days_current; $i++){
			 $this->num_days_array[] = $i;
		} 

		// Build an array for the number of days
		// in last month
		for($i = 1; $i <= $this->num_days_last; $i++){
			 $this->num_days_last_array[] = $i;
		}

		// If the $this->offset from the starting day of the
		// week happens to be Sunday, $this->offset[$this->month_start_day] would be 0,
		// so don't need an offset correction.

		if($this->offset[$this->month_start_day] > 0){
			 $this->offset_correction = array_slice($this->num_days_last_array, -$this->offset[$this->month_start_day], $this->offset[$this->month_start_day]);
			 $this->new_count = array_merge($this->offset_correction, $this->num_days_array);
			 $this->offset_count = count($this->offset_correction);
		}
		else {
			 $this->offset_count = 0;
			 $this->new_count = $this->num_days_array;
		}

		// count how many days we have with the two
		// previous arrays merged together
		$this->current_num = count($this->new_count);

		// Since we will have 5 HTML table rows (TR)
		// with 7 table data entries (TD)
		// we need to fill in 35 TDs
		// so, we will have to figure out
		// how many days to appened to the end
		// of the final array to make it 35 days.

		if($this->current_num > 35){
			$this->num_weeks = 6;
			$this->outset = (42 - $this->current_num);
		} elseif($this->current_num < 35){
			$this->num_weeks = 5;
			$this->outset = (35 - $this->current_num);
		}
		if($this->current_num == 35){
			$this->num_weeks = 5;
			$this->outset = 0;
		}
		// Outset Correction
		for($i = 1; $i <= $this->outset; $i++){
			$this->new_count[] = $i;
		}

		// Format the pre-select date if it is defined:
		if (!empty($this->sel_date)) {
			$this->sel_date = Crumbs::date_format($this->sel_date,"Y-m-d");
			if ($this->sel_mode == "week") {
				$this->sel_date = $this->get_first_weekday($this->sel_date);
			}
		}
		else {
			$this->sel_date = '';
		}
		// Now let's "chunk" the $all_days array
		// into weeks. Each week has 7 days
		// so we will array_chunk it into 7 days.
		$this->weeks = array_chunk($this->new_count, 7);

		// Previous and Next MONTH dates
		if($this->calmonth == 1){
			$this->prev_date = ($this->calyear-1).'-12-'.$this->calday;
		} else {
			$this->prev_date = $this->calyear.'-'.(($this->calmonth-1 < 10) ? '0' : '').($this->calmonth-1).'-'.$this->calday;
		}
		if($this->calmonth == 12){
			$this->next_date = ($this->calyear+1).'-01-'.$this->calday;
		} else {
			$this->next_date = $this->calyear.'-'.(($this->calmonth+1 < 10) ? '0' : '').($this->calmonth+1).'-'.$this->calday;
		}
		// Previous and Next YEAR dates
		$this->prev_year_date = ($this->calyear-1).'-'.$this->calmonth.'-'.$this->calday;
		$this->next_year_date = ($this->calyear+1).'-'.$this->calmonth.'-'.$this->calday;
		$this->prev_month_js = '\$Calendar.change({ cal_type: \''.$this->calendar_type.'\',div_id: \''.$this->div_id.'\',sel_mode: \''.$this->sel_mode.'\',cal_date: \''.$this->prev_date.'\',sel_date: \''.$this->sel_date.'\',fieldname: \''.$this->form_fieldname.'\')';
		// Calcuate information about the weeks and days for the view file to use:
		$i = 0;
		foreach ($this->weeks as $key => $week) {
			// Week defaults:
			$this->week_info[$key]['id'] = "week_".($key+1);
			$this->week_info[$key]['css_class'] = "cal_row_reg";
			$this->week_info[$key]['event_handlers'] = '';
			if ($this->sel_mode == "week") {
				// If in week selection mode:
				$week_date_month = (int)$this->calmonth;
				$week_date_year = (int)$this->calyear;
				if ($i < $this->offset_count) {
					$week_date_month -= 1;
					if ($week_date_month < 1) {
						$week_date_month = 12;
						$week_date_year -= 1;
					}
				}
				elseif ($this->outset > 0 && $i >= ($this->num_weeks * 7) - $this->outset) {
					$week_date_month += 1;
					if ($week_date_month > 12) {
						$week_date_month = 1;
						$week_date_year += 1;
					}
				}
				$firstday = $week[0];
				if ($key == 0 && $firstday > 20) {
					$week_date = $week_date_year."-".(($week_date_month < 10) ? "0" : "").$week_date_month."-".(($firstday < 10) ? "0" : "").$firstday;
					$week_date_month -= 1;
				}
				else {
					$week_date = $week_date_year."-".(($week_date_month < 10) ? "0" : "").$week_date_month."-".(($firstday < 10) ? "0" : "").$firstday;
				}
				Console::log("        First day of week: ".$week_date);
				$selected_week_date = $this->get_first_weekday($this->sel_date);
				if ($week_date == $selected_week_date) {
					// If the current week is the same as the selected week, highlight it
					$this->week_info[$key]['id'] = "current_week";
					$this->week_info[$key]['css_class'] = "cal_row_sel";
				}
				else {
					// Otherwise add week selection event handlers:
					$this->week_info[$key]['event_handlers'] = ' onMouseOver="this.className=\'cal_row_over\'" onMouseOut="this.className=\'cal_row_reg\'" onClick="'.$this->js_callback.'(\''.$week_date.'\')"';
				}
			}
			foreach ($week as $index => $d) {
				// Some values we'll need to figure out stuff for the days:
				$this_day_year = $this->calyear;
				$this_day_month = $this->calmonth; 
				if($i < $this->offset_count){
					$this_day_month = $this->calmonth-1;
					if ($this_day_month < 1) {
						$this_day_month = 12;
						$this_day_year = $this->calyear-1;
					}
				}
				elseif ($this->outset > 0 && $i >= ($this->num_weeks * 7) - $this->outset) {
					$this_day_month = $this->calmonth+1;
					if ($this_day_month > 12) {
						$this_day_month = 1;
						$this_day_year = $this->calyear+1;
					}
				}
				// Day defaults:
				$this->day_info[$i]['id'] = "day_".(($i+1)*((int)$d+1));
				// The date of the current day:
				$today_date = $this_day_year."-".(($this_day_month < 10) ? "0" : "").(int)$this_day_month."-".(($d < 10) ? "0" : "").$d;
				// Set base day CSS class based on the particular day:
				if ($i < $this->offset_count || ($this->outset > 0 && $i >= ($this->num_weeks * 7) - $this->outset)) {
					// Days that are part of the previous or next month
					$this->day_info[$i]['css_class'] = "cal_nonmonthdays";
				}
				else {
					// Days that are part of this month
					if (date("Y-m-d") == $today_date) {
						// Today
						$this->day_info[$i]['css_class'] = 'cal_today';
					}
					else {
						if ($index == 0 || $index == 6) {
							// Weekend days
							$this->day_info[$i]['css_class'] = 'cal_weekenddays';
						}
						else {
							// Normal days
							$this->day_info[$i]['css_class'] = 'cal_days';
						}
					}
				}
				$this->day_info[$i]['content_class'] = 'cal_day_content';
				if ($this->date_has_event($today_date)) {
					// If there's an event on the current date, add the appropriate CSS class
					$this->day_info[$i]['content_class'] .= ' cal_day_event';
				}
				$this->day_info[$i]['event_handlers'] = '';
				if ($this->sel_mode == "day") {
					// If using day selection mode, either pre-select the current day or apply mouse functionality if this date is not pre-selected:
					if ($today_date == $this->sel_date) {
						// If the current day is the same as the Calendar's selected date highlight it:
						$this->day_info[$i]['css_class'] .= ' cal_day_sel';
						$this->day_info[$i]['id'] = "current_day";
					}
					if ($this->calendar_type == "popup") {
						// Ensure the dated is formatted correctly for output:
						$today_date = Crumbs::date_format($today_date,$this->output_format);
						// For a popup calendar the callback needs both the form field name and the date
						$callback_params = '\''.$this->params['fieldname'].'\',\''.$today_date.'\'';
					}
					elseif ($this->calendar_type == "inline") {
						// For inline calendars the callback just needs the date
						$callback_params = '\''.$today_date.'\'';
					}
					if ($this->calendar_type == "popup" || $today_date != $this->sel_date) {
						// Allow rollovers and clicking if this day is not the current day, or it's a popup calendar
						$this->day_info[$i]['event_handlers'] = ' onMouseOver="Element.addClassName(this,\'cal_day_over\')" onMouseOut="Element.removeClassName(this,\'cal_day_over\')" onClick="'.$this->js_callback.'('.$callback_params.')"';
					}
				}
				$i++;
			}
		}
	}
	/**
	 * Puts an array of events dates into the event_array property
	 * Must call this function after the constructor and before displaying the calendar
	 *
	 * @return void
	 * @param array $event_array An array of dates on which events occur. Pass this from the function or class that is calling the calendar class
	**/
	function set_events($event_array) {
		$this->cal_events = $event_array;
	}
	/**
	 * Business logic to determine if a date has any events
	 *
	 * @return bool
	 * @param string $date A date to find in the $event_array
	 * Requires the $event_array property be set first
	**/
	function date_has_event($date) {
		return (in_array($date,$this->cal_events));
	}
	/**
	 * Determine the date of the first day of a week containing the day in the $date parameter
	 *
	 * @return string Date in the format YYYY-MM-DD
	 * @param string $datestr A date in the format YYYY-MM-DD
	 *
	 */
	function get_first_weekday($datestr) {
		// Find the start date of any week from any date.  Requires the $this->offset array defined in the configure() function
		$day = Crumbs::date_format($datestr,"D");
		$daynum = (int)Crumbs::date_format($datestr,"j");
		$month = Crumbs::date_format($datestr,"n");
		$year = Crumbs::date_format($datestr,"Y");
		$start_day = $daynum-$this->offset[$day];
		if ($start_day < 1) {
			$month -= 1;
			if ($month < 1) {
				$month = 12;
				$year -= 1;
			}
			$tmp_date = $year."-".(($month < 10) ? "0" : "").$month;
			$num_days = (int)Crumbs::date_format($tmp_date."-01","t");
			$start_day = $num_days+$start_day;
		}
		else {
			$tmp_date = $year."-".(($month < 10) ? "0" : "").$month;
		}
		return $tmp_date."-".(($start_day < 10) ? "0" : "").$start_day;
	}
	/**
	 * Display a calendar inline in the page
	 *
	 * @param string $sel_mode How the user can select a date - can be "day" or "week"
	 * @param string $div_id The id of the div containing the calendar
	 * @param string $cal_date The calendar's date - will determine which month to display
	 * @param string $sel_date Optional - date to have pre-selected. Will default to today's date if left out
	 * @param array $events_array Optional - an array of dates containing events. These dates will be highlighted in the calendar.
	 * @return void
	 * @author Peter Epp
	 */
	function render_inline_cal($sel_mode,$div_id,$cal_date,$sel_date = '',$events_array = array()) {
		if ($this->dependencies_met()) {
			$this->cal_date = $cal_date;
			$this->sel_mode = $sel_mode;
			$this->div_id = $div_id;
			$this->sel_date = $sel_date;
			$this->cal_events = $events_array;
			// Configure the calendar:
			$this->configure();
			// Set local vars for accessing Biscuit and Calendar objects:
			$Biscuit = &$this->Biscuit;
			$Calendar = &$this;
			$return = include 'views/plugins/calendar/index.php';
			Crumbs::include_response($return,'Calendar','Calendar could not be displayed! Please contact the system administrator.');
		}
		else {
			echo "Calendar cannot be rendered since Web 2.0 plugin is not installed in the site!";
			Console::log("        Calendar cannot render unless Web 2.0 plugin is installed!");
		}
	}
	/**
	 * Render a date field with an actuator button
	 *
	 * @param string $fieldname			Name/ID of the form text field to contain the date
	 * @param string $date_format		Optional - How the date in the form-field should be formatted. Defaults to d-M-Y (eg. 20-Jan-2008).  This MUST be a format that PHP's strtotime() function can parse into a unix timestamp.  See the PHP manual on the date() and strtotime() functions if you need help determining what formats can be used.
	 * @param string $default_date		Optional - The date you want to appear in the form field by default.  This can be in any string date format.  It will be converted to the format provided by $date_format regardless
	 * @param string $css_class			Optional - A CSS classname to add to the text field
	 * @param string $custom_btn_img	Optional - an image to use for the actuator button. If left blank it will display a standard form button labelled "Pick"
	 * @return void
	 * @author Peter Epp
	 */
	function render_date_field($fieldname,$options = array()) {
		$default_date = "";
		$date_format = "d-M-Y";
		$css_class = "";
		$attributes = "";
		$custom_btn_img = '';
		if (!empty($options['default_date'])) {
			$default_date = $options['default_date'];
		}
		if (!empty($options['date_format'])) {
			$date_format = $options['date_format'];
		}
		if (!empty($options['css_class'])) {
			$css_class = $options['css_class'];
		}
		if (!empty($options['attributes'])) {
			$attributes = $options['attributes'];
		}
		if (!empty($options['custom_btn_img'])) {
			$custom_btn_img = $options['custom_btn_img'];
		}
		if (!empty($options['field_id'])) {
			$field_id = $options['field_id'];
		}
		else {
			$field_id = preg_replace("/([^a-z0-9-_])/i","",$fieldname);
		}
		Console::log($options);
		if ($this->dependencies_met()) {
			if ($default_date == "0000-00-00") {
				$default_date = "";
			}
			else if (!empty($default_date)) {
				$default_date = Crumbs::date_format($default_date,$date_format);
			}
			ob_start();
			include("views/plugins/calendar/selector_field.php");
			return ob_get_clean();
		}
		else {
			Console::log("        Calendar cannot render unless Web 2.0 plugin is installed!");
			return "Date selector field cannot be rendered since Web 2.0 plugin is not installed in the site!";
		}
	}
}
?>