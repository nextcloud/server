$(document).ready(function() {
	FileActions.register('all', 'Share', OC.imagePath('core', 'actions/share'), function(filename) {
		createShareDialog(filename, $('#dir').val()+'/'+filename);
	});
	$('.share').click(function(event) {
		event.preventDefault();
		var filenames = getSelectedFiles('name');
		var length = filenames.length;
		var files = '';
		for (var i = 0; i < length; i++) {
			files += $('#dir').val()+'/'+filenames[i]+';';
		}
		var lastFileName = filenames.pop();
		if (filenames.length > 0) {
			filenames = filenames.join(', ')+' and '+lastFileName;
		} else {
			filenames = lastFileName;
		}
		createShareDialog(filenames, files);
	});
	$("input[name=share_type]").live('change', function() {
		$('#private').toggle();
		$('#public').toggle();
	});
	$('.uid_shared_with').live('keyup', function() {
		$(this).autocomplete({
			source: '../apps/files_sharing/ajax/userautocomplete.php',
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
		$(this).html('&nbsp-&nbsp&nbsp');
		$(this).removeClass('add-uid_shared_with fancybutton');
		$(this).addClass('remove-uid_shared_with fancybutton');
	});
	$('button.remove-uid_shared_with').live('click', function(event) {
		event.preventDefault();
		alert("remove");
		// TODO Remove corresponding row
	});
	$('#expire').datepicker({
		dateFormat:'MM d, yy',
		altField: '#expire_time',
		altFormat: 'yy-mm-dd'
	});
});

function createShareDialog(filenames, files) {
	var html = "<div id='dialog' align='center'>";
	html += "<label><input type='radio' name='share_type' value='private' checked='checked' /> Private</label>";
	html += "<label><input type='radio' name='share_type' value='public' /> Public</label>";
	html += "<br />";
	html += "<div id='private'>";
	html += "<label>Share with <input placeholder='User or Group' class='uid_shared_with' /></label>";
	html += "<button id='hey' class='add-uid_shared_with fancybutton'>+</button>";
	html += "<br />";
	html += "<div id='permissions'style='text-align: left'>";
	html += "Permissions"
	html += "<br />";
	html += "<label><input type='checkbox' name='share_permissions' value='1' /> Edit</label><br />";
	html += "<label><input type='checkbox' name='share_permissions' value='2' /> Delete</label><br />";
	html += "</div>";
	html += "</div>";
	html += "<div id='public' style='display: none'>";
	html += "TODO: Construct a public link";
	html += "<input placeholder='Expires' id='expire' />";
	html += "</div>";
	html += "<div>";
	$(html).dialog({
		title: 'Share ' + filenames,
		modal: true,
		close: function(event, ui) {
			$(this).remove();
		},
		buttons: {
			'Share': function() {
				if ($('input[name=share_type]:checked').val() == 'public') {
					// TODO Construct public link
				} else {
					// TODO Check all inputs are valid
					var uid_shared_with = $('.uid_shared_with').val();
					var permissions = 0;
					$(this).find('input:checkbox:checked').each(function() {
						permissions += parseInt($(this).val());
					});
					$.ajax({
						type: 'POST',
						url: '../apps/files_sharing/ajax/share.php',
						cache: false,
						data: '&sources='+encodeURIComponent(files)+'&uid_shared_with[]='+uid_shared_with+'&permissions='+permissions,
						success: function() {
							$('#dialog').dialog('close');
						}
					});
				}
			}
		}
	});
}