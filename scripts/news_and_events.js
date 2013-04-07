// JavaScript Document

/**
 * Functions used by news and events plugin
**/
$(document).observe("dom:loaded",function() {
	// add confirm to delete news/event
	var delete_item = $('delete_item');
	if (delete_item) {
		delete_item.observe("click", function(event){
	    	if(!window.confirm("Are you sure you want to delete this item? This action cannot be undone.")){
	    		Event.stop(event);
				return false;
			}
		});
	};
	var delete_items = $$('.delete_item');
	if (delete_items) {
		delete_items.each(function(el) {
			el.observe("click", function(event) {
		    	if(!window.confirm("Are you sure you want to delete this item? This action cannot be undone.")){
		    		Event.stop(event);
					return false;
				}
			});
		});
	}
	if ($('remove_attachment')) {
		$('remove_attachment').observe('click',function(e) {
			var el = Event.element(e);
			$('file_attachment').disabled = el.checked;
		});
	}

	// add form validation on submit
	if ($('news_event_edit_form')) {
		$('news_event_edit_form').observe("submit", function(event){
			Event.stop(event);
			new Biscuit.Ajax.FormValidator('news_event_edit_form');
		});
	}
});
