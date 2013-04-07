// Automatically apply jQuery fancybox to all links to images on any page:
$(document).ready(function() {
	$('.has-linked-images a[href$=".jpg"], .has-linked-images a[href$=".jpeg"], .has-linked-images a[href$=".gif"], .has-linked-images a[href$=".png"]').attr('rel', 'lightbox').fancybox();
});
