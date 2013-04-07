if (swfobject.hasFlashPlayerVersion("8.0.0")) {
	swfobject.embedSWF("my_flash_file.swf","flash_component","xxx", "xxx","8.0.0","/framework/views/plugins/flashplayer/expressInstall.swf",
	{	// flashvars - variables to send to the Flash component
		somevar: "somevalue"
	},
	{	// params - params for the object tag
		quality: "high"
	},
	{	// Attributes - attributes for the object tag
		id: "flash_record_player"
	});
}
else {
	document.observe("dom:loaded",function() {
		$('flash_component').hide();
		$('flash_warning').show();
	})
}
