<?php
/**
 * This file customized to provide a standard upload form that will be shown for users that do not have Flash.
 *
 * @author Peter Epp
 */
require_once('config_tinybrowser.php');
// Set language
if(isset($tinybrowser['language']) && file_exists('langs/'.$tinybrowser['language'].'.php'))
	{
	require_once('langs/'.$tinybrowser['language'].'.php'); 
	}
else
	{
	require_once('langs/en.php'); // Falls back to English
	}
require_once('fns_tinybrowser.php');

// Check session, if it exists
if(session_id() != '')
	{
	if(!isset($_SESSION[$tinybrowser['sessioncheck']]))
		{
		echo TB_DENIED;
		exit;
		}
	}

if(!$tinybrowser['allowupload'])
	{
	echo TB_UPDENIED;
	exit;
	}

// Assign get variables
$validtypes = array('image','media','file');
$typenow = ((isset($_GET['type']) && in_array($_GET['type'],$validtypes)) ? $_GET['type'] : 'image');
$foldernow = str_replace(array('../','..\\','./','.\\'),'',($tinybrowser['allowfolders'] && isset($_REQUEST['folder']) ? urldecode($_REQUEST['folder']) : ''));
$passfolder = '&folder='.urlencode($foldernow);
$passfeid = (isset($_GET['feid']) && $_GET['feid']!='' ? '&feid='.$_GET['feid'] : '');
$passupfeid = (isset($_GET['feid']) && $_GET['feid']!='' ? $_GET['feid'] : '');

// Assign upload path
$uploadpath = urlencode($tinybrowser['path'][$typenow].$foldernow);

// Assign directory structure to array
$uploaddirs=array();
dirtree($uploaddirs,$tinybrowser['filetype'][$typenow],$tinybrowser['docroot'],$tinybrowser['path'][$typenow]);

// determine file dialog file types
switch ($_GET['type'])
	{
	case 'image':
		$filestr = TB_TYPEIMG;
		break;
	case 'media':
		$filestr = TB_TYPEMEDIA;
		break;
	case 'file':
		$filestr = TB_TYPEFILE;
		break;
	}
$fileexts = str_replace(",",";",$tinybrowser['filetype'][$_GET['type']]);
$filelist = $filestr.' ('.$tinybrowser['filetype'][$_GET['type']].')';

// Initalise alert array
$notify = array(
	'type' => array(),
	'message' => array()
);
$goodqty = (isset($_GET['goodfiles']) ? $_GET['goodfiles'] : 0);
$badqty = (isset($_GET['badfiles']) ? $_GET['badfiles'] : 0);
$dupqty = (isset($_GET['dupfiles']) ? $_GET['dupfiles'] : 0);

if($goodqty>0)
	{
	$notify['type'][]='success';
	$notify['message'][]=sprintf(TB_MSGUPGOOD, $goodqty);
	}
if($badqty>0)
	{
	$notify['type'][]='failure';
	$notify['message'][]=sprintf(TB_MSGUPBAD, $badqty);
	}
if($dupqty>0)
	{
	$notify['type'][]='failure';
	$notify['message'][]=sprintf(TB_MSGUPDUP, $dupqty);
	}
if(isset($_GET['permerror']))
	{
	$notify['type'][]='failure';
	$notify['message'][]=sprintf(TB_MSGUPFAIL, $tinybrowser['docroot'].$tinybrowser['path'][$typenow]);
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>TinyBrowser :: <?php echo TB_UPLOAD; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Pragma" content="no-cache" />
<?php
if($passfeid == '' && $tinybrowser['integration']=='tinymce')
	{
	?><link rel="stylesheet" type="text/css" media="all" href="<?php echo $tinybrowser['tinymcecss']; ?>" /><?php 
	}
else
	{
	?><link rel="stylesheet" type="text/css" media="all" href="css/stylefull_tinybrowser.css" /><?php 
	}
?>
<link rel="stylesheet" type="text/css" media="all" href="css/style_tinybrowser.css.php" />
<script type="text/javascript" src="js/swfobject.js"></script>
<script type="text/javascript">
function uploadComplete(url) {
document.location = url;
}
</script>
</head>
<body onload='
      var so = new SWFObject("flexupload.swf", "mymovie", "100%", "340", "9", "#ffffff");
      so.addVariable("folder", "<?php echo $uploadpath; ?>");
      so.addVariable("uptype", "<?php echo $typenow; ?>");
      so.addVariable("destid", "<?php echo $passupfeid; ?>");
      so.addVariable("maxsize", "<?php echo $tinybrowser['maxsize'][$_GET['type']]; ?>");
      so.addVariable("sessid", "<?php echo session_id(); ?>");
      so.addVariable("obfus", "<?php echo md5($_SERVER['DOCUMENT_ROOT'].$tinybrowser['obfuscate']); ?>");
      so.addVariable("filenames", "<?php echo $filelist; ?>");
      so.addVariable("extensions", "<?php echo $fileexts; ?>");
      so.addVariable("filenamelbl", "<?php echo TB_FILENAME; ?>");
      so.addVariable("sizelbl", "<?php echo TB_SIZE; ?>");
      so.addVariable("typelbl", "<?php echo TB_TYPE; ?>");
      so.addVariable("progresslbl", "<?php echo TB_PROGRESS; ?>");
      so.addVariable("browselbl", "<?php echo TB_BROWSE; ?>");
      so.addVariable("removelbl", "<?php echo TB_REMOVE; ?>");
      so.addVariable("uploadlbl", "<?php echo TB_UPLOAD; ?>");
      so.addVariable("uplimitmsg", "<?php echo TB_MSGMAXSIZE; ?>");
      so.addVariable("uplimitlbl", "<?php echo TB_TTLMAXSIZE; ?>");
      so.addVariable("uplimitbyte", "<?php echo TB_BYTES; ?>");
      so.addParam("allowScriptAccess", "always");
      so.addParam("type", "application/x-shockwave-flash");
      so.write("flashcontent");'>
<?php
if(count($notify['type'])>0) alert($notify);
form_open('foldertab',false,'upload.php','?type='.$typenow.$passfeid);
?>
<div class="tabs">
<ul>
<li id="browse_tab"><span><a href="tinybrowser.php?type=<?php echo $typenow.$passfolder.$passfeid ; ?>"><?php echo TB_BROWSE; ?></a></span></li>
<li id="upload_tab" class="current"><span><a href="upload.php?type=<?php echo $typenow.$passfolder.$passfeid ; ?>"><?php echo TB_UPLOAD; ?></a></span></li>
<?php
if($tinybrowser['allowedit'] || $tinybrowser['allowdelete'])
	{
	?><li id="edit_tab"><span><a href="edit.php?type=<?php echo $typenow.$passfolder.$passfeid ; ?>"><?php echo TB_EDIT; ?></a></span></li>
	<?php 
	}
if($tinybrowser['allowfolders'])
	{
	?><li id="folders_tab"><span><a href="folders.php?type=<?php echo $typenow.$passfolder.$passfeid; ?>"><?php echo TB_FOLDERS; ?></a></span></li><?php
	}
// Display folder select, if multiple exist
if(count($uploaddirs)>1)
	{
	?><li id="folder_tab" class="right"><span><?php
	form_select($uploaddirs,'folder',TB_FOLDERCURR,urlencode($foldernow),true);
	?></span></li><?php
	}
?>
</ul>
</div>
</form>
<div class="panel_wrapper">
<div id="general_panel" class="panel currentmod">
<fieldset>
<legend><?php echo TB_UPLOADFILES; ?></legend>
<?php

	// Let the user know the max allowed upload size (per file), both for the Flash uploader and the standard upload form:
?>
	<p style="width: 550px;margin: 10px auto">Maximum allowed size for each uploaded file: <?php echo ini_get('upload_max_filesize') ?></p>
    <div id="flashcontent">
		<div id="standard-upload-form" style="width: 550px;margin: 0 auto">
    	<?php
    	// Standard form for file upload for non-Flash users. Will be replaced with the Flash component by SWFobject on load if Flash is present.
		if ($passupfeid == 'null') {
			$passupfeid = '';
		}
		$submit_url = 'upload_file.php?standard_upload_form=1&amp;folder='.$uploadpath.'&amp;type='.$typenow.'&amp;feid='.$passupfeid.'&amp;obfuscate='.md5($_SERVER['DOCUMENT_ROOT'].$tinybrowser['obfuscate']).'&amp;sessidpass='.session_id();

		// Construct a text string to let the user know what file types they can upload:
		$filetype_str = $tinybrowser['filetype'][$typenow];
		$filetype_str = str_replace('*.','',$filetype_str);
		$filetype_str = strtoupper($filetype_str);
		$display_type = $typenow.(($typenow != 'media') ? 's' : '');
		if ($filetype_str == '*') {
			$allowed_str = ' of any type.';
		} else {
			$allowed_str = ' with the following extensions: '.$filetype_str;
		}
		?>
		<form name="standard-upload-form" action="<?php echo $submit_url ?>" accept-charset="utf-8" enctype="multipart/form-data" method="POST">
			<input type="file" name="Filedata" value="">
			<input type="submit" name="Upload" value="Upload">
		</form>
		<p>Select a file to upload and click the "Upload" button. You must upload one file at a time.</p>
		<p>You may upload <?php echo $display_type ?> <?php echo $allowed_str ?></p>
		<p><strong>Note:</strong> If you install Adobe Flash Player 8 or newer, you can use the enhanced, multiple-file upload widget.</p>
		</div>
    </div>
</fieldset></div></div>
</body>
</html>
