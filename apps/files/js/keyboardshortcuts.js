// your file
var Files = Files || {};

// Array Remove - By John Resig (MIT Licensed)
Array.prototype.remove = function(from, to) {
  var rest = this.slice((to || from) + 1 || this.length);
  this.length = from < 0 ? this.length + from : from;
  return this.push.apply(this, rest);
};

Files.bindKeyboardShortcuts = function (document, $){
	var keys = []

	$(document).keydown(function(event){//check for modifier keys
		if($.inArray(event.keyCode, keys) == -1)
			keys.push(event.keyCode);
		console.log(event.keyCode);
		
		if($.inArray(78, keys) !== -1 && ($.inArray(224, keys) !== -1 || $.inArray(17, keys) !== -1 || $.inArray(91, keys) !== -1 || $.inArray(93, keys) !== -1)){ //78=n, 224=cmd(firefox), 17=cmd(Opera), 91=leftCmd(WebKit), 93=rightCmd(WebKit)
			event.preventDefault(); //Prevent web browser from responding
		}
	});
	
	$(document).keyup(function(event){
       // do your event.keyCode checks in here
		
		console.log(JSON.stringify(keys));
		
		if($.inArray(78, keys) !== -1 && ($.inArray(224, keys) !== -1 || $.inArray(17, keys) !== -1 || $.inArray(91, keys) !== -1 || $.inArray(93, keys) !== -1)){ //78=n, 224=cmd(firefox), 17=cmd(Opera), 91=leftCmd(WebKit), 93=rightCmd(WebKit)
			if($.inArray(16, keys) !== -1){ //16=shift, New File
				$("#new").addClass("active");
				$(".popup.popupTop").toggle(true);
				$('#new li[data-type="file"]').trigger('click');
				console.log("new file");
				keys.remove($.inArray(78, keys));
			}
			else{ //New Folder
				$("#new").addClass("active");
				$(".popup.popupTop").toggle(true);
				$('#new li[data-type="folder"]').trigger('click');
				console.log("new folder");
				keys.remove($.inArray(78, keys));
			}
		}
		if($("#new").hasClass("active") && $.inArray(27, keys) !== -1){
			$("#controls").trigger('click');
			console.log("close");
		}
		
		keys.remove($.inArray(event.keyCode, keys));
	});
};