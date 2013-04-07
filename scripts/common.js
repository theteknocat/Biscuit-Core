var iTimer = null;
// JavaScript Document
function write_mailto(domain,user,display_name) {
	if (display_name == "") {
		display_name = user+"@"+domain;
	}
	document.write('<a href="mailto:'+user+'@'+domain+'">'+display_name+'</a>');
}

function isInteger(s)
{   var i;
    for (i = 0; i < s.length; i++)
    {   
        // Check that current character is number.
        var c = s.charAt(i);
        if (((c < "0") || (c > "9"))) return false;
    }
    // All characters are numbers.
    return true;
}

function stripCharsInBag(s, bag)
{   var i;
    var returnString = "";
    // Search through string\'s characters one by one.
    // If character is not in bag, append to returnString.
    for (i = 0; i < s.length; i++)
    {   
        // Check that current character isn\'t whitespace.
        var c = s.charAt(i);
        if (bag.indexOf(c) == -1) returnString += c;
    }
    return returnString;
}

function checkImageStatus(callback) {
	clearTimeout(iTimer);
	var loadedCount = 0;
	var pageImages = $$('img.preload');
	pageImages.each(function(element) {
		if (element.complete == true) {
			loadedCount += 1;
		}
	});
	if (loadedCount == pageImages.length) {
		eval(callback);
	}
	else {
		iTimer = setTimeout("checkImageStatus('"+callback+"');",100);
	}
}
