<?php
/**
 * Image processing functions. Make sure to always destroy the object or call the destroy() method when done to free up memory.
 * 
 * Example usage:
 *
 * $image = new Image('/uploads/some-image.jpg');
 * $image->auto_rotate();
 * $image->resize(100,100,'/uploads/some-image-thumb.jpg');
 * $image->destroy();
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 1.0
 */
class Image {
	/**
	 * Mode for resizing only without cropping to fit the destination space
	 */
	const RESIZE_ONLY = 1;
	/**
	 * Mode for resizing and cropping to fit the destination space
	 */
	const RESIZE_AND_CROP = 2;
	/**
	 * Flip type horizontal
	 */
	const FLIP_HORIZONTAL = 1;
	/**
	 * Flip type vertical
	 */
	const FLIP_VERTICAL = 2;
	/**
	 * Flip type both
	 */
	const FLIP_BOTH = 3;
	/**
	 * Place to store the image resource
	 *
	 * @var string
	 */
	private $_image = null;
	/**
	 * The source image width
	 *
	 * @var int
	 */
	private $_src_width;
	/**
	 * The source image height
	 *
	 * @var int
	 */
	private $_src_height;
	/**
	 * Full path to the source image
	 *
	 * @var string
	 */
	private $_source_path;
	/**
	 * Exif image type
	 *
	 * @var string
	 */
	private $_image_type = false;
	/**
	 * The particular error with an image if it's not valid
	 *
	 * @var string
	 */
	private $_error_msg;
	/**
	 * Create image resource from the file if it's a valid image type that we can work with
	 *
	 * @param string $full_file_path 
	 * @author Peter Epp
	 */
	public function __construct($full_file_path) {
		if (!$this->can_load_into_memory($full_file_path)) {
			$this->error("The image is too large to load into the available memory.");
			return;
		}
		$this->_image_type = exif_imagetype($full_file_path);
		switch ($this->_image_type) {
			case IMAGETYPE_JPEG:
				$this->_image = imagecreatefromjpeg($full_file_path);
				break;
			case IMAGETYPE_GIF:
				$this->_image = imagecreatefromgif($full_file_path);
				break;
			case IMAGETYPE_PNG:
				$this->_image = imagecreatefrompng($full_file_path);
				break;
		}
		if ($this->image_is_valid()) {
			$this->_source_path = $full_file_path;
			$this->_src_width  = imagesx($this->_image);
			$this->_src_height = imagesy($this->_image);
			if ($this->_image_type == IMAGETYPE_JPEG) {
				$this->_exif = exif_read_data($this->_source_path);
			}
		} else {
			$this->error("The image does not appear to be a valid JPEG, PNG or GIF. Maybe the file is corrupted?");
		}
	}
	/**
	 * Whether or not an image file can be loaded into memory
	 *
	 * @param string $full_file_path 
	 * @return void
	 * @author Peter Epp
	 */
	private function can_load_into_memory($full_file_path) {
		Console::log("Check if image file can be loaded into memory...");
		$memory_allowed = ini_get('memory_limit');
		if (strtolower(substr($memory_allowed,-1)) == 'm') {
			$memory_available = ((int)$memory_allowed)*1024*1024;
		} else if (strtolower(substr($memory_allowed,-1)) == 'k') {
			$memory_available = ((int)$memory_allowed)*1024;
		} else {
			$memory_available = (int)$memory_allowed;
		}
		Console::log("Available RAM: ".$memory_available);
		// Estimate memory needed to create GD image resource from file:
		$imgInfo = getimagesize($full_file_path);
		if (!$imgInfo) {
			return false;
		}
		$width = $imgInfo[0];
		$height = $imgInfo[1];
		if (!empty($imgInfo['bits'])) {
			$bits = (int)$imgInfo['bits'];
		} else {
			$bits = 8;	// Assume 8-bit if not defined in imageinfo, as that's a safe bet
		}
		$memory_estimate = $width * $height * $bits;
		Console::log("Required RAM: ".$memory_estimate);
		return ($memory_estimate <= $memory_available);
	}
	/**
	 * Reset the source image to point at a new image in memory and set the source dimensions to the new ones
	 *
	 * @param string $new_image_resource Pointer to the new image resource in memory
	 * @return void
	 * @author Peter Epp
	 */
	private function _update_source($new_image_resource) {
		// Destroy the old image to free memory
		imagedestroy($this->_image);
		// Point the image at the new resource
		$this->_image = $new_image_resource;
		// Unset the old resource pointer
		unset($new_image_resource);
		// Ensure the source dimensions are set correctly
		$this->_src_width  = imagesx($this->_image);
		$this->_src_height = imagesy($this->_image);
	}
	/**
	 * Save an image resource to file, if desired, and/or update the source image with the new one
	 *
	 * @param string $dest_resource Pointer to the image resource in memory
	 * @param string $destination_path Destination path. If empty, only an update of the source image will occur
	 * @return void
	 * @author Peter Epp
	 */
	private function _save_and_or_update($dest_resource,$destination_path) {
		if (!empty($destination_path)) {
			// If image path provided, save to file:
			switch ($this->_image_type) {
				case IMAGETYPE_JPEG:
					imagejpeg($dest_resource, $destination_path, 90);
					break;
				case IMAGETYPE_GIF:
					imagegif($dest_resource, $destination_path);
					break;
				case IMAGETYPE_PNG:
					imagepng($dest_resource, $destination_path, 9);
					break;
			}
		}
		if (empty($destination_path) || $destination_path == $this->_source_path) {
			// If not saved to file or destination overwrites the source, update our source image:
			$this->_update_source($dest_resource);
		} else {
			// Otherwise free up memory:
			imagedestroy($dest_resource);
		}
	}
	/**
	 * Ensure that the image is destroyed from memory when the object is destructed
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function __destruct() {
		$this->destroy();
	}
	/**
	 * Whether or not the current image is valid
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function image_is_valid() {
		return ($this->_image_type !== false && !empty($this->_image));
	}
	/**
	 * Read Exif image data and if rotation information is found automatically rotate the image to the correct angle. This accommodates
	 * cameras that use an accelerometer or gyro to determine rotation and set it in the meta data
	 *
	 * @param string|null $destination_path Optional. If not provided, changes will just stay in memory and not be written to a file
	 * @return bool Success
	 * @author Peter Epp
	 */
	public function auto_rotate($destination_path = null) {
		if (!$this->image_is_valid() || empty($this->_exif)) {
			// Fail if invalid image type
			return false;
		}
		$ort = null;
		if (!empty($this->_exif['Orientation'])) {
			$ort = $this->_exif['Orientation'];
		} else if (!empty($this->_exif['IFD0']) && !empty($exif['IFD0']['Orientation'])) {
			$ort = $this->_exif['IFD0']['Orientation'];
		}
		if (!empty($ort)) {
			switch ($ort) {
				case 1: // nothing
					return true;
					break;

				case 2: // horizontal flip
					return $this->flip_image(self::FLIP_HORIZONTAL,$destination_path);
					break;

				case 3: // 180 rotate left
					return $this->rotate_image(180);
					break;

				case 4: // vertical flip
					return $this->flip_image(self::FLIP_VERTICAL,$destination_path);
					break;

				case 5: // vertical flip + 90 rotate right
					if ($this->flip_image(self::FLIP_VERTICAL)) {
						return $this->rotate_image(-90,$destination_path);
					}
					return false;
					break;

				case 6: // 90 rotate right
					return $this->rotate_image(-90,$destination_path);
					break;

				case 7: // horizontal flip + 90 rotate right
					if ($this->flip_image(self::FLIP_HORIZONTAL)) {
						return $this->rotate_image(-90,$destination_path);
					}
					return false;
					break;

				case 8:    // 90 rotate left
					return $this->rotate_image(90,$destination_path);
					break;
			}
		} else {
			// Pretend it worked if there was no orientation data to go by
			return true;
		}
	}
	/**
	 * Flip an image either horizontally or vertically, optionally saving out to destination file
	 *
	 * @param int $type self::FLIP_HORIZONTAL or self::FLIP_VERTICAL
	 * @param string $destination_path Optional. If not provided, changes will just stay in memory and not be written to a file
	 * @return bool Success
	 * @author Peter Epp
	 */
	public function flip_image(int $type,$destination_path = null) {
		if (!$this->image_is_valid()) {
			// Fail if invalid image type
			return false;
		}

		$src_width  = $this->_src_width;
		$src_height = $this->_src_height;

		$dest = imagecreatetruecolor($src_width, $src_height);

		for ($x = 0 ; $x < $src_width ; $x++) {
			for ($y = 0 ; $y < $src_height ; $y++) {
				if ($type == self::FLIP_HORIZONTAL) {
					imagecopy($dest, $this->_image, $src_width-$x-1, $y, $x, $y, 1, 1);
				}
				if ($type == self::FLIP_VERTICAL) {
					imagecopy($dest, $this->_image, $x, $src_height-$y-1, $x, $y, 1, 1);
				}
				if ($type == self::FLIP_BOTH) {
					imagecopy($dest, $this->_image, $src_width-$x-1, $height-$y-1, $x, $y, 1, 1);
				}
			}
		}

		$this->_save_and_or_update($dest,$destination_path);

		// Indicate success
		return true;
	}
	/**
	 * Rotate an image by a specified angle
	 *
	 * @param string $angle 
	 * @param string $destination_path Optional. If not provided, changes will just stay in memory and not be written to a file
	 * @return bool Success
	 * @author Peter Epp
	 */
	public function rotate_image($angle,$destination_path = null) {
		if (!$this->image_is_valid()) {
			// Fail if invalid image type
			return false;
		}

		$dest = imagerotate($this->_image,$angle,0);
		
		$this->_save_and_or_update($dest,$destination_path);

		// Indicate success
		return true;
	}
	/**
	 * Resize an image, saving to a new destination file if desired
	 *
	 * @param string $new_width New image width
	 * @param string $new_height New image height
	 * @param string $fit_mode self::RESIZE_ONLY or self::RESIZE_AND_CROP
	 * @param string $destination_path Optional. If not provided, changes will just stay in memory and not be written to a file
	 * @return bool Success
	 * @author Peter Epp
	 */
	public function resize($new_width,$new_height,$fit_mode,$destination_path = null) {

		if (!$this->image_is_valid()) {
			// Fail if invalid image type
			return false;
		}

		if ($new_width == 0 && $new_height == 0) {
			// Fail if width and height are both zero
			return false;
		}

		// Put source image dimensions into local vars so as not to interfere with the original data:
		$src_width  = $this->_src_width;
		$src_height = $this->_src_height;

		if ($fit_mode == self::RESIZE_AND_CROP) {
			// If asked to resize and crop but the new width or height are set to zero, force resize only since we cannot crop without both a
			// width and height greater than zero for the destination image
			if ($new_width == 0 || $new_height == 0) {
				$fit_mode = self::RESIZE_ONLY;
			}
		}

		if ($fit_mode == self::RESIZE_ONLY) {
			$src_x_offset = 0;
			$src_y_offset = 0;
			if ($new_height == 0 || $src_width > $src_height) {
				// If the new height is set to zero or the source image is landscape orientation, we will size the height proportionally to the width:
				$scale = $new_width/$src_width;
				$new_height = round($src_height*$scale);
			} else if ($new_width == 0 || $src_height > $src_width) {
				// If the new width is set to zero or the source image is portrait orientation, we will size the width proportionally to the height:
				$scale = $new_height/$src_height;
				$new_width = round($src_width*$scale);
			}
		} else if ($fit_mode == self::RESIZE_AND_CROP) {
			$src_ratio = ($src_width/$src_height);
			$dest_ratio = ($new_width/$new_height);
			if ($src_ratio > $dest_ratio) {
				// The source image's width-to-height ratio is greater than the destination's width-to-height ratio, therefore we will
				// crop the source's width to the correct proportion of the height

				// Change the source width to use for the image copy to the cropped width:
				$new_src_width = round($src_height*$dest_ratio);

				// Set the left and top offsets to use for the image copy:
				$src_x_offset = round(($src_width/2)-($new_src_width/2));
				$src_y_offset = 0;

				// Override original source width with the new value:
				$src_width = $new_src_width;
			} else {
				// The source image's width-to-height ratio is equal to or less than the destination's width-to-height ratio, therefore we will
				// crop the source's height to the correct proportion of the width

				// Change the source height to use for the image copy to the cropped height:
				$new_src_height = round($src_width/$dest_ratio);

				// Set the left and top offsets to use for the image copy:
				$src_x_offset = 0;
				$src_y_offset = round(($src_height/2)-($new_src_height/2));

				// Override the original source height with the new value:
				$src_height = $new_src_height;
			}
		}

		// Now perform the image copy:

		// Create a destination image resource in memory:
		$dest = imagecreatetruecolor($new_width,$new_height);

		// Syntax for crop and scale source into destination:
		// imagecopyresampled ( resource $dst_image , resource $src_image , int $dst_x , int $dst_y , int $src_x , int $src_y , int $dst_w , int $dst_h , int $src_w , int $src_h )

		// Do the work:
		imagecopyresampled($dest,$this->_image,0,0,$src_x_offset,$src_y_offset,$new_width,$new_height,$src_width,$src_height);

		$this->_save_and_or_update($dest,$destination_path);

		// Indicate success:
		return true;
	}
	/**
	 * Destroy image to free memory
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function destroy() {
		if (!empty($this->_image)) {
			imagedestroy($this->_image);
			$this->_image = null;
		}
	}
	/**
	 * Replacement for imagerotate GD function in case it doesn't exist with the GD lib installed on the server.
	 *
	 * @param string $srcImg 
	 * @param string $angle 
	 * @param string $bgcolor 
	 * @param string $ignore_transparent Not actually used
	 * @return image
	 * @author Peter Epp
	 */
	public static function rotate($srcImg, $angle, $bgcolor, $ignore_transparent = 0) {

		$srcw = imagesx($srcImg);
		$srch = imagesy($srcImg);

		if($angle == 0) return $srcImg;

		// Convert the angle to radians
		$theta = deg2rad ($angle);

		// Calculate the width of the destination image.
		$temp = array (    self::rotateX(0,     0, 0-$theta),
			$this->rotateX($srcw, 0, 0-$theta),
			$this->rotateX(0,     $srch, 0-$theta),
			$this->rotateX($srcw, $srch, 0-$theta)
			);
		$minX = floor(min($temp));
		$maxX = ceil(max($temp));
		$width = $maxX - $minX;

		// Calculate the height of the destination image.
		$temp = array (    $this->rotateY(0,     0, 0-$theta),
			$this->rotateY($srcw, 0, 0-$theta),
			$this->rotateY(0,     $srch, 0-$theta),
			$this->rotateY($srcw, $srch, 0-$theta)
			);
		$minY = floor(min($temp));
		$maxY = ceil(max($temp));
		$height = $maxY - $minY;

		$destimg = imagecreatetruecolor($width, $height);
		imagefill($destimg, 0, 0, imagecolorallocate($destimg, 0,255, 0));

		// sets all pixels in the new image
		for($x=$minX;$x<$maxX;$x++) {
			for($y=$minY;$y<$maxY;$y++)
			{
				// fetch corresponding pixel from the source image
				$srcX = round($this->rotateX($x, $y, $theta));
				$srcY = round($this->rotateY($x, $y, $theta));
				if($srcX >= 0 && $srcX < $srcw && $srcY >= 0 && $srcY < $srch)
				{
					$color = imagecolorat($srcImg, $srcX, $srcY );
				}
				else
				{
					$color = $bgcolor;
				}
				imagesetpixel($destimg, $x-$minX, $y-$minY, $color);
			}
		}
		return $destimg;
	}
	private static function rotateX($x, $y, $theta){
		return $x * cos($theta) - $y * sin($theta);
	}

	private static function rotateY($x, $y, $theta){
		return $x * sin($theta) + $y * cos($theta);
	}
	/**
	 * Get or set an error message for the image
	 *
	 * @param string $msg 
	 * @return void
	 * @author Peter Epp
	 */
	public function error($msg = null) {
		if (empty($msg)) {
			return $this->_error_msg;
		}
		$this->_error_msg = $msg;
	}
}

if(!function_exists("imagerotate")) {
	// Define imagerotate if not found, using the static method defined on the Image class
    function imagerotate($srcImg, $angle, $bgcolor, $ignore_transparent = 0) {
        return Image::rotate($srcImg, $angle, $bgcolor, $ignore_transparent);
    }
}
