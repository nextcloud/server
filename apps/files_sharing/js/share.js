$(document).ready(function() {
	$(this).click(function(event) {
		if ($(event.target).parents().index($('#dropdown')) == -1) {
			if ($('#dropdown').is(':visible')) {
				$('#dropdown').hide('blind', function() {
					$('#dropdown').remove();
					$('tr').removeClass('mouseOver');
				});
			}
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
		var permissions;
		if (this.checked) {
			permissions = 1;
		} else {
			permissions = 0;
		}
		var source = $('#dropdown').data('file');
		var uid_shared_with = $(this).parent().data('uid_shared_with');
		var data = 'source='+encodeURIComponent(source)+'&uid_shared_with='+encodeURIComponent(uid_shared_with)+'&permissions='+encodeURIComponent(permissions);
		$.ajax({
			type: 'GET',
			url: OC.linkTo('files_sharing','ajax/setpermissions.php'),
			cache: false,
			data: data
		});
	});
	$('.unshare').live('click', function(event) {
		// TODO Fix unshare
		event.preventDefault();
		event.stopPropagation();
		var source = $('#dropdown').data('file');
		var uid_shared_with = $(this).parent().data('uid_shared_with');
		var data = 'source='+encodeURIComponent(source)+'&uid_shared_with='+encodeURIComponent(uid_shared_with);
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
						$('#link').data('token', token);
						$('#link').val('http://'+location.host+OC.linkTo('files_publiclink','get.php')+'?token='+token);
						$('#link').show('blind');
					}
				}
			});
		} else {
			var token = $('#link').data('token');
			var data = 'token='+token;
			$.ajax({
				type: 'GET',
				url: OC.linkTo('files_publiclink','ajax/deletelink.php'),
				cache: false,
				data: data,
				success: function(){
					$('#link').hide('blind');
				}
			});
		}
	});
	$('#link').live('click', function() {
		$(this).focus();
		$(this).select();
	});
});

function createShareDropdown(filenames, files) {
	var html = "<div id='dropdown' data-file='"+files+"'>";
	html += "<div id='private'>";
	html += "<input placeholder='User or Group' id='uid_shared_with' />";
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
				if (typeof(index) == 'string') {
					// TODO typeof not always working, group together users that have parent folders shared with them
					list += "<li>disabled";
					list += index;
					list += row.uid_shared_with;
					list += "</li>";
				} else {
					list += "<li data-uid_shared_with='"+row.uid_shared_with+"'>";
					list += row.uid_shared_with;
					var checked;
					if (row.permissions > 0) {
						checked = "checked='checked'";
					}
					list += "<input type='checkbox' name='permissions' id='"+index+"' class='permissions' "+checked+" /><label for='"+index+"'>can edit</label>";
					list += "<a href='' title='Unshare' class='unshare' data-uid_shared_with='"+row.uid_shared_with+"'><img class='svg' src='"+OC.imagePath('core','actions/delete')+"'/></a>";
					list += "</li>";
					
				}
			});
			list += "</ul>";
			$(list).appendTo('#shared_list');
		}
	});
	$.getJSON(OC.linkTo('files_publiclink','ajax/getlink.php'), { path: files }, function(token) {
		if (token) {
			$('#makelink').attr('checked', true);
			$('#link').data('token', token);
			$('#link').val('http://'+location.host+OC.linkTo('files_publiclink','get.php')+'?token='+token);
			$('#link').show('blind');
		}
	});
	$('#dropdown').show('blind');
}
