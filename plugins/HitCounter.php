<?php
/**
 * Basic hit counter functions.
 *
 * @package Plugins
 * @author Peter Epp
 **/
class HitCounter extends AbstractPluginController {
	var $dependencies = array("BrowserDetection");
	/**
	 * Record a unique page hit in the database. Only records hits for new sessions created by human visitors. Sets the total count and start date of the date that can be called on for display in view files.
	 *
	 * @return void
	 * @author Peter Epp
	 **/
	function run() {
		if ($this->dependencies_met()) {
			$visitor_type = $this->Biscuit->plugins['BrowserDetection']->info['visitor_type'];
			// $visitor_type can be determined using a browser detection script and then passed to this function
			if ($visitor_type != "bot" && $this->Biscuit->content_type() == "text/html") {		// Only count a hit on HTML content that's not being requested by a bot (not always reliable)
				// Count a human visitor:
				if (!Session::var_exists('counted')) {
					// Count a hit from the current user if they have not been counted already:
					DB::query("UPDATE hit_counter SET count = (count+1) WHERE id = 1");
					if (DB::affected_rows() < 1) {
						DB::insert("INSERT INTO hit_counter SET id = 1, count = 1, start_date = NOW()");
					}
					Console::log('                        A new hit was counted');
					DB::query("OPTIMIZE TABLE hit_counter");
					Session::set('counted',1); // Set this user's hit as counted so it won't count another hit each time they navigate to another page
				}
				else {
					Console::log('                        No hit counted, visitor has already been here once this session');
				}
			}
			else {
				if ($visitor_type == "bot") {
					Console::log('                        A bot came to visit, no hit counted');
				}
				else {
					Console::log('                        Not HTML output, no hit counted');
				}
			}
			// Now grab the total hit count from the DB to display on the page:
			$query = "SELECT count, DATE_FORMAT(start_date,'%b %e, %Y') AS since_date FROM hit_counter LIMIT 1";
			$counter_data = DB::fetch_one($query);
			$this->counter = $counter_data['count'];
			$this->since_date = $counter_data['since_date'];
		}
		else {
			Console::log("                        HitCounter died because it can't live without BrowserDetection");
		}
	}
	/**
	 * Supply the name (or names) of the database table(s) used by this plugin
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function db_tablename() {
		return 'hit_counter';
	}
	/**
	 * Return the query to create a new table for this plugin
	 *
	 * @param mixed $table_name Either an array or string containing the names of the tables used by the plugin
	 * @return void
	 * @author Peter Epp
	 */
	function db_create_query($table_name) {
		return "CREATE TABLE `{$table_name}` (
		  `id` tinyint(3) unsigned NOT NULL default '0',
		  `count` int(15) unsigned NOT NULL default '0',
		  `start_date` datetime NOT NULL default '0000-00-00 00:00:00',
		  PRIMARY KEY  (`id`)
		) TYPE=MyISAM;";
	}
}
?>