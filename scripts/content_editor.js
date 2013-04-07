// JavaScript Document

/**
 * Functions used by content editor plugin
**/
var PageContent = {
	AddEditHandlers: function() {
		// add form validation on submit
		$('content_edit_form').observe("submit", function(event){
			Event.stop(event);
			new Biscuit.Ajax.FormValidator('content_edit_form',{});
		});
	}
}
