<?php
define('RESIZE_AND_CROP',1);
define('RESIZE_ONLY',2);
/**
 * Abstract class for doing the dirty work of resizing the image.
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 */
class AbstractImageResize {
	var $imageName;
	var $resizedImageName;
	var $src_image;
	var $dest_image;
	/**
	 * Resize the image
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function resizeImage() {
		$this->do_the_math();
		$this->dest_image = imagecreatetruecolor($this->new_w, $this->new_h);
		// imagecopyresampled ( resource $dst_image , resource $src_image , int $dst_x , int $dst_y , int $src_x , int $src_y , int $dst_w , int $dst_h , int $src_w , int $src_h )
		imagecopyresampled($this->dest_image, $this->src_image, 0, 0, $this->x_offset, $this->y_offset, $this->new_w, $this->new_h, $this->old_w, $this->old_h);
		imagedestroy($this->src_image);
	}
	/**
	 * Do the math that gives us our new size data
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function do_the_math() {
		$this->old_w = imagesx($this->src_image);
		$this->old_h = imagesy($this->src_image);

		$old_ratio = $this->old_w/$this->old_h;
		if (empty($this->full_w) || empty($this->full_h)) {
			// If destination width or height are undefined, keep the same ratio as the original image
			$ratio = $old_ratio;
		} else {
			$ratio = $this->full_w/$this->full_h;
		}
		if ($this->fitMode == RESIZE_AND_CROP) {
			if($old_ratio >= $ratio) {
				// Size to height
				$percent = $this->full_h/$this->old_h;
				$this->x_offset = round(($this->old_w-$this->old_h)/2);
				$this->y_offset = 0;
				$this->old_w = $this->old_h;	// Make it square
			}
			elseif ($old_ratio < $ratio) {
				// Size to width
				$percent = $this->full_w/$this->old_w;
				$this->x_offset = 0;
				$this->y_offset = round(($this->old_h-$this->old_w)/2);
				$this->old_h = $this->old_w;	// Make it square
			}
			// Use the exact size for the destination image:
			$this->new_w = $this->full_w;
			$this->new_h = $this->full_h;
		}
		elseif ($this->fitMode == RESIZE_ONLY) {
			if($old_ratio >= $ratio && !empty($this->full_w)) {
				$percent = $this->full_w/$this->old_w;
			} else {
				$percent = $this->full_h/$this->old_h;
			}
			$this->new_w = round($this->old_w*$percent);
			$this->new_h = round($this->old_h*$percent);
			$this->x_offset = 0;
			$this->y_offset = 0;
		}
	}
}
/**
 * Resize images either from a file to a new file, or from binary data to new binary data
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 */
class ImageResize extends AbstractImageResize {
	/**
	 * Constructor. Arguments are only needed when outputting image to a file
	 *
	 * @param string $imageName 
	 * @param string $resizedImageName 
	 * @return void
	 * @author Peter Epp
	 */
	function __construct($imageName = null, $resizedImageName = null) {
		$this->imageName = $imageName;
		$this->resizedImageName = $resizedImageName;
	}
	/**
	 * Resize an image from a source file and output it to a destination file
	 *
	 * @param string $full_w Full original width of the image
	 * @param string $full_h Full original height of the image
	 * @param int $fitMode 1 for zoom and crop, 2 for zoom only
	 * @return void
	 * @author Peter Epp
	 */
	function makeResizedImage($full_w,$full_h,$fitMode) {
		if (empty($full_w) && empty($full_h)) {
			// Both width and height not specified so just copy source image to destination and return. This eliminates the need for any extra logic
			// in the caller.
			@copy($this->imageName,$this->resizedImageName);
			return;
		}
		$this->full_w = $full_w;
		$this->full_h = $full_h;
		if ($fitMode == RESIZE_AND_CROP && (empty($this->full_w) || empty($this->full_h))) {
			// If width or height are empty, resize only, don't crop
			$this->fitMode = RESIZE_ONLY;
		} else {
			$this->fitMode = $fitMode;
		}
		if (exif_imagetype($this->imageName) == IMAGETYPE_JPEG) {
			$this->src_image = imagecreatefromjpeg($this->imageName);
		}
		if (exif_imagetype($this->imageName) == IMAGETYPE_GIF) {
			$this->src_image = imagecreatefromgif($this->imageName);
		}
		if (exif_imagetype($this->imageName) == IMAGETYPE_PNG) {
			$this->src_image = imagecreatefrompng($this->imageName);
		}
		$this->resizeImage($full_w,$full_h,$fitMode);
		imagejpeg($this->dest_image, $this->resizedImageName);
		imagedestroy($this->dest_image);
	}
	/**
	 * Resize an image and return the binary resource pointer to the caller. Remember to ensure that your caller destroys the resource afterwards!
	 *
	 * @param bin $src_data The binary image source data
	 * @param string $full_w Full original width of the image
	 * @param string $full_h Full original height of the image
	 * @param int $fitMode 1 for zoom and crop, 2 for zoom only
	 * @return resource The binary image data resource pointer
	 * @author Peter Epp
	 */
	function returnResizedImage($src_data,$full_w,$full_h,$fitMode) {
		$this->full_w = $full_w;
		$this->full_h = $full_h;
		$this->fitMode = $fitMode;
		$this->src_image = imagecreatefromstring($src_data);
		$this->resizeImage();
		return $this->dest_image;
	}
}	

/**
 * Merge images with alpha blending
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 */
class ImageMergeClass
{
	var $source;
	var $mergedImageName;
	var $src_image;
	var $dest_image;
	
	function mergeImage($dest_w,$dest_h,$fitMode,$frameborder)
	{
		$src_w = imagesx($this->src_image);
		$src_h = imagesy($this->src_image);
		$src_ratio = $src_w/$src_h;

		$frameSource = SITE_ROOT.$frameborder;
		$ratio = $dest_w/$dest_h;
		$mid_w = floor($dest_w/2);
		$mid_h = floor($dest_h/2);
		$src_x = 0;
		$src_y = 0;
		$dest_x = 0;
		$dest_y = 0;
		if ($src_ratio > $ratio) {
			if ($fitMode == 1) {
				$src_x = round(($src_w/2)-$mid_w);
			}
			elseif ($fitMode == 2) {
				$dest_x = round($mid_w-($src_w/2));
				$dest_y = round($mid_h-($src_h/2));
			}
		}
		elseif ($src_ratio < $ratio) {
			if ($fitMode == 1) {
				$src_y = round(($src_h/2)-$mid_h);
			}
			elseif ($fitMode == 2) {
				$dest_x = round($mid_w-($src_w/2));
				$dest_y = round($mid_h-($src_h/2));
			}
		}
		$this->dest_image = imagecreatetruecolor($dest_w,$dest_h);
		$color = imagecolorallocate($this->dest_image,255,255,255);
		imagefill($this->dest_image,0,0,$color);
		imagecopymerge($this->dest_image,$this->src_image,$dest_x,$dest_y,$src_x,$src_y,$src_w,$src_h,100);
		$border_image = imagecreatefrompng($frameSource);
		imagealphablending($this->dest_image,1);
		imagealphablending($border_image,1);
		imagecopy($this->dest_image,$border_image,0,0,0,0,$dest_w,$dest_h);
		imagedestroy($this->src_image);
		imagedestroy($border_image);
	}
}

/**
 * API wrapper for image merge class
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 */
class ImageMerge extends ImageMergeClass
{
	function ImageMerge($source,$mergedImageName) {
		$this->source = $source;
		$this->mergedImageName = $mergedImageName;
	}
	
	function getMergedImage($dest_w,$dest_h,$fitMode,$frameborder) {
		if (exif_imagetype($this->source) == IMAGETYPE_JPEG) {
			$this->src_image = imagecreatefromjpeg($this->source);
		}
		if (exif_imagetype($this->source) == IMAGETYPE_GIF) {
			$this->src_image = imagecreatefromgif($this->source);
		}
		if (exif_imagetype($this->source) == IMAGETYPE_PNG) {
			$this->src_image = imagecreatefrompng($this->source);
		}
		$this->mergeImage($dest_w,$dest_h,$fitMode,$frameborder);
		imagejpeg($this->dest_image, $this->mergedImageName);
		imagedestroy($this->dest_image);
	}
}
?>