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
	$('#uid_shared_with').live('keyup', function() {
		$(this).autocomplete({
			source: '../apps/files_sharing/ajax/userautocomplete.php'
		});
	});
	$('button.remove-uid_shared_with').live('click', function(event) {
		event.preventDefault();
		alert("remove");
		// TODO Remove corresponding row
	});
});

function createShareDialog(filenames, files) {
	var html = "<div id='dialog' style='position: absolute'>";
	html += "<div id='private'>";
	html += "<label>Share with <input placeholder='User or Group' id='uid_shared_with' /></label>";
	html += "<label><input type='checkbox' name='permissions' value='1' /> allow editing</label><br />";
	html += "<br />";
	html += "<div id='shared_list'></div>";
	$.getJSON(OC.linkTo('files_sharing','ajax/getitem.php'), { source: files }, function(users) {
		var list = "";
		$.each(users, function(index, row) {
			list += row.uid_shared_with;
			list += " <label><input type='checkbox' name='share_private_permissions' value='1' /> allow editing</label><br />";
			if (row.permissions > 0) {
				$('share_private_permissions').prop('checked', true);
			}
		});
		$(list).appendTo('#shared_list');
	});
	html += "</div>";
	html += "<div id='public'>";
	html += "<label><input type='checkbox' name='public_link' value='1' /> make public</label>";
	html += "<label><input type='checkbox' name='public_link_write' value='1' /> writeable</label>";
	html += "<div id='link'>";
	html += "</div>";
	html += "</div>";
	$(html).appendTo($('tr[data-file="'+filenames+'"] '));
}