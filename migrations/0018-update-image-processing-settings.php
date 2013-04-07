<?php

$migration_success = false;

// Insert new image processing settings:
$query1 = "INSERT INTO `system_settings` (`constant_name`, `friendly_name`, `description`, `value`, `value_type`, `required`, `group_name`)
	VALUES
	('IMG_KEEP_ORIGINALS','Keep Originals','Choose \"Yes\" if you have a need to offer your users downloads of original images. If your images will only ever be viewed within your site, choose \"No\".','No','radios{Yes|No}',1,'Image Processing Options'),
	('IMG_AUTO_ORIENT_ORIGINAL','Auto-Orient Original','Only applicable if you keep originals. When you upload an image that has orientation data from the camera, do you want it auto-oriented so it appears the right way up in web browsers? Select \"No\" if you want to leave the original exactly as-is.','No','radios{Yes|No}',1,'Image Processing Options'),
	('IMG_JPEG_QUALITY','JPEG Image Quality','When JPEG images are uploaded, this is the quality that will be applied to alternate sizes that get generated.','90','slider[0|100|1|%]',1,'Image Processing Options'),
	('IMG_PNG_COMPRESSION','PNG Image Compression','When PNG images are uploaded, this is the compression level that will be applied to alternate sizes that get generated.','9','slider[0|9|1|]',1,'Image Processing Options')";

// Updated the existing ones so they go in the same configuration group as the new ones:
$query2 = "UPDATE `system_settings` SET `group_name` = 'Image Processing Options' WHERE `constant_name` = 'IMG_WIDTH' OR `constant_name` = 'IMG_HEIGHT' OR `constant_name` = 'THUMB_WIDTH' OR `constant_name` = 'THUMB_HEIGHT'";

$migration_success = (DB::query($query1) && DB::query($query2));
