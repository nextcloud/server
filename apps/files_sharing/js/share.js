$(document).ready(function() {
	FileActions.register('all', 'Share', OC.imagePath('core', 'actions/share'), function(filename) {
		createShareDialog(filename);
	});
	$('.share').click(function(event) {
		event.preventDefault();
		createShareDialog(getSelectedFiles('name'));
	});
	$("input[name=share_type]").live('change', function() {
		$('#private').toggle();
		$('#public').toggle();
	});
	$('.uid_shared_with').live('keyup', function() {
		$(this).autocomplete({
			source: "../apps/files_sharing/ajax/userautocomplete.php",
			minLength: 1
		});
	});
	$('button.add-uid_shared_with').live('click', function(event) {
		event.preventDefault();
		// TODO Make sure previous textbox has a valid user or group name
		var html = "<br />";
		html += "<label>Share with <input placeholder='User or Group' class='uid_shared_with' /></label>";
		html += "<button class='add-uid_shared_with fancybutton'>+</button>";
		$(html).insertAfter('.add-uid_shared_with');
		$(this).html("&nbsp-&nbsp&nbsp");
		$(this).removeClass("add-uid_shared_with fancybutton");
		$(this).addClass("remove-uid_shared_with fancybutton");
	});
	$('button.remove-uid_shared_with').live('click', function(event) {
		event.preventDefault();
		alert("remove");
		// TODO Remove corresponding row
	});
	$('#toggle-private-advanced').live('click', function(event) {
		event.preventDefault();
		$('#private-advanced').toggle();
	});
	$('#expire').datepicker({
		dateFormat:'MM d, yy',
		altField: "#expire_time",
		altFormat: "yy-mm-dd"
	});
	$('button.submit').live('click', function(event) {
		event.preventDefault();
		if ($("input[name=share_type]:checked").val() == 'public') {
			// TODO Construct public link
		} else {
			// TODO Check all inputs are valid
			var sources = "";
			var files = getSelectedFiles('name');
			var length = files.length;
			for (var i = 0; i < length; i++) {
				sources += "&sources[]=" + $('#dir').val() + "/" + files[i];
			}
			var uid_shared_with = $('.uid_shared_with').val();
			var permissions = 0;
			var data = sources+'&uid_shared_with[]='+uid_shared_with+'&permissions='+permissions;
			$.ajax({
				type: 'GET',
				url: '../apps/files_sharing/ajax/share.php',
				cache: false,
				data: data,
				success: function() {
					$('#dialog').dialog('close');
				}
			});
		}
	});
});

function createShareDialog(files) {
	var html = "<div id='dialog' title='Share "+files+"' align='center'>";
	html += "<label><input type='radio' name='share_type' value='private' checked='checked' /> Private</label>";
	html += "<label><input type='radio' name='share_type' value='public' /> Public</label>";
	html += "<br />";
	html += "<div id='private'>";
	html += "<label>Share with <input placeholder='User or Group' class='uid_shared_with' /></label>";
	html += "<button id='hey' class='add-uid_shared_with fancybutton'>+</button>";
	html += "<br />";
	html += "<a id='toggle-private-advanced'>Advanced</a>";
	html += "<br />";
	html += "<div id='private-advanced' style='display: none; text-align: left'>";
	html += "<label><input type='checkbox' name='share_permissions' value='read' checked='checked' disabled='disable' /> Read</label><br />";
	html += "<label><input type='checkbox' name='share_permissions' value='write' /> Write</label><br />";
	html += "<label><input type='checkbox' name='share_permissions' value='rename' /> Rename</label><br />";
	html += "<label><input type='checkbox' name='share_permissions' value='delete' /> Delete</label><br />";
	html += "</div>";
	html += "</div>";
	html += "<div id='public' style='display: none'>";
	html += "TODO: Construct a public link";
	html += "<input placeholder='Expires' id='expire' />";
	html += "</div>";
	html += "<br />";
	html += "<button class='submit fancybutton'>Share</button>";
	html += "<div>";
	$(html).dialog({
		close: function(event, ui) {
			$(this).remove();
		}
	});
}