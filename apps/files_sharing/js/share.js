$(document).ready(function() {
	var shared_status = {};
	if (typeof FileActions !== 'undefined') {
		FileActions.register('all', 'Share', function(filename) {
			var icon;
			var file = $('#dir').val()+'/'+filename;
			if(shared_status[file])
				return shared_status[file].icon;
			$.ajax({
				type: 'GET',
				url: OC.linkTo('files_sharing', 'ajax/getitem.php'),
				dataType: 'json',
				data: {source: file},
				async: false,
				success: function(users) {
					if (users) {
						icon = OC.imagePath('core', 'actions/shared');
						$.each(users, function(index, row) {
							if (row.uid_shared_with == 'public') {
								icon = OC.imagePath('core', 'actions/public');
							}
						});
					} else {
						icon = OC.imagePath('core', 'actions/share');
					}
					shared_status[file]= { timestamp: new Date().getTime(), icon: icon };
				}
			});
			return icon;
		}, function(filename) {
			if (($('#dropdown').length > 0)) {
				$('#dropdown').hide('blind', function() {
					var dropdownFile = $('#dropdown').data('file') 
					var file = $('#dir').val()+'/'+filename;
					$('#dropdown').remove();
					$('tr').removeClass('mouseOver');
					if (dropdownFile != file) {
						createDropdown(filename, file);
					}
				});
			} else {
				createDropdown(filename, $('#dir').val()+'/'+filename);
			}
		});
	};

	$('.share').click(function(event) {
		event.preventDefault();
		event.stopPropagation();
		var filenames = getSelectedFiles('name');
		var length = filenames.length;
		var files = '';
		for (var i = 0; i < length; i++) {
			files += $('#dir').val()+'/'+filenames[i]+';';
		}
		createDropdown(false, files);
	});
	
	$(this).click(function(event) {
		if (!($(event.target).hasClass('drop')) && $(event.target).parents().index($('#dropdown')) == -1) {
			if ($('#dropdown').is(':visible')) {
				delete shared_status[$('#dropdown').data('file')]; //Remove File from icon cache
				$('#dropdown').hide('blind', function() {
					$('#dropdown').remove();
					$('tr').removeClass('mouseOver');
				});
			}
		}
	});
	
	$('#share_with').live('change', function() {
		var source = $('#dropdown').data('file');
		var uid_shared_with = $(this).val();
		var permissions = 0;
		var data = 'sources='+encodeURIComponent(source)+'&uid_shared_with='+encodeURIComponent(uid_shared_with)+'&permissions='+encodeURIComponent(permissions);
		$.ajax({
			type: 'POST',
			url: OC.linkTo('files_sharing','ajax/share.php'),
			cache: false,
			data: data,
			success: function(result) {
				if (result !== 'false') {
					addUser(uid_shared_with, permissions, false);
				}
			}
		});
	});
	
	$('#shared_list > li').live('mouseenter', function(event) {
		$(':hidden', this).show();
	});
	
	$('#shared_list > li').live('mouseleave', function(event) {
		$('a', this).hide();
		if (!$('input:[type=checkbox]', this).is(':checked')) {
			$('input:[type=checkbox]', this).hide();
			$('label', this).hide();
		}
	});
	
	$('.permissions').live('change', function() {
		var permissions = (this.checked) ? 1 : 0;
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
		event.preventDefault();
		var user = $(this).parent();
		var source = $('#dropdown').data('file');
		var uid_shared_with = user.data('uid_shared_with');
		var data = 'source='+encodeURIComponent(source)+'&uid_shared_with='+encodeURIComponent(uid_shared_with);
		$.ajax({
			type: 'GET',
			url: OC.linkTo('files_sharing','ajax/unshare.php'),
			cache: false,
			data: data,
			success: function() {
				var option = '<option value="'+uid_shared_with+'">'+uid_shared_with+'</option>';
				$(user).remove();
				$(option).appendTo('#share_with');
				$('#share_with').trigger('liszt:updated');
			}
		});
	});
	
	$('#makelink').live('change', function() {
		if (this.checked) {
			var source = $('#dropdown').data('file');
			var uid_shared_with = 'public';
			var permissions = 0;
			var data = 'sources='+encodeURIComponent(source)+'&uid_shared_with='+encodeURIComponent(uid_shared_with)+'&permissions='+encodeURIComponent(permissions);
			$.ajax({
				type: 'POST',
				url: OC.linkTo('files_sharing','ajax/share.php'),
				cache: false,
				data: data,
				success: function(token) {
					if (token) {
						showPublicLink(token);
					}
				}
			});
		} else {
			var source = $('#dropdown').data('file');
			var uid_shared_with = 'public';
			var data = 'source='+encodeURIComponent(source)+'&uid_shared_with='+encodeURIComponent(uid_shared_with);
			$.ajax({
				type: 'GET',
				url: OC.linkTo('files_sharing','ajax/unshare.php'),
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

function createDropdown(filename, files) {
	var html = '<div id="dropdown" class="drop" data-file="'+files+'">';
	html += '<div id="private">';
	html += '<select data-placeholder="User or Group" style="width:220px;" id="share_with" class="chzen-select">';
	html += '<option value=""></option>';
	html += '</select>';
	html += '<ul id="shared_list"></ul>';
	html += '</div>';
	html += '<div id="public">';
	html += '<input type="checkbox" name="makelink" id="makelink" value="1" /><label for="makelink">make public</label>';
	//html += '<input type="checkbox" name="public_link_write" id="public_link_write" value="1" /><label for="public_link_write">allow upload</label>';
	html += '<br />';
	html += '<input id="link" style="display:none; width:90%;" />';
	html += '</div>';
	if (filename) {
		$('tr').filterAttr('data-file',filename).addClass('mouseOver');
		$(html).appendTo($('tr').filterAttr('data-file',filename).find('td.filename'));
	} else {
		$(html).appendTo($('thead .share'));
	}
	$.getJSON(OC.linkTo('files_sharing', 'ajax/userautocomplete.php'), function(users) {
		if (users) {
			$.each(users, function(index, row) {
				$(row).appendTo('#share_with');
			});
			$('#share_with').trigger('liszt:updated');
		}
	});
	$.getJSON(OC.linkTo('files_sharing', 'ajax/getitem.php'), { source: files }, function(users) {
		if (users) {
			$.each(users, function(index, row) {
				if (row.uid_shared_with == 'public') {
					showPublicLink(row.token);
				} else if (isNaN(index)) {
					addUser(row.uid_shared_with, row.permissions, index.substr(0, index.lastIndexOf('-')));
				} else {
					addUser(row.uid_shared_with, row.permissions, false);
				}
			});
		}
	});
	$('#dropdown').show('blind');
	$('#share_with').chosen();
}

function addUser(uid_shared_with, permissions, parentFolder) {
	if (parentFolder) {
		var user = '<li>Parent folder '+parentFolder+' shared with '+uid_shared_with+'</li>';
	} else {
		var checked = ((permissions > 0) ? 'checked="checked"' : 'style="display:none;"');
		var style = ((permissions == 0) ? 'style="display:none;"' : '');
		var user = '<li data-uid_shared_with="'+uid_shared_with+'">';
		user += '<a href="" class="unshare" style="display:none;"><img class="svg" alt="Unshare" src="'+OC.imagePath('core','actions/delete')+'"/></a>';
		user += uid_shared_with;
		user += '<input type="checkbox" name="permissions" id="'+uid_shared_with+'" class="permissions" '+checked+' />';
		user += '<label for="'+uid_shared_with+'" '+style+'>can edit</label>';
		user += '</li>';
	}
	$('#share_with option[value="'+uid_shared_with+'"]').remove();
	$('#share_with').trigger('liszt:updated');
	$(user).appendTo('#shared_list');
}

function showPublicLink(token) {
	$('#makelink').attr('checked', true);
	$('#link').data('token', token);
	$('#link').val(parent.location.protocol+'//'+location.host+OC.linkTo('files_sharing','get.php')+'?token='+token);
	$('#link').show('blind');
}
