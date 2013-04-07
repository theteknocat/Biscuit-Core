tinyMCE.init({
	mode : "textareas",
	theme: 'advanced',
	theme_advanced_buttons1: 'undo,redo,|,search,replace,|,bold,italic,underline,strikethrough,|,bullist,numlist,|,forecolor,backcolor,|,link,unlink',
	theme_advanced_buttons2: null,
	theme_advanced_buttons3: null,
	theme_advanced_buttons4: null,
	theme_advanced_buttons5: null,
	theme_advanced_buttons6: null,
	theme_advanced_toolbar_align: 'center',
	theme_advanced_toolbar_location: 'top',
	theme_advanced_resizing: true,
	theme_advanced_resize_horizontal: false,
	theme_advanced_statusbar_location: 'bottom',
	skin: 'o2k7',
	skin_variant: 'silver',
	cleanup_on_startup: true,
	content_css: '/framework/css/tiny_mce_custom.css',
	plugins : "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,inlinepopups",
	setup: function(ed) {
		ed.onChange.add(function() {
			Biscuit.Session.KeepAlive.ping();
		});
	}
});
