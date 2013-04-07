<?php
require_once('plugins/PhotoGallery.php');
/**
 * Controller for basic photo gallery
 *
 * @package Plugins
 * @author Peter Epp
 */
class PhotoGalleryManager extends AbstractPluginController {
	/**
	 * List of other plugins this one is dependent on
	 *
	 * @var array
	 */
	var $dependencies = array("Authenticator","Prototype","LightView");

	function run($params) {
		if ($this->dependencies_met()) {
			$this->Biscuit->register_js("photo_gallery.js");
			if (!empty($params['data_type'])) {
				$this->data_type = $params['data_type'];
			}
			else {
				if ($this->Biscuit->page_name == "photos") {	// Viewing an album
					$this->data_type = 'album';
				}
				elseif ($this->Biscuit->page_name == "photo") {	// Viewing a photo
					$this->data_type = 'photo';
				}
			}
			if (Request::is_ajax() && Request::type() == "validate_batch") {
				// Dispatch to ajax validation if requested
				$this->action_ajax_validate_batch();
			}
			else {
				parent::run($params);
			}
		}
		else {
			Console::log("                        Photo Gallery died because it can't live without Authenticator, Web 2.0, Lightview and Flash Player");
		}
	}
	/**
	 * Read all albums and render
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_index() {
		$this->albums = GalleryAlbum::find_all();
		$this->render();
	}
	/**
	 * Show the contents of a single album
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_show_album() {
		$this->photos = GalleryPhoto::find_all($this->params['id']);
		$this->album = GalleryAlbum::find($this->params['id']);
		$this->render();
	}
	/**
	 * Render XML output for an album
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_xml_output() {
		$this->photos = GalleryPhoto::find_all($this->params['album_id']);
		$this->Biscuit->render_with_template(false);
		$this->Biscuit->content_type("application/xml");
		$this->render();
	}
	/**
	 * Call validation and return a response for use by the Biscuit JS Ajax validation
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_ajax_validate() {
		Console::log('                        Performing AJAX validation for '.get_class($this));
		if (!empty($this->params[$this->data_type])) {
			// grab from user input
			if ($this->data_type == 'album') {
				$item_data = array(
					'title'			=> $this->params['album']['title'],
					'sort_order'	=> $this->params['album']['sort_order']
				);
			}
			else if ($this->data_type == 'photo') {
				$item_data = array(
					'album_id'		=> $this->params['album_id'],
					'title'			=> $this->params['photo']['title'],
					'description'	=> $this->params['photo']['description'],
					'sort_order'	=> $this->params['photo']['sort_order']
				);
			}
			if (!empty($this->params['id'])) {
				$item_data['id'] = $this->params['id'];
			}
			if ($this->data_type == 'album') {
				$item = new GalleryAlbum($item_data);
			}
			else if ($this->data_type == 'photo') {
				$item = new GalleryPhoto($item_data);
			}
			if ($item->validate()) {
				$output = '+OK';
			}
			else {
				Session::flash_unset('user_message');
				$output = "Please make the following corrections:\n\n-".implode("\n-",$item->errors());
			}
		}
		else {
			$output = 'No data submitted!';
		}
		Console::log('                        Validation result: '.$output);
		$this->Biscuit->render($output);
	}
	function action_ajax_validate_batch() {
		$item_data = array(
			'album_id'		=> $this->params['album_id']
		);
		$photo = new GalleryPhoto($item_data);
		if ($photo->validate_batch()) {
			$output = '+OK';
		}
		else {
			Session::flash_unset('user_message');
			$output = "Please make the following corrections:\n\n-".implode("\n-",$photo->errors());
		}
		Console::log('                        Batch validation result: '.$output);
		$this->Biscuit->render($output);
	}
	function action_edit_album() {
		$this->title("Edit Album");
		$item = GalleryAlbum::find($this->params['id']);
		if (!empty($this->params['album'])) {
			// grab from user input
			$item_data = array(
				'title'			=> $this->params['album']['title'],
				'sort_order'	=> $this->params['album']['sort_order']
			);
			// Replace attributes with user input:
			$item->set_attributes($item_data);
		}
		if (Request::is_post()) {
			if ($item->save($this->upload_path())) {
				$this->success_save_response($this->url());
			}
			else {
				$this->failed_save_response($item,"item");
			}
		}
		else {
			$this->item = &$item;
			$this->render();
		}
	}

	function action_edit_photo() {
		$this->title("Edit Photo");
		$item = GalleryPhoto::find($this->params['id']);
		if (!empty($this->params['photo'])) {
			// grab from user input
			$item_data = array(
				'album_id'		=> $this->params['album_id'],
				'title'			=> $this->params['photo']['title'],
				'description'	=> $this->params['photo']['description'],
				'sort_order'	=> $this->params['photo']['sort_order']
			);
			// Replace attributes with user input:
			$item->set_attributes($item_data);
		}
		$this->album = GalleryAlbum::find($item->album_id());
		if (Request::is_post()) {
			if ($item->save($this->upload_path())) {
				$this->success_save_response($this->url('show_album',$item->album_id()));
			}
			else {
				$this->failed_save_response($item,"item");
			}
		}
		else {
			$this->item = &$item;
			$this->render();
		}
	}

	function action_new_album() {
		$this->title('New Album');
		if (empty($this->params['album'])) {
			// create default, empty item
			$item_data = array(
				'title'			=> '',
				'sort_order'	=> ''
			);
		} else {
			// grab from user input
			$item_data = array(
				'title'			=> $this->params['album']['title'],
				'sort_order'	=> $this->params['album']['sort_order']
			);
		}
		// Create a new item from the user input:
		$item = new GalleryAlbum($item_data);
		if (Request::is_post()) {
			if ($item->save($this->upload_path())) {
				$this->success_save_response($this->url());
			}
			else {
				$this->failed_save_response($item,"item");
			}
		}
		else {
			$this->item = &$item;
			$this->render();
		}
	}

	function action_new_photo() {
		$this->title("New Photo");
		if (empty($this->params['photo'])) {
			// create default, empty item
			$item_data = array(
				'album_id'		=> $this->params['album_id'],
				'title'			=> '',
				'description'	=> '',
				'sort_order'	=> ''
			);
		} else {
			// grab from user input
			$item_data = array(
				'album_id'		=> $this->params['album_id'],
				'title'			=> $this->params['photo']['title'],
				'description'	=> $this->params['photo']['description'],
				'sort_order'	=> $this->params['photo']['sort_order']
			);
		}
		// Create a new item from the user input:
		$item = new GalleryPhoto($item_data);
		$this->album = GalleryAlbum::find($item->album_id());
		if (Request::is_post()) {
			if ($item->save($this->upload_path())) {
				$this->success_save_response($this->url('show_album',$item->album_id()),"show_album");
			}
			else {
				$this->failed_save_response($item,"item");
			}
		}
		else {
			$this->item = &$item;
			$this->render();
		}
	}
	/**
	 * Handle batch photo uploads
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_batch_add() {
		$this->title('Batch Photo Upload');
		$item_data = array(
			'album_id'		=> $this->params['album_id']
		);
		// Create a new item from the user input:
		$item = new GalleryPhoto($item_data);
		$this->album = GalleryAlbum::find($item->album_id());
		if (Request::is_post()) {
			if ($item->batch_save($this->upload_path())) {
				$this->success_save_response($this->url('show_album',$item->album_id()),"show_album");
			}
			else {
				$this->failed_save_response($item,"item");
			}
		}
		else {
			$this->item = &$item;
			$this->render();
		}
	}
	/**
	 * Tell the model to resort it's database table according to a sorting array.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_resort_albums() {
		GalleryAlbum::resort($this->params['album_sort']);
	}
	/**
	 * Tell the model to resort it's database table according to a sorting array.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_resort_photos() {
		GalleryPhoto::resort($this->params['photo_sort']);
	}
	/**
	 * Remove a photo album and all of its contents
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_delete_album() {
		$album = GalleryAlbum::find($this->params['id']);
		if ($album->delete()) {
			$photos = GalleryPhoto::find_all($album->id());
			if (!empty($photos)) {
				foreach ($photos as $photo) {
					if ($photo->delete()) {
						if (is_file($this->item_full_file_path($photo)) && @!unlink($this->item_full_file_path($photo))) {
							Console::log("                        Failed to remove file ". $photo->filename());
						}
						else {
							if (is_file($this->item_full_thumb_path($photo)) && @!unlink($this->item_full_thumb_path($photo))) {
								Console::log("                        Failed to remove thumbnail file ". $photo->filename());
							}
						}
					}
				}
			}
			Session::flash("user_message", "Album removed");
		} else {
			Session::flash('user_message', "Failed to remove album");
		}
		if (!Request::is_ajax()) {
			Response::redirect($this->url());
		}
		else {
			$this->params['action'] = 'index';
			$this->action_index();
		}
	}
	/**
	 * Remove a single photo
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_delete_photo() {
		$photo = GalleryPhoto::find($this->params['id']);
		if ($photo->delete()) {
			if (is_file($this->item_full_file_path($photo)) && @!unlink($this->item_full_file_path($photo))) {
				Console::log("                        Failed to remove file ". $photo->filename());
			}
			else {
				if (is_file($this->item_full_thumb_path($photo)) && @!unlink($this->item_full_thumb_path($photo))) {
					Console::log("                        Failed to remove thumbnail file ". $photo->filename());
				}
			}
			Session::flash("user_message", "Photo removed");
		} else {
			Session::flash('user_message', "Failed to remove photo");
		}
		if (!Request::is_ajax()) {
			Response::redirect($this->url('show_album',$photo->album_id()));
		}
		else {
			$this->params['id'] = $photo->album_id();
			$this->params['action'] = 'show_album';
			$this->action_show_album();
		}
	}
	/**
	 * Set the view file based on the current data_type (news or events)
	 *
	 * @return void
	 **/
	function render() {
		$action_name = $this->params['action'];
		if (substr($action_name,0,3) == 'new') {
			$action_name = 'edit_'.$this->data_type;
		}
		parent::render($action_name);
	}
	/**
	 * Enforce the presence of some data(notably ID) for certain actions. This function
     * is called before the action by AbstractPluginController#run
	 *
	 * @return boolean
	 **/
	function before_filter() {
		$can_do = true;
		if (in_array($this->params['action'], array('edit_album', 'edit_photo', 'delete_album', 'delete_photo', 'download'))) {
			// require ID
			$can_do = (!empty($this->params['id']));
		}
		else if (in_array($this->params['action'], array('batch_add', 'new_photo', 'xml_output'))) {
			// require album_id
			$can_do = (!empty($this->params['album_id']));
		}
		return $can_do;
	}

	function item_file_path(&$item){
		if ($item->filename()) {
			return $this->upload_path(). "/" . $item->filename();
		}
		return false;
	}
	function item_thumb_path(&$item) {
		if ($item->filename()) {
			return $this->upload_path(). "/thumbs/" . $item->filename();
		}
		return false;
	}

	/**
	 * Return the full path on the local file system
	 *
	 * @param array Publication
	 * @return string
	 **/
	function item_full_file_path(&$item) {
		return SITE_ROOT.$this->item_file_path($item);
	}
	function item_full_thumb_path(&$item) {
		return SITE_ROOT.$this->item_thumb_path($item);
	}
	/**
	 * Function to grab date and size of a file attachment
	 * @param object $item The news or event item
	 * @return array Or boolean - array of data if file exists, false if file does not exist
	**/
	function file_info(&$item) {
		Console::log("                        Checking for photo file attachment: ".$this->item_full_file_path($item));
		if ($item->filename() != null && file_exists($this->item_full_file_path($item))) {
			Console::log("                        File found: ".$this->item_full_file_path($item));
			$size = intval(filesize($this->item_full_file_path($item))/ 1024);
			if ($size < 1) {
				$file_size = 'Less than 1kb';
			} else {
				$file_size = $size . 'kb';
			}
			$file_date = date('M j Y', filemtime($this->item_full_file_path($item)));
			list($width,$height,$type,$attributes) = getimagesize($this->item_full_file_path($item));
			list($thumb_width,$thumb_height,$thumb_type,$thumb_attributes) = getimagesize($this->item_full_thumb_path($item));
			return array(
				"file_size" => $file_size,
				"file_date" => $file_date,
				"width" => $width,
				"height" => $height,
				"type" => $type,
				"attributes" => $attributes,
				"thumb_width" => $thumb_width,
				"thumb_height" => $thumb_height,
				"thumb_type" => $thumb_type,
				"thumb_attributes" => $thumb_attributes
			);
		}
		else {
			Console::log("                        File not found, moving on...");
			return false;
		}
	}
	/**
	 * If action is "download", returns a new or event file attachment url, otherwise defers to the parent url function
	 *
	 * @param string $action 
	 * @param string $id 
	 * @return void
	 * @author Peter Epp
	 */
	function url($action=NULL,$id=NULL) {
		switch ($action) {
			case 'download':
			case 'thumbnail':
				$download_err_msg = "We apologize for the inconvenience, but our server is currently experiencing difficulties locating your file. This error has been logged, and a system administrator will take care of it as soon as possible.";
				if (empty($id) || !is_object($id)) {
					Console::log("                        URL for 'download' action requires an object instance");
					Session::flash("user_message",$download_err_msg);
					Response::redirect("/".$this->Biscuit->full_page_name);
				}
				// In this case "$id" is not the id, but the database table row
				$item = $id;
				if ($action == 'download') {
					$filepath = $this->item_file_path($item);
				}
				elseif ($action == 'thumbnail') {
					$filepath = $this->item_thumb_path($item);
				}
				if (empty($filepath) || !$filepath) {
					Console::log(get_class($this)."::item_file_path() did not return a value! Download cannot be completed");
					Session::flash("user_message",$download_err_msg);
					Response::redirect("/".$this->Biscuit->full_page_name);
				}
				return $filepath;
				break;
			case 'new_album':
				return sprintf("/".$this->Biscuit->full_page_name."/new_album");
				break;
			case 'new_photo':
				if (empty($id)) {
					Console::log("                        No album ID provided for ".$action);
					Session::flash("user_message","An ID is required for ".$action);
					Response::redirect("/".$this->Biscuit->full_page_name);
				}
				return sprintf("/".$this->Biscuit->full_page_name."/".$id."/new_photo");
				break;
			case 'batch_add':
				if (empty($id)) {
					Console::log("                        No album ID provided for ".$action);
					Session::flash("user_message","An ID is required for ".$action);
					Response::redirect("/".$this->Biscuit->full_page_name);
				}
				return sprintf("/".$this->Biscuit->full_page_name."/".$id."/batch_add");
				break;
			case 'show_album':
			case 'edit_album':
			case 'edit_photo':
			case 'delete_album':
			case 'delete_photo':
				if (empty($id)) {
					Console::log("                        No ID provided for ".$action);
					Session::flash("user_message","An ID is required for ".$action);
					Response::redirect("/".$this->full_page_name);
				}
				return '/'.$this->Biscuit->full_page_name.'/'.$action.'/'.$id;
				break;
			default:
				return parent::url($action,$id);
				break;
		}
	}
	/**
	 * A shortcut to checking user edit album permission
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	function user_can_edit_album() {
		return $this->user_can("edit_album");
	}
	/**
	 * A shortcut to checking user edit photo permission
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	function user_can_edit_photo() {
		return $this->user_can("edit_photo");
	}
	/**
	 * A shortcut to checking user create album permission
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	function user_can_create_album() {
		return $this->user_can("new_album");
	}
	/**
	 * A shortcut to checking user create photo permission
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	function user_can_add_photo() {
		return $this->user_can("new_photo");
	}
	/**
	 * A shortcut to checking user delete album permission
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	function user_can_delete_album() {
		return $this->user_can("delete_album");
	}
	/**
	 * A shortcut to checking user delete photo permission
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	function user_can_delete_photo() {
		return $this->user_can("delete_photo");
	}
	function db_tablename() {
		return array(GalleryPhoto::db_tablename(),GalleryAlbum::db_tablename());
	}
	function db_create_query($table_name) {
		if ($table_name == GalleryPhoto::db_tablename()) {
			return GalleryPhoto::db_create_query();
		}
		else if ($table_name == GalleryAlbum::db_tablename()) {
			return GalleryAlbum::db_create_query();
		}
	}
}
?>