// Use this for Prototype:
document.observe("dom:ready",function() {
	// Put sIFR replacements here, eg:
	if (typeof(sIFR) == "function") {
		sIFR.replaceElement(named({
			sSelector:"body h1",
			sFlashSrc:"/framework/views/sifr/vandenkeere.swf",
			sColor:"#000000",
			sLinkColor:"#000000",
			sBgColor:"#FFFFFF",
			sHoverColor:"#CCCCCC",
			nPaddingTop:20,
			nPaddingBottom:20,
			sFlashVars:"textalign=center&offsetTop=6"
		}));
	}
});
// Use this for jQuery:
jQuery(document).ready(function() {
	// Put sIFR replacements here
});
