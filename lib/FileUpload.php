<?php
require_once("Image.php");
/**
 * Like FileUpload, only with multiple files (so cryptic!)
 *
 * @package Core
 * @author Lee O'Mara
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 **/
class MultiFileUpload {

	/**
		* List of files uploaded
		*
		* @var FileUpload
		**/
	private $files = array();

	/**
	 *
	 * @param array  $files         Required. The file data from the server. normally $_FILES
	 * @param string $upload_dir    Required. File upload directory relative to the web root (ie. "/images/photos").  If the directory doesn't exist, it will be created
	 * @param bool $overwrite_existing Optional. Whether or not to overwrite existing file. Defaults to false.
	 */
	public function __construct($files, $upload_dir, $overwrite_existing = false) {
		if (!is_array($files)) {
			trigger_error("Argument error. First argument must be an array", E_USER_ERROR);
		}

		if (!is_array($files['name'])) { // bail if used for single upload
			trigger_error("MultiFileUpload expects multiple files", E_USER_ERROR);
		}
		
		for ($i=0; $i < count($files['name']);$i++) {
			$file_array = $this->data_for_single_file($files, $i); // create an array that looks like a single file upload
			if (!empty($file_array) && !empty($file_array['name'])) {
				$this->files[] = new FileUpload($file_array, $upload_dir, $overwrite_existing);
			}
		}
	}
	/**
	* return an array that resembles a single file upload
	*
	* @param array $files A $_FILES like array
	* @param integer $index
	* @return array
	* @author Lee O'Mara
	**/
	public function data_for_single_file($files, $index) {
		$file_data = array();
		$fields = array("name", 'type', 'size', 'tmp_name', 'error');
		foreach($fields as $field) {
			$file_data[$field] = $files[$field][$index];
		}
		return $file_data;
	}

	public function is_okay() {
		foreach($this->files as $file){
			if (!$file->is_okay()) {
				return false;
			}
		}
		return true;
	}
	/**
	 * Whether or not at least 1 file successfully uploaded
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function is_partially_okay() {
		$bad_count = 0;
		foreach($this->files as $file){
			if (!$file->is_okay()) {
				$bad_count += 1;
			}
		}
		if ($bad_count == count($this->files)) {
			return false;
		}
		return true;
	}
	/**
	* Were any files sent?
	*
	* @access public
	* @author Lee O'Mara
	*/
	public function no_file_sent() {
		return empty($this->files);
	}

	/**
	* Collect error messages from files
	*
	* @return array
	* @author Lee O'Mara
	**/
	public function get_error_messages(){
		$messages = array();
		foreach($this->files as $file){
			if ($error_message = $file->get_error_message()) {
				$messages[] = $error_message;
			}
		}
		return $messages;
	}
	/**
	 * Return the final names of all the successfully uploaded files
	 *
	 * @return array
	 * @author Peter Epp
	 */
	public function file_names() {
		$filenames = array();
		foreach ($this->files as $file) {
			if ($file->is_okay()) {
				$filenames[] = $file->file_name;
			}
		}
		return $filenames;
	}
	/**
	 * Return all the upload objects that failed.
	 *
	 * @return array
	 * @author Peter Epp
	 */
	public function failed_uploads() {
		if (!$this->is_okay()) {
			$failures = array();
			foreach ($this->files as $file) {
				if (!$file->is_okay()) {
					$failures[] = $file;
				}
			}
			return $failures;
		}
		return false;
	}
	/**
	 * Return a string list of failed filenames names and reasons for failures
	 *
	 * @param string $line_separator What you want each line separated by. Defaults to "\n"
	 * @return string
	 * @author Peter Epp
	 */
	public function failure_list($line_separator = "\n") {
		$failures = $this->failed_uploads();
		$fail_strings = array();
		foreach ($failures as $file) {
			$fail_strings[] = $file->file_name." failed because:".$line_separator.$file->get_error_message().$line_separator;
		}
		return implode($line_separator,$fail_strings);
	}
}

/**
 * Handle single file upload
 *
 * @package Core
 * @author Lee O'Mara
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 */
class FileUpload {
	/**
	 * nothing happened, no file uploaded
	 */
	const UPLOAD_NOTHING = 0;
	/**
	 * successful file upload
	 */
	const UPLOAD_SUCCESS = 1;
	/**
	 * unsuccessful upload, error occurred
	 */
	const UPLOAD_ERROR = 2;
	/**
	 * Status of the FileUpload object
	 * 
	 * @var
	 */
	private $status = self::UPLOAD_NOTHING;

	/**
	 * File upload error message, or false if no errors
	 *
	 * @var string
	 */
	private $file_error = false;
	/**
	 * Full path of the upload directory
	 *
	 * @var string
	 */
	private $upload_dir;
	/**
	 * Name of the uploaded file (will be taken from the $_FILES array)
	 *
	 * @var string
	 */
	private $file_name;
	/**
	 * Name of the file upload form field
	 *
	 * @var string
	 */
	private $field_name;
	/**
	 * Whether or not to overwrite existing files. If false, file will be renamed to avoid conflict with existing files and it will be up to the script
	 * that deals with the upload to sort out what to do with the old file, if applicable.
	 *
	 * @var bool
	 */
	private $overwrite_existing = false;

	/**
	 * Handle a file upload.
	 * 
	 *    $uploaded_file = new FileUpload($_FILES["publication_file"], "/uploads/publications")
	 *    if ($uploaded_file->is_okay()) {
	 *        $filename = $uploaded_file->file_name;
	 *        ...	
	 *    } else {
	 *        $error = $uploaded_file->get_error_message();
	 *        ...
	 *    }
	 *
	 * @param array $uploaded_file	Required. Contains the file data (must resemble $_FILES['foo'])
	 * @param string $upload_dir	Required. File upload directory relative to the web root (ie. "/images/photos").  If the directory doesn't exist, it will be created
	 * @param bool $overwrite_existing Optional. Whether or not to overwrite existing file. Defaults to false.
	 */
	public function __construct($uploaded_file, $upload_dir, $overwrite_existing = false) {
		if (empty($uploaded_file)) {
			$this->error("No uploaded file found!");
			return;
		}

		if (is_array($uploaded_file['name'])) {
			$this->error("FileUpload expects only a single file. Use MultiFileUpload for multiple files", 2);
			return;
		}

		$this->uploaded_file = $uploaded_file;

		$this->overwrite_existing = $overwrite_existing;

		$this->set_file_error($this->uploaded_file);

		if (!$this->is_okay()) {	// errors occurred in upload
			Console::log("                        Errors occurred with file upload: ".$this->file_error);
			return;
		}

		if (empty($this->uploaded_file['name'])) {
			$this->error("No filename given");
			return;
		}		

		if (!is_uploaded_file($this->uploaded_file['tmp_name'])) {
			$this->error("No file was uploaded");
			return;
		}

		$this->upload_dir = SITE_ROOT.$upload_dir;
		$this->file_name = $this->uploaded_file['name'];
		$this->type = "";
		if ($this->is_valid_image($this->uploaded_file['tmp_name'])) {
			$this->type = "image";
		}
		if ($this->ensure_directory_setup()) {
			$this->process_uploaded_file();
		}
	}
	/**
	 * Process the uploaded file
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function process_uploaded_file() {
		Console::log("                        Processing uploaded file: ".$this->uploaded_file['name']);

		$this->clean_filename();

		Console::log("                        Cleaned up filename: ".$this->file_name);
		$full_temp_file_path = TEMP_DIR."/".$this->file_name;		// Always move to temp folder first for post processing
		Console::log("                        Temporary file: ".$full_temp_file_path);
		$full_dest_file_path = $this->upload_dir."/".$this->file_name;
		Console::log("                        Final file:".$full_dest_file_path);
		
		if (!move_uploaded_file($this->uploaded_file['tmp_name'], $full_temp_file_path)) {	// Failure to move file to destination folder
			@unlink($this->uploaded_file['tmp_name']);	// Make sure temp file is deleted
			// Set error message and status
			$this->error(sprintf("Failed to move uploaded file (%s) to destination folder",$this->file_name), 2);
			return;
		}

		chmod($full_temp_file_path,0644);

		if ($this->overwrite_existing && file_exists($full_dest_file_path)) {
			@unlink($full_dest_file_path);
		}

		Console::log("                        File successfully moved to temporary directory for post processing");
		// Post processing
		if ($this->type == "image") {
			$this->image_post_process($full_temp_file_path,$full_dest_file_path);
		}
		else {
			Console::log("                        No processing required. Moving file to destination directory...");
			// Just move the file to destination folder
			if (!rename($full_temp_file_path,$full_dest_file_path)) {
				$this->error(sprintf("Failed to move uploaded file (%s) to destination folder",$this->file_name), 2);
				return;
			}
			if (!chmod($full_dest_file_path,0644)) {
				$this->error(sprintf("Failed to change permissions on uploaded file (%s).",$full_dest_file_path), 2);
				return;
			}
		}
	}
	/**
	 * Post process uploaded image - auto-rotate, if possible, size large image correctly and make thumbnail
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function image_post_process($full_temp_file_path,$full_dest_file_path) {

		Console::log("                        Processing image - resize and make thumbnail");

		// Resize the image, make a thumbnail, then move them to the destination folders
		$image = new Image($full_temp_file_path);

		if (!$image->image_is_valid()) {
			$this->error(sprintf("Your uploaded file cannot be processed. %s Please try a different file.",$image->error()), self::UPLOAD_ERROR);
			return;
		}

		$full_dest_thumb_path = $this->upload_dir."/_thumbs/_".$this->file_name;
		if (file_exists($full_dest_file_path)) {		// Over-write existing image. This is just a fail-safe, since it should have a unique filename by this point
			@unlink($full_dest_file_path);
			@unlink($full_dest_thumb_path);
		}

		// Auto-rotate the image based on the exif data, if present:
		$image->auto_rotate();

		// Resize large image:
		$image->resize(IMG_WIDTH,IMG_HEIGHT,Image::RESIZE_ONLY,$full_dest_file_path);

		// Make thumbnail:
		$image->resize(THUMB_WIDTH,THUMB_HEIGHT,Image::RESIZE_AND_CROP,$full_dest_thumb_path);

		// Destroy the image to free memory:
		$image->destroy();

		unset($image);

		@unlink($full_temp_file_path); // Delete the temporary image file.

		chmod($full_dest_file_path,0644);
		chmod($full_dest_thumb_path,0644);
	}
	/**
	 * Whether or not an uploaded file is a valid image type for resizing
	 *
	 * @param string $src_file Name of the file to check
	 * @return void
	 * @author Peter Epp
	 */
	private function is_valid_image($src_file) {
		$image_type = exif_imagetype($src_file);
		return ($image_type == IMAGETYPE_JPEG || $image_type == IMAGETYPE_GIF || $image_type == IMAGETYPE_PNG);
	}
	/**
	 * Remove invalid characters from the filename and give it a unique name if needed
	 *
	 * @param string $file_name
	 */
	private function clean_filename() {
		$info = pathinfo($this->file_name);
		$ext  = $info['extension'];
		$name = $info['filename'];
		
		$name = preg_replace("'[^A-Za-z0-9_-]+'","_", $name);

		if (!$this->overwrite_existing) {
			$x = 1;
			$original = $name;
			while (file_exists($this->upload_dir."/".$name . '.' . $ext)) { 
				$name = $original."_".$x; // eg if todo.txt and todo_1.txt exist, we'll use todo_2.txt
				$x++;
			}
		}
		$this->file_name = $name . '.' . $ext;
	}

	/**
	 * Return file uploaded error text based on error number passed from $_FILES[your_field_name]['error']
	 */
	private function set_file_error($uploaded_file) {
		switch ($uploaded_file['error']){
			case 0:
				// No error
				$this->file_error = false;
				break;
			case 1:		// Exceeded maximum file size defined in php.ini
			case 2:		// Exceeded maximum file size defined by "MAX_FILE_SIZE" field in upload form
				$this->file_error =   "The file exceeds the maximum allowed file size.";
				break;
			case 3:
				$this->file_error =   "The file was only partially uploaded.";
				break;
			case 4:
				$this->file_error =   "No file was uploaded.";
				break;
			case 6:
				$this->file_error =   "No temporary folder was available (contact the system administrator!)";
				break;
			default:
				$this->file_error =   "A system error occurred (contact the system administrator!)";
				break;
		}//end switch
		$this->status = (($this->file_error === false) ? self::UPLOAD_SUCCESS : self::UPLOAD_ERROR);
	}
	
	/**
	 * Any errors?
	 *
	 * @return boolean
	 **/
	public function is_okay() {
		return $this->file_error === false;
	}
	
	/**
	 * Return an error string, or false if no error found
	 *
	 * @return void
	 **/
	public function get_error_message() {
		if ($this->is_okay()) {
			return false;
		}
		return $this->file_error;
	}
	
	/**
	 * was no file uploaded?
	 *
	 * @return boolean
	 */
	public function no_file_sent() {
		return (!isset($this->uploaded_file) || $this->uploaded_file['error'] == 4);
	}
	
	/**
	 * Tests that the destination and temp directories exist and are writable. Will
	 * try to create them if they are not found.
	 * 
	 * If an error is encountered, it is recorded in $this->file_error
	 *
	 * @return void
	 **/
	private function ensure_directory_setup() {
		// Create destination folders if they don't exist:
		if(!Crumbs::ensure_directory($this->upload_dir)){
			$this->file_error = sprintf("Upload directory (%s) cannot be created or is not writable. Check permissions or create the directory manually.",$this->upload_dir);
			return false;
		}
		if($this->type == "image" && !Crumbs::ensure_directory($this->upload_dir."/_thumbs")){
			$this->file_error = sprintf("Thumbnail directory (%s/_thumbs) cannot be created or is not writable. Check permissions or create the directory manually.",$this->upload_dir);
			return false;
		}
		Console::log("                        Destination directory exists and is writable");
		return true;
	}

	public function error($message, $code = 0){
		Console::log("                        ".$message);
		$this->file_error = $message;
		$this->status = $code;
	}
	/**
	 * Return the file name
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function file_name() {
		return $this->file_name;
	}
}
