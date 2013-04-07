<?php
	$content = &$PageContentManager->content;
?>
<form name="content_edit_form" id="content_edit_form" method="post" accept-charset="utf-8" action="" class="admin">
	<?php echo RequestTokens::render_token_field(); ?>
	<div class="controls">
		<input type="submit" name="content_save_bttn1" value="Save" class="rightfloat SubmitButton">
		<a href="<?php echo $PageContentManager->url('index',$content->id()) ?>" class="leftfloat">Cancel</a>
		<a href="<?php echo $PageManager->url('index') ?>" class="leftfloat">Manage Pages</a>
	</div>
	<fieldset>
		<legend>Meta Data</legend>
		<p><label for="page_title">*Title:</label><input type="text" class="text" id="page_title" name="content_data[title]" value="<?php echo $content->title()?>"></p>
		<?php
		if ($content->shortname() != "index") {
			?>
		<p><label for="page_parent">*Parent Section:</label><select name="content_data[parent]" id="page_parent">
			<?php echo $PageManager->render_parent_section_options($content->parent(),$content->shortname(),'Tab Menu') ?>
		</select></p>
			<?php
		} else {
			?>
		<input type="hidden" name="content_data[parent]" id="page_parent" value="<?php echo $content->parent() ?>">
			<?php
		}
		?>
		<p><label for="page_description">Description:</label><textarea style="width: 450px; height: 28px" id="page_description" name="content_data[description]" rows="5" cols="40"><?php echo $content->description()?></textarea></p>
		<p><label for="page_keywords">Keywords:</label><textarea style="width: 450px; height: 28px" id="page_keywords" name="content_data[keywords]" rows="5" cols="40"><?php echo $content->keywords()?></textarea></p>
	</fieldset>
	<h3>Content:</h3>
	<p style="clear: both; padding: 0"><textarea id="content_editor" name="content_data[content]" rows="20" cols="100"><?php echo H::purify_html($content->content(),array('css_allowed' => array('width','height','text-align','text-decoration','padding','padding-left','margin','border')))?></textarea></p>
	<div class="controls">
		<input type="submit" name="content_save_bttn2" value="Save" class="rightfloat SubmitButton">
		<a href="<?php echo $PageContentManager->url('index',$content->id()) ?>" class="leftfloat">Cancel</a>
	</div>
</form>
<script type="text/javascript" charset="utf-8">
	document.observe("dom:loaded",function() {
		PageContent.AddEditHandlers();
		Biscuit.Session.KeepAlive.init_form_observer();
	});
	tinyMCE.init({
		mode : "exact",
		elements: "content_editor",
		theme: 'advanced',
		theme_advanced_buttons1: 'undo,redo,|,search,replace,|,justifyleft,justifycenter,justifyright,justifyfull,|,indent,outdent,|,bullist,numlist,|,hr,|,link,unlink,image',
		theme_advanced_buttons2: 'bold,italic,underline,|,sup,sub,|,formatselect,styleselect,|,removeformat',
		theme_advanced_buttons3: null,
		theme_advanced_buttons4: null,
		theme_advanced_buttons5: null,
		theme_advanced_buttons6: null,
		theme_advanced_toolbar_align: 'left',
		theme_advanced_toolbar_location: 'top',
		theme_advanced_resizing: true,
		theme_advanced_resize_horizontal: false,
		theme_advanced_statusbar_location: 'bottom',
		theme_advanced_blockformats: "p,h1,h2,h3,h4",
		relative_urls: false,
		remove_script_host: true,
		document_base_url: "<?php echo STANDARD_URL ?>/",
		skin: 'o2k7',
		skin_variant: 'silver',
		width: 625,
		height: 600,
		cleanup_on_startup: true,
		content_css: '/css/styles_tinymce.css',
		external_link_list_url : "/tiny_mce_link_list",
		plugins : "safari,style,iespell,insertdatetime,preview,media,searchreplace,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,template,inlinepopups,filemanager,imagemanager",
		setup: function(ed) {
			ed.onChange.add(function() {
				Biscuit.Session.KeepAlive.ping();
			});
		}
	});
</script>
