var PageManager = {
	AddIndexHandlers: function() {
		$$('.bttn-del').each(function(el) {
			el.observe("click",function(event) {
				if (!confirm("WARNING!!!\nYou are about to delete the selected page and all of it's content!\n\nYOU CANNOT UNDO THIS ACTION.\n\nAre you sure you want to proceed?")) {
					Event.stop(event);
				}
			});
		});
	},
	AddEditHandlers: function() {
		// add form validation on submit
		$('page_create_form').observe("submit", function(event){
			Event.stop(event);
			new Biscuit.Ajax.FormValidator('page_create_form',{});
		});
	}
}
