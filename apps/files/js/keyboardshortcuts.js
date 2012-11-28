// your file
var Files = Files || {};

function removeA(arr) {
    var what, a = arguments, L = a.length, ax;
    while (L > 1 && arr.length) {
        what = a[--L];
        while ((ax= arr.indexOf(what)) !== -1) {
            arr.splice(ax, 1);
        }
    }
    return arr;
}

Files.bindKeyboardShortcuts = function (document, $){
	var keys = [];
	keyCodes = {
		shift: 16,
		n: 78,
		cmdFirefox: 224,
		cmdOpera: 17,
		leftCmdWebKit: 91,
		rightCmdWebKit: 93,
		esc: 27
	};

	$(document).keydown(function(event){//check for modifier keys
		if($.inArray(event.keyCode, keys) == -1)
			keys.push(event.keyCode);
		console.log(event.keyCode);
		
		if($.inArray(keyCodes.n, keys) !== -1 && ($.inArray(keyCodes.cmdFirefox, keys) !== -1 || $.inArray(keyCodes.cmdOpera, keys) !== -1 || $.inArray(keyCodes.leftCmdWebKit, keys) !== -1 || $.inArray(keyCodes.rightCmdWebKit, keys) !== -1)){
			event.preventDefault(); //Prevent web browser from responding
		}
	});
	
	$(document).keyup(function(event){
       // do your event.keyCode checks in here
		
		console.log(JSON.stringify(keys));
		
		if($.inArray(keyCodes.n, keys) !== -1 && ($.inArray(keyCodes.cmdFirefox, keys) !== -1 || $.inArray(keyCodes.cmdOpera, keys) !== -1 || $.inArray(keyCodes.leftCmdWebKit, keys) !== -1 || $.inArray(keyCodes.rightCmdWebKit, keys) !== -1)){
			if($.inArray(keyCodes.shift, keys) !== -1){ //16=shift, New File
				$("#new").addClass("active");
				$(".popup.popupTop").toggle(true);
				$('#new li[data-type="file"]').trigger('click');
				console.log("new file");
/* 				keys.remove($.inArray(keyCodes.n, keys)); */
				removeA(keys, keyCodes.n);
			}
			else{ //New Folder
				$("#new").addClass("active");
				$(".popup.popupTop").toggle(true);
				$('#new li[data-type="folder"]').trigger('click');
				console.log("new folder");
/* 				keys.remove($.inArray(keyCodes.n, keys)); */
				removeA(keys, keyCodes.n);
			}
		}
		if($("#new").hasClass("active") && $.inArray(keyCodes.esc, keys) !== -1){
			$("#controls").trigger('click');
			console.log("close");
		}
		
/* 		keys.remove($.inArray(event.keyCode, keys)); */
		removeA(keys, event.keyCode);
	});
};