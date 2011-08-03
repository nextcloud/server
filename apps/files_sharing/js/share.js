$(document).ready(function() {
	$(this).click(function() {
		if ($('#dropdown').is(':visible')) {
			$('#dropdown').hide('blind', function() {
				$('#dropdown').remove();
				$('tr').removeClass('mouseOver');
			});
		}
	});
	FileActions.register('all', 'Share', OC.imagePath('core', 'actions/share'), function(filename) {
		createShareDropdown(filename, $('#dir').val()+'/'+filename);
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
		createShareDropdown(filenames, files);
	});
	$('#uid_shared_with').live('keyup', function() {
		$(this).autocomplete({
			source: OC.linkTo('files_sharing','ajax/userautocomplete.php')
		});
		$('.ui-autocomplete').click(function(event) {
			event.stopPropagation();
		});
	});
	$('.permissions').live('change', function() {
		// TODO Modify item ajax call
	});
	$('.unshare').live('click', function() {
		var source = $('#dropdown').data('file');
		var uid_shared_with = $(this).data('uid_shared_with');
		var data='source='+encodeURIComponent(source)+'&uid_shared_with='+encodeURIComponent(uid_shared_with);
		$.ajax({
			type: 'GET',
			url: OC.linkTo('files_sharing','ajax/unshare.php'),
			cache: false,
			data: data,
			success: function() {
				$(this).parent().remove();
			}
		});
	});
	$('#makelink').live('change', function() {
		if (this.checked) {
			var path = $('#dropdown').data('file');
			var expire = 0;
			var data = 'path='+path+'&expire='+expire;
			$.ajax({
				type: 'GET',
				url: OC.linkTo('files_publiclink','ajax/makelink.php'),
				cache: false,
				data: data,
				success: function(token) {
					if (token) {
						var link = OC.linkTo('files_publiclink','get.php')+'?token='+token;
						$('#link').show('blind');
						$('#link').val(link);
					}
				}
			});
		} else {
			var token = $(this).attr('data-token');
			var data = "token="+token;
			$.ajax({
				type: 'GET',
				url: OC.linkTo('files_publiclink','ajax/deletelink.php'),
				cache: false,
				data: data,
				success: function(){
					$('#token').hide('blind');
				}
			});
		}
	});
});

function createShareDropdown(filenames, files) {
	var html = "<div id='dropdown' data-file='"+files+"'>";
	html += "<div id='private'>";
	html += "<input placeholder='User or Group' id='uid_shared_with' />";
	html += "<input type='checkbox' name='permissions' id='permissions' value='1' /><label for='permissions'>can edit</label>";
	html += "<div id='shared_list'></div>";
	html += "</div>";
	html += "<div id='public'>";
	html += "<input type='checkbox' name='makelink' id='makelink' value='1' /><label for='makelink'>make public</label>";
	html += "<input type='checkbox' name='public_link_write' id='public_link_write' value='1' /><label for='public_link_write'>allow upload</label>";
	html += "<br />";
	html += "<input id='link' style='display:none;width:100%' />";
	html += "</div>";
	$('tr[data-file="'+filenames+'"]').addClass('mouseOver');
	$(html).appendTo($('tr[data-file="'+filenames+'"] td.filename'));
	$.getJSON(OC.linkTo('files_sharing','ajax/getitem.php'), { source: files }, function(users) {
		if (users) {
			var list = "<ul>";
			$.each(users, function(index, row) {
				list += "<li>";
				list += row.uid_shared_with;
				list += "<input type='checkbox' name='permissions' data-uid_shared_with='"+row.uid_shared_with+"' /><label>can edit</label>";
				list += "<a href='#' title='Unshare' class='unshare' data-uid_shared_with='"+row.uid_shared_with+"'><img src='"+OC.imagePath('core','actions/delete')+"'/></a>";
				list += "</li>";
				if (row.permissions > 0) {
					$('share_private_permissions').prop('checked', true);
				}
			});
			list += "</ul>";
			$(list).appendTo('#shared_list');
		}
	});
	// TODO Create gettoken.php
	//$.getJSON(OC.linkTo('files_publiclink','ajax/gettoken.php'), { path: files }, function(token) {
	var token;
		if (token) {
			var link = OC.linkTo('files_publiclink','get.php')+'?token='+token;
			$('#makelink').attr('checked', true);
			$('#link').show('blind');
			$('#link').val(link);
		}
	//});
	$('#dropdown').show('blind');
	$('#dropdown').click(function(event) {
		event.stopPropagation();
	});
}
