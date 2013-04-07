// JavaScript Document

/**
 * Functions used by photo gallery plugin
**/
$(document).observe("dom:loaded",function() {
	if ($$('.delete_album')) {
		$$('.delete_album').each(function(el) {
			el.observe("click", function(event) {
				if(!window.confirm("Are you sure you want to delete this album? All photos in the album will be removed. This action cannot be undone.")) {
					Event.stop(event);
					return false;
				}
			});
		});
	}
	if ($$('.delete_photo')) {
		$$('.delete_photo').each(function(el) {
			el.observe("click", function(event) {
				if(!window.confirm("Are you sure you want to delete this photo? This action cannot be undone.")) {
					Event.stop(event);
					return false;
				}
			});
		});
	}
	// Make albums sortable:
	if ($('album_sort_form')) {
		Biscuit.Crumbs.Sortable.create('albums','div','photos','album_list',{
			action: 'resort_albums',
			hoverclass: 'draggable',
			array_name: 'album_sort',
			constraint: false,
			overlap: 'horizontal'
		});
	}
	// Make photos sortable:
	if ($('photo_sort_form')) {
		Biscuit.Crumbs.Sortable.create('photos','div','photos','photo_list',{
			action: 'resort_photos',
			hoverclass: 'draggable',
			array_name: 'photo_sort',
			constraint: false,
			overlap: 'horizontal'
		});
	}
	// add form validation on submit
	if ($('photo_editor')) {
		$('photo_editor').observe("submit", function(event){
			Event.stop(event);
			new Biscuit.Ajax.FormValidator('photo_editor');
		});
	}
	// add form validation on submit
	if ($('album_editor')) {
		$('album_editor').observe("submit", function(event){
			Event.stop(event);
			new Biscuit.Ajax.FormValidator('album_editor',{});
		});
	}
});
