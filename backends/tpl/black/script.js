
jQuery(document).ready(function(){
	var title_default   = jQuery("#title").val();
	var content_default = jQuery("#content").val();
	
	if(ID == '')
	{
		jQuery("#title").addClass("placeholder");
		jQuery("#content").addClass("placeholder");
		
		
		jQuery("#title").click(function() {
			remove_class("#title", title_default);
		});
		
		jQuery("#content").click(function() {
			remove_class("#content", content_default);
		});
		
		jQuery("#title").bind('paste', null, function() {
			remove_class("#title", title_default);
		});
		
		jQuery("#content").bind('paste', null, function() {
			remove_class("#content", content_default);
		});
		
		jQuery("#title").blur(function() {
			add_class("#title", title_default);
		});
		
		jQuery("#content").blur(function() {
			add_class("#content", content_default);
		});
	}
});


function remove_class(id, _default)
{
	var curr = jQuery(id).val();
	if(curr == _default)
	{
		jQuery(id).val('');
		jQuery(id).removeClass("placeholder");
	}
}

function add_class(id, _default)
{
	var curr = jQuery(id).val();
	if(curr == '')
	{
		jQuery(id).val(_default);
		jQuery(id).addClass("placeholder");
	}
}


/**
 * Insert a tab at the current text position in a textarea
 * Jan Dittmer, jdittmer@ppp0.net, 2005-05-28
 * Inspired by http://www.forum4designers.com/archive22-2004-9-127735.html
 * Tested on: 
 *   Mozilla Firefox 1.0.3 (Linux)
 *   Mozilla 1.7.8 (Linux)
 *   Epiphany 1.4.8 (Linux)
 *   Internet Explorer 6.0 (Linux)
 * Does not work in: 
 *   Konqueror (no tab inserted, but focus stays)
 */
function insertTab(event,obj) {
	var tabKeyCode = 9;
	if (event.which) // mozilla
		var keycode = event.which;
	else // ie
		var keycode = event.keyCode;
	if (keycode == tabKeyCode) {
		if (event.type == "keydown") {
			if (obj.setSelectionRange) {
				// mozilla
				var s = obj.selectionStart;
				var e = obj.selectionEnd;
				obj.value = obj.value.substring(0, s) + 
					"\t" + obj.value.substr(e);
				obj.setSelectionRange(s + 1, s + 1);
				obj.focus();
			} else if (obj.createTextRange) {
				// ie
				document.selection.createRange().text="\t";
				obj.onblur = function() { this.focus(); this.onblur = null; };
			} else {
				// unsupported browsers
			}
		}
		if (event.returnValue) // ie ?
			event.returnValue = false;
		if (event.preventDefault) // dom
			event.preventDefault();
		return false; // should work in all browsers
	}
	return true;
}