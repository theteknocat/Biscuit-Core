<?php
/**
 * Photo model for basic photo gallery
 *
 * @package Plugins
 * @author Peter Epp
 */

class GalleryPhoto extends AbstractModel {
	/**
	 * The ID of the album the photo is associated with
	 *
	 * @var string
	 */
	var $album_id;
	/**
	 * Filename of the image
	 *
	 * @var string
	 */
	var $filename;
	/**
	 * Title for the photo
	 *
	 * @var string
	 */
	var $title;
	/**
	 * Description of the photo
	 *
	 * @var string
	 */
	var $description;
	/**
	 * Display order of photo
	 *
	 * @var string
	 */
	var $sort_order;
	/**
	 * The old file - for use when saving
	 *
	 * @var string
	 */
	var $_old_file;
	/**
	 * Find one photo in the database
	 *
	 * @param string $id 
	 * @return void
	 * @author Peter Epp
	 */
	function find($id) {
		$id = (int)$id;
		return GalleryPhoto::photo_from_query("SELECT * FROM photos WHERE id = ".$id);
	}
	/**
	 * Find all photos in a given album, or all photos in the entire gallery
	 *
	 * @param int $album_id 
	 * @return void
	 * @author Peter Epp
	 */
	function find_all($album_id = null) {
		$where = "";
		if ($album_id != null) {
			$where = " WHERE album_id = ".(int)$album_id;
		}
		$query = "SELECT * FROM photos".$where." ORDER BY sort_order";
		return GalleryPhoto::photos_from_query($query);
	}
	function album_id()		{		return $this->get_attribute('album_id');	}
	function filename()		{		return $this->get_attribute('filename');	}
	function title()		{		return $this->get_attribute('title');		}
	function description()	{		return $this->get_attribute('description');	}
	function sort_order()	{		return $this->get_attribute('sort_order');	}
	function old_file()		{		return $this->get_attribute('_old_file');	}
	/**
	 * Validate form data
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function validate() {
		if ($this->id() == null) {
			if ((Request::is_ajax() && !Request::form('photo_filename')) || (!Request::is_ajax() && !Request::files('photo_filename'))) {
				$this->set_error("Please select an image file to upload");
			}
		}
		if ($this->sort_order() == null) {
			$this->set_attribute('sort_order','NULL');
		}
		if ($this->errors()) {
			Console::log("error messages: ".implode("\n",$this->errors()));
		}
		$this->has_been_validated(true);
		return !$this->errors();
	}
	/**
	 * Validate a batch upload
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	function validate_batch() {
		$no_files = false;
		Console::log("Validating batch photo upload...");
		Console::log("Contents of 'batch_upload' form input:\n".print_r(Request::form('batch_filename'),true));
		if (Request::is_ajax() && Request::form('batch_filename')) {
			$photo_files = array_values(array_filter(Request::form('batch_filename')));
			if (empty($photo_files)) {
				$no_files = true;
			}
		} else if (!Request::is_ajax() && Request::files('batch_filename')) {
			// Grab the array of files from the form post with empty elements filtered out
			$photo_files = array_values(array_filter(Request::files('batch_filename')));
			if (empty($photo_files)) {
				$no_files = true;
			}
		}
		if ($no_files) {
			$this->set_error("Please select at least one image file to upload");
		}
		return !$this->errors();
	}
	/**
	 * Resort all items in a database table from a sorting array.
	 *
	 * @param array $sort_list An indexed array with elements in the format $sort_list[$sort_index] = $db_primary_key
	 * @return void
	 * @author Peter Epp
	 */
	function resort($sort_list) {
		foreach ($sort_list as $index => $id) {
			$store_index = $index+1;
			DB::query("UPDATE photos SET sort_order = {$store_index} WHERE id = {$id}");
		}
	}
	/**
	 * Handle file upload and save
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function save($upload_path) {
		Console::log("                        Saving data...");
		if ($this->id() != null) {
			$this->set_attribute('_old_file',$this->filename());
		}
		Console::log("                        Checking for uploaded file...");
		$photo_file = Request::files('photo_filename');
		if ($photo_file !== null) {
			Console::log("                        ".print_r($photo_file,true));
			$uploaded_file = new FileUpload($photo_file, $upload_path);
			if ($uploaded_file->is_okay()) {
				// uploaded, processed okay
				if ($this->old_file() != null) {
					Console::log("                        Deleting old file: ".$this->old_file());
					unlink(SITE_ROOT.$upload_path."/".$this->old_file());
				}
				$this->set_attribute('filename',$uploaded_file->file_name());
			} elseif ($uploaded_file->no_file_sent()) {
				if ($this->id() == null) {
					$this->set_error("Please select an image file to upload");
				}
			} else {
				$this->set_error('File upload failed:\n\n'. $uploaded_file->get_error_message());
			}
		}
		else {
			if ($this->id() == null) {
				$this->set_error("Please select an image file to upload");
			}
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
		return (!$this->errors());
	}
	function batch_save($upload_path) {
		Console::log("                        Saving batch upload...");
		$this->set_attribute('batch_filename','');
		Console::log("                        Checking for uploaded file...");
		$photo_files = Request::files('batch_filename');
		$warning = '';
		if ($photo_files != null && is_array($photo_files['name'])) {
			Console::log("                        ".print_r($photo_files,true));
			$uploaded_files = new MultiFileUpload($photo_files, $upload_path, 'image');
			if ($uploaded_files->is_partially_okay()) {
				// At least 1 file uploaded, processed okay
				Console::log("                        Successfully uploaded files as: ".print_r($uploaded_files->file_names(),true));
				// Were there any failures?
				if ($uploaded_files->failed_uploads() !== false) {
					$warning = 'Some files did not upload and were therefore not added to the album:\n\n'.$uploaded_files->failure_list('\n');
				}
			} elseif ($uploaded_files->no_file_sent()) {
				$this->set_error("Please select at least one image file to upload");
			} else {
				$this->set_error('None of the files uploaded and were therefore not added to the album:\n\n'.$uploaded_files->failure_list('\n'));
			}
		}
		else {
			$this->set_error("Please select at least one image file to upload");
		}

		if (!$this->errors()) {
			Console::log('                        Saving data now...');
			// Save the data:
			foreach ($uploaded_files->files as $file) {
				if ($file->is_okay()) {
					$insert_name = DB::escape($file->file_name);
					$album_id = $this->album_id();
					$query = "INSERT INTO photos SET album_id = {$album_id}, filename = '{$insert_name}'";
					$id = DB::insert($query);
				}
			}
			if (!empty($warning)) {
				$this->set_error($warning);
			}
		} else {
			Console::log("                        Skipping DB save");
		}
		return (!$this->errors());
	}
	/**
	 * Build a photo object from a database query
	 *
	 * @param string $query Database query
	 * @return object
	 * @author Peter Epp
	 */
	function photo_from_query($query) {
		return parent::model_from_query("GalleryPhoto",$query);
	}
	/**
	 * Build a collection of photos from a database query
	 *
	 * @param string $query Database query
	 * @return array Collection of news/event objects
	 * @author Peter Epp
	 */
	function photos_from_query($query) {
		return parent::models_from_query("GalleryPhoto",$query);
	}
	function db_tablename() {
		return 'photos';
	}
	function db_create_query() {
		return 'CREATE TABLE  `photos` (
		`id` INT( 8 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`album_id` INT( 8 ) NOT NULL ,
		`title` VARCHAR( 255 ) NOT NULL ,
		`description` TEXT NOT NULL ,
		`sort_order` INT( 3 ) NULL DEFAULT NULL ,
		`filename` VARCHAR( 255 ) NOT NULL ,
		INDEX (  `title` )
		) TYPE = MyISAM';
	}
}
/**
 * Album model for basic photo gallery
 *
 * @package Plugins
 * @author Peter Epp
 */
class GalleryAlbum extends AbstractModel {
	/**
	 * Title of the gallery
	 *
	 * @var string
	 */
	var $title;
	/**
	 * Display order of the album
	 *
	 * @var string
	 */
	
	var $sort_order;
	/**
	 * Date the album was created/updated
	 *
	 * @var string
	 */
	var $updated;
	/**
	 * Find one album in the database
	 *
	 * @param int $id 
	 * @return void
	 * @author Peter Epp
	 */
	function find($id) {
		$id = (int)$id;
		return GalleryAlbum::album_from_query("SELECT * FROM albums WHERE id = ".$id);
	}
	/**
	 * Find all albums in the database
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function find_all() {
		return GalleryAlbum::albums_from_query("SELECT * FROM albums ORDER BY sort_order ASC, updated DESC");
	}
	/**
	 * Return the number of photos in a specified album
	 *
	 * @return string
	 * @author Peter Epp
	 */
	function photo_count() {
		return (int)DB::fetch_one("SELECT COUNT(*) AS photo_count FROM photos WHERE album_id = ".(int)$this->id());
	}
	/**
	 * Return the filename of the first photo in the album
	 *
	 * @return string
	 * @author Peter Epp
	 */
	function first_photo() {
		$album_id = (int)$this->id();
		return DB::fetch_one("SELECT filename FROM photos WHERE album_id = {$album_id} ORDER BY sort_order LIMIT 1");
	}
	function title()		{		return $this->get_attribute('title');		}
	function sort_order()	{		return $this->get_attribute('sort_order');	}
	/**
	 * Validate user input
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function validate() {
		if ($this->title() == null) {
			$this->set_error("Please enter a title");
		}
		if ($this->sort_order() == null) {
			$this->set_attribute('sort_order','NULL');
		}
		$this->set_attribute('updated',date('Y-m-d H:i:s'));
		$this->has_been_validated(true);
		return (!$this->errors());
	}
	/**
	 * Resort all items in a database table from a sorting array.
	 *
	 * @param array $sort_list An indexed array with elements in the format $sort_list[$sort_index] = $db_primary_key
	 * @return void
	 * @author Peter Epp
	 */
	function resort($sort_list) {
		foreach ($sort_list as $index => $id) {
			$store_index = $index+1;
			DB::query("UPDATE albums SET sort_order = {$store_index} WHERE id = {$id}");
		}
	}
	/**
	 * Build a photo object from a database query
	 *
	 * @param string $query Database query
	 * @return object
	 * @author Peter Epp
	 */
	function album_from_query($query) {
		return parent::model_from_query("GalleryAlbum",$query);
	}
	/**
	 * Build a collection of photos from a database query
	 *
	 * @param string $query Database query
	 * @return array Collection of news/event objects
	 * @author Peter Epp
	 */
	function albums_from_query($query) {
		return parent::models_from_query("GalleryAlbum",$query);
	}
	function db_tablename() {
		return 'albums';
	}
	function db_create_query() {
		return 'CREATE TABLE  `albums` (
		`id` INT( 8 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`title` VARCHAR( 255 ) NOT NULL ,
		`sort_order` INT( 3 ) NULL DEFAULT NULL ,
		`updated` DATETIME NOT NULL ,
		INDEX (  `title` ,  `sort_order` )
		) TYPE = MyISAM';
	}
}
?>