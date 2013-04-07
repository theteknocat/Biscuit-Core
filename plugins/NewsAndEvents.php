<?php
/**
 * News and events model
 *
 * @package Plugins
 * @author Peter Epp
 */
class NewsAndEvents extends AbstractModel {
	/**
	 * The type of data - news or event
	 *
	 * @var string
	 */
	var $data_type;
	/**
	 * The title of the item
	 *
	 * @var string
	 */
	var $title;
	/**
	 * The detailed content of the item
	 *
	 * @var string
	 */
	var $text;
	/**
	 * The date and time the item was first published
	 *
	 * @var string
	 */
	var $date;
	/**
	 * The date and time the item was last updated
	 *
	 * @var string
	 */
	var $updated;
	/**
	 * The date the item expires
	 *
	 * @var string
	 */
	var $expiry;
	/**
	 * The filename of the attachment relative to the /uploads/newsandeventsmanager folder
	 *
	 * @var string
	 */
	var $attachment;
	/**
	 * Whether or not the item is expired
	 *
	 * @var int
	 */
	var $_is_expired;
	/**
	 * The old file attachment - for use when saving
	 *
	 * @var string
	 */
	var $_old_attachment;
	/**
	 * Whether or not to remove an attachment when saving
	 *
	 * @var int
	 */
	var $remove_attachment = 0;
	/**
	 * Find a single item by id
	 *
	 * @param int $id The id of the news/event
	 * @return object An instance of the newsandevents model
	 * @author Peter Epp
	 */
	function find($id) {
		$id = (int)$id;
		return NewsAndEvents::item_from_query("SELECT * FROM news_and_events WHERE id = ".$id);
	}
	/**
	 * Find all items of a specified type, with or without items that have expired
	 *
	 * @param string $data_type "news" or "event"
	 * @param bool $include_expired 
	 * @return array Indexed array of news/event objects
	 * @author Peter Epp
	 */
	function find_all($data_type = "news",$include_expired = false) {
		$expired_exclude = "";
		if (!$include_expired) {
			$expired_exclude = "AND (expiry >= NOW() OR expiry = '0000-00-00')";
		}
		if ($data_type == "news") {
			$sort_dir = "DESC";
		}
		else {
			$sort_dir = "ASC";
		}
		$query = "SELECT *, IF((expiry<NOW() AND expiry != '0000-00-00'),1,0) AS _is_expired
				FROM news_and_events
				WHERE data_type = '{$data_type}'
				{$expired_exclude}
				ORDER BY date ".$sort_dir;
		return NewsAndEvents::items_from_query($query);
	}
	/**
	 * Find latest X items by date
	 *
	 * @param int $count Number of rows to retrieve
	 * @param string $data_type "news" or "event"
	 * @param bool $include_expired 
	 * @return array Indexed array of news/event objects
	 * @author Peter Epp
	 */
	function find_latest($count,$data_type = "news",$include_expired) {
		$expired_exclude = "";
		if (!$include_expired) {
			$expired_exclude = "AND (expiry >= NOW() OR expiry = '0000-00-00')";
		}
		if ($data_type == "news") {
			$sort_dir = "DESC";
		}
		else {
			$sort_dir = "ASC";
		}
		$query = "SELECT *, IF((expiry<NOW() AND expiry != '0000-00-00'),1,0) AS _is_expired
				FROM news_and_events
				WHERE data_type = '{$data_type}'
				{$expired_exclude}
				ORDER BY date {$sort_dir}
				LIMIT {$count}";
		return NewsAndEvents::items_from_query($query);
	}
	/**
	 * Find all the records following a particular record number
	 *
	 * @param int $start_row Number of the row to start from
	 * @param string $data_type "news" or "event"
	 * @param bool $include_expired 
	 * @return array Indexed array of news/event objects
	 * @author Peter Epp
	 */
	function find_all_after($start_row,$data_type = "news",$include_expired) {
		$expired_exclude = "";
		if (!$include_expired) {
			$expired_exclude = "AND (expiry >= NOW() OR expiry = '0000-00-00')";
		}
		if ($data_type == "news") {
			$sort_dir = "DESC";
		}
		else {
			$sort_dir = "ASC";
		}
		$query = "SELECT *, IF((expiry<NOW() AND expiry != '0000-00-00'),1,0) AS _is_expired
				FROM news_and_events
				WHERE data_type = '{$data_type}'
				{$expired_exclude}
				ORDER BY date {$sort_dir}
				LIMIT {$start_row}, 18446744073709551615";
		return NewsAndEvents::items_from_query($query);
	}
	/**
	 * Return the data type for the current item
	 *
	 * @return string "news" or "event"
	 * @author Peter Epp
	 */
	function data_type()		{	return $this->get_attribute('data_type');		}
	/**
	 * Return the title for the current item
	 *
	 * @return string
	 * @author Peter Epp
	 */
	function title()			{	return $this->get_attribute('title');			}
	/**
	 * Return the text content for the current item
	 *
	 * @return string
	 * @author Peter Epp
	 */
	function text()				{	return $this->get_attribute('text');			}
	/**
	 * Return the published date for the current item
	 *
	 * @return string
	 * @author Peter Epp
	 */
	function date()				{	return $this->get_attribute('date');			}
	/**
	 * Return the updated date for the current item
	 *
	 * @return string
	 * @author Peter Epp
	 */
	function updated()			{	return $this->get_attribute('updated');			}
	/**
	 * Return the expiry date for the current item
	 *
	 * @return string
	 * @author Peter Epp
	 */
	function expiry()			{	return $this->get_attribute('expiry');			}
	/**
	 * Return whether or not the item has expired
	 *
	 * @return int 1 or 0
	 * @author Peter Epp
	 */
	function is_expired()		{	return $this->get_attribute('_is_expired');		}
	/**
	 * Return the attachment filename for the current item
	 *
	 * @return string
	 * @author Peter Epp
	 */
	function attachment()		{	return $this->get_attribute('attachment');		}
	/**
	 * Return the old attachment filename for the current item
	 *
	 * @return string
	 * @author Peter Epp
	 */
	function old_attachment()	{	return $this->get_attribute('_old_attachment');	}
	/**
	 * Build a news/event object from a database query
	 *
	 * @param string $query Database query
	 * @return object
	 * @author Peter Epp
	 */
	function item_from_query($query) {
		return parent::model_from_query("NewsAndEvents",$query);
	}
	/**
	 * Build a collection of news/events from a database query
	 *
	 * @param string $query Database query
	 * @return array Collection of news/event objects
	 * @author Peter Epp
	 */
	function items_from_query($query) {
		return parent::models_from_query("NewsAndEvents",$query);
	}
	/**
	 * Validate user input
	 *
	 * @return bool Whether or not data is valid
	 * @author Peter Epp
	 */
	function validate() {
		if ($this->title() == null) {
			$this->set_error('Enter a title for this '.$this->data_type().' entry');
		}
		if ($this->text() == null) {
			$this->set_error('Enter the details for this '.$this->data_type().' entry');
		}
		if ($this->date() == null) {
			$this->set_error('Select a date for this '.$this->data_type().' entry');
		}
		if (!$this->errors()) {
			// Set defaults for optional fields:
			if ($this->expiry() == null) {
				$this->set_attribute('expiry',"0000-00-00");
			}
			else if ($this->expiry() != "0000-00-00") {
				$this->set_attribute('expiry',Crumbs::date_format($this->expiry(),"Y-m-d"));		// Ensure that the expiry date is properly formatted for DB insertion
			}
			$this->set_attribute('date',Crumbs::date_format($this->date(),"Y-m-d"));		// Ensure that the post date is properly formatted for DB insertion
			$this->set_attribute('updated',date("Y-m-d"));
		}
		else {
			Console::log("error messages: ".implode("\n",$this->errors()));
		}
		$this->has_been_validated(true);
		return !$this->errors();
	}

	function save_filter($data) {
		unset($data['remove_attachment']);
		return $data;
	}

	function save($upload_path) {
		Console::log("                        Saving data...");
		if ($this->id() !== null && $this->attachment() != null) {
			$this->set_attribute('_old_attachment',$this->attachment());
		}
		Console::log("                        Checking for uploaded file...");
		if (!empty($_FILES) && !empty($_FILES['item_filename'])) {
			Console::log("                        ".print_r($_FILES['item_filename'],true));
			$uploaded_file = new FileUpload($_FILES['item_filename'], $upload_path);
			if ($uploaded_file->is_okay()) {
				// uploaded, processed okay
				if ($this->old_attachment() != null) {
					Console::log("                        Deleting old file: ".$this->old_attachment());
					unlink(SITE_ROOT.$upload_path."/".$this->old_attachment());
				}
				$this->set_attribute('attachment',$uploaded_file->file_name());
			} elseif ($uploaded_file->no_file_sent()) {
				Console::log("                        No file uploaded, chill"); // let validation catch the error, if one exists
			} else {
				$this->set_error("File upload failed: ". $uploaded_file->get_error_message());
			}
		}
		else {
			Console::log("                        No file upload data, chill");
		}

		if (!$this->errors()) {
			if (!$this->validate()) {
				return false;
			}
			Console::log('                        Saving data now...');
			// Save the data:
			if ($this->id() == null) {
				$id = parent::save();
		        if (!$id) {
					$this->set_error("Failed to save ".$this->data_type().":<br>".DB::error());
		        }
			} else {
				// Delete existing file attachment if requested:
				if ($this->remove_attachment == 1) {
					Console::log("                        Deleting attachment...");
					unlink(SITE_ROOT.$upload_path.'/'.$this->old_attachment());
					$this->set_attribute('attachment','');
				}
				if (!parent::save()) {
					$this->set_error("Failed to save ".$this->data_type()." item.");
				}
			}
			if ($this->errors() && !empty($uploaded_file)) {
				unlink(SITE_ROOT.$upload_path."/".$uploaded_file->file_name());
			}
		} else {
			Console::log("                        Skipping DB save");
		}
		if (!$this->errors()) {
			Console::log('                        Save success');
			return true;
		} else {
			Console::log("                        Error_messages: " . implode(', ', $this->errors()));
			Session::flash("user_message", implode('<br>', $this->errors()));
			return false;
		}
	}
	/**
	 * Supply the name (or names) of the database table(s) used by this plugin
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function db_tablename() {
		return 'news_and_events';
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
			`id` int(14) unsigned NOT NULL auto_increment,
			`data_type` varchar(6) NOT NULL default '',
			`title` varchar(255) NOT NULL default '',
			`text` longtext NOT NULL,
			`date` datetime NOT NULL default '0000-00-00 00:00:00',
			`updated` datetime NOT NULL default '0000-00-00 00:00:00',
			`expiry` date NOT NULL default '0000-00-00',
			`attachment` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`id`)
		) TYPE=MyISAM;";
	}
}
?>