OC.Share={
	SHARE_TYPE_USER:0,
	SHARE_TYPE_GROUP:1,
	SHARE_TYPE_PRIVATE_LINK:3,
	item:[],
	statuses:[],
	loadIcons:function(itemType) {
		// Load all share icons
		$.get(OC.filePath('core', 'ajax', 'share.php'), { fetch: 'getItemsSharedStatuses', itemType: itemType }, function(result) {
			if (result && result.status === 'success') {
				$.each(result.data, function(item, hasPrivateLink) {
					// Private links override shared in terms of icon display
					if (itemType == 'file') {
						OC.Share.statuses[item] = hasPrivateLink;
					} else {
						if (hasPrivateLink) {
							$('.share').find('[data-item="'+item+'"]').attr('src', OC.imagePath('core', 'actions/public'));
						} else {
							$('.share').find('[data-item="'+item+'"]').attr('src', OC.imagePath('core', 'actions/shared'));
						}
					}
				});
			}
		});
	},
	loadItem:function(itemType, item) {
		var data = '';
		$.ajax({type: 'GET', url: OC.filePath('core', 'ajax', 'share.php'), data: { fetch: 'getItemShared', itemType: itemType, item: item }, async: false, success: function(result) {
			if (result && result.status === 'success') {
				data = result.data;
			} else {
				data = false;
			}
		}});
		return data;
	},
	share:function(itemType, item, shareType, shareWith, permissions, callback) {
		$.post(OC.filePath('core', 'ajax', 'share.php'), { action: 'share', itemType: itemType, item: item, shareType: shareType, shareWith: shareWith, permissions: permissions }, function(result) {
			if (result && result.status === 'success') {
				if (callback) {
					callback(result.data);
				}
			} else {
				OC.dialogs.alert('Error', 'Error while sharing');
			}
		});
	},
	unshare:function(itemType, shareType, shareWith, callback) {
		$.post(OC.filePath('core', 'ajax', 'share.php'), { action: 'unshare', itemType: itemType, shareType: shareType, shareWith: shareWith }, function(result) {
			if (result && result.status === 'success') {
				if (callback) {
					callback();
				}
			} else {
				OC.dialogs.alert('Error', 'Error while unsharing');
			}
		});
	},
	setPermissions:function(itemType, item, shareType, shareWith, permissions) {
		$.post(OC.filePath('core', 'ajax', 'share.php'), { action: 'setPermissions', itemType: itemType, item: item, shareType: shareType, shareWith: shareWith, permissions: permissions }, function(result) {
			if (!result || result.status !== 'success') {
				OC.dialogs.alert('Error', 'Error while changing permissions');
			}
		});
	},
	showDropDown:function(itemType, item, appendTo) {
		var html = '<div id="dropdown" class="drop" data-item-type="'+itemType+'" data-item="'+item+'">';
		// TODO replace with autocomplete textbox
		html += '<input id="shareWith" type="text" placeholder="Share with" />';
		html += '<div id="sharedWithList">';
		var sharedWith = OC.Share.loadItem(itemType, item);
		if (sharedWith) {
			$.each(sharedWith, function(index, row) {
				html += row.share_with;
				html += '<br />';
			});
		}
		html += '</div>';
		html += '<div id="privateLink">';
		html += '<input type="checkbox" name="privateLinkCheckbox" id="privateLinkCheckbox" value="1" /><label for="privateLinkCheckbox">Share with private link</label>';
		html += '<br />';
		html += '<form id="emailPrivateLink">';
		html += '<input id="privateLinkText" style="display:none; width:90%;" />';
		html += '<input id="email" style="display:none; width:65%;" value="" placeholder="Email link to person" />';
		html += '<input id="emailButton" style="display:none;" type="submit" value="Send" />';
		html += '</form>';
		html += '</div>';
		$(html).appendTo(appendTo);
		$('#dropdown').show('blind');
		$('#share_with').chosen();
	},
	hideDropDown:function(callback) {
		$('#dropdown').hide('blind', function() {
			$('#dropdown').remove();
			if (callback) {
				callback.call();
			}
		});
	},
	addSharedWith:function(uid_shared_with, permissions, isGroup, parentFolder) {
		if (parentFolder) {
			var sharedWith = '<li>Parent folder '+parentFolder+' shared with '+uid_shared_with+'</li>';
		} else {
			var checked = ((permissions > 0) ? 'checked="checked"' : 'style="display:none;"');
			var style = ((permissions == 0) ? 'style="display:none;"' : '');
			var sharedWith = '<li data-uid_shared_with="'+uid_shared_with+'">';
			sharedWith += '<a href="" class="unshare" style="display:none;"><img class="svg" alt="Unshare" src="'+OC.imagePath('core','actions/delete')+'"/></a>';
			sharedWith += uid_shared_with;
			sharedWith += '<input type="checkbox" name="permissions" id="'+uid_shared_with+'" class="permissions" '+checked+' />';
			sharedWith += '<label class="edit" for="'+uid_shared_with+'" '+style+'>can edit</label>';
			sharedWith += '</li>';
		}
		if (isGroup) {
			// Groups are added to a different list
			$('#groups').show();
			$(sharedWith).appendTo('#groupList');
			// Remove group from select form
			$('#share_with option[value="'+uid_shared_with+'(group)"]').remove();
			$('#share_with').trigger('liszt:updated');
			// Remove users in group from select form
			$.each(isGroup, function(index, user) {
				$('#share_with option[value="'+user+'"]').remove();
				$('#share_with').trigger('liszt:updated');
			});
		} else {
			$(sharedWith).appendTo('#userList');
			// Remove user from select form
			$('#share_with option[value="'+uid_shared_with+'"]').remove();
			$('#share_with').trigger('liszt:updated');
		}
		
	},
	removeSharedWith:function(uid_shared_with) {
		var option;
		if ($('#userList li[data-uid_shared_with="'+uid_shared_with+'"]').length > 0) {
			$('#userList li[data-uid_shared_with="'+uid_shared_with+'"]').remove();
			option = '<option value="'+uid_shared_with+'">'+uid_shared_with+'</option>';
		} else if ($('#groupList li[data-uid_shared_with="'+uid_shared_with+'"]').length > 0) {
			$('#groupList li[data-uid_shared_with="'+uid_shared_with+'"]').remove();
			if ($('#groupList li').length < 1) {
				$('#groups').hide();
			}
			option = '<option value="'+uid_shared_with+'(group)">'+uid_shared_with+' (group)</option>';
		}
		$(option).appendTo('#share_with');
		$('#share_with').trigger('liszt:updated');
	},
	showPrivateLink:function(item, token) {
		$('#privateLinkCheckbox').attr('checked', true);
		var link = parent.location.protocol+'//'+location.host+OC.linkTo('', 'public.php')+'?service=files&token='+token;
		if (token.indexOf('&path=') == -1) {
			link += '&file=' + encodeURIComponent(item).replace(/%2F/g, '/');
		} else {
			// Disable checkbox if inside a shared parent folder
			$('#privateLinkCheckbox').attr('disabled', 'true');
		}
		$('#privateLinkText').val(link);
		$('#privateLinkText').show('blind', function() {
			$('#privateLinkText').after('<br id="emailBreak" />');
			$('#email').show();
			$('#emailButton').show();
		});
	},
	hidePrivateLink:function() {
		$('#privateLinkText').hide('blind');
		$('#emailBreak').remove();
		$('#email').hide();
		$('#emailButton').hide();
	},
	emailPrivateLink:function() {
		var link = $('#privateLinkText').val();
		var file = link.substr(link.lastIndexOf('/') + 1).replace(/%20/g, ' ');
		$.post(OC.filePath('files_sharing', 'ajax', 'email.php'), { toaddress: $('#email').val(), link: link, file: file } );
		$('#email').css('font-weight', 'bold');
		$('#email').animate({ fontWeight: 'normal' }, 2000, function() {
			$(this).val('');
		}).val('Email sent');
	},
	dirname:function(path) {
		return path.replace(/\\/g,'/').replace(/\/[^\/]*$/, '');
	}
}

$(document).ready(function() {

	$('.share').live('click', function() {
		if ($(this).data('item-type') !== undefined && $(this).data('item') !== undefined) {
			OC.Share.showDropDown($(this).data('item-type'), $(this).data('item'), $(this).parent().parent());
		}
	});
	
	if (typeof FileActions !== 'undefined') {
		OC.Share.loadIcons('file');
		FileActions.register('all', 'Share', function(filename) {
			// Return the correct sharing icon
			if (scanFiles.scanning) { return; } // workaround to prevent additional http request block scanning feedback
			var item =  $('#dir').val() + '/' + filename;
			// Check if status is in cache
			if (OC.Share.statuses[item] === true) {
				return OC.imagePath('core', 'actions/public');
			} else if (OC.Share.statuses[item] === false) {
				return OC.imagePath('core', 'actions/shared');
			} else {
				var last = '';
				var path = OC.Share.dirname(item);
				// Search for possible parent folders that are shared
				while (path != last) {
					if (OC.Share.statuses[path] === true) {
						return OC.imagePath('core', 'actions/public');
					} else if (OC.Share.statuses[path] === false) {
						return OC.imagePath('core', 'actions/shared');
					}
					last = path;
					path = OC.Share.dirname(path);
				}
				return OC.imagePath('core', 'actions/share');
			}
		}, function(filename) {
			var item = $('#dir').val() + '/' + filename;
			var appendTo = $('tr').filterAttr('data-file',filename).find('td.filename');
			// Check if drop down is already visible for a different file
			if (($('#dropdown').length > 0)) {
				if (item != $('#dropdown').data('item')) {
					OC.Share.hideDropDown(function () {
						$('tr').removeClass('mouseOver');
						$('tr').filterAttr('data-file', filename).addClass('mouseOver');
						OC.Share.showDropDown('file', item, appendTo);
					});
				}
			} else {
				$('tr').filterAttr('data-file',filename).addClass('mouseOver');
				OC.Share.showDropDown('file', item, appendTo);
			}
		});
	}

// 	$(this).click(function(event) {
// 		if (!($(event.target).hasClass('drop')) && $(event.target).parents().index($('#dropdown')) == -1) {
// 			if ($('#dropdown').is(':visible')) {
// 				OC.Share.hideDropDown(function() {
// 					$('tr').removeClass('mouseOver');
// 				});
// 			}
// 		}
// 	});

	$('#sharedWithList li').live('mouseenter', function(event) {
		// Show permissions and unshare button
		$(':hidden', this).show();
	});
	
	$('#sharedWithList li').live('mouseleave', function(event) {
		// Hide permissions and unshare button
		$('a', this).hide();
		if (!$('input:[type=checkbox]', this).is(':checked')) {
			$('input:[type=checkbox]', this).hide();
			$('label', this).hide();
		}
	});
	
	$('#shareWith').live('change', function() {
		OC.Share.share($('#dropdown').data('item-type'), $('#dropdown').data('item'), 0, $(this).val(), 0, false);
	});
	
	$('.unshare').live('click', function() {
		var item = $('#dropdown').data('item');
		var uid_shared_with = $(this).parent().data('uid_shared_with');
		OC.Share.unshare(item, uid_shared_with, function() {
			OC.Share.removeSharedWith(uid_shared_with);
			// Reload item to update cached users and groups for the icon check
			OC.Share.loadItem(item);
			// Change icon
			if (!OC.Share.itemPrivateLink && !OC.Share.itemUsers && !OC.Share.itemGroups) {
				OC.Share.icons[item] = OC.imagePath('core', 'actions/share');
			}
		});
	});
	
	$('.permissions').live('change', function() {
		var permissions = (this.checked) ? 1 : 0;
		OC.Share.changePermissions($('#dropdown').data('item'), $(this).parent().data('uid_shared_with'), permissions);
	});
	
	$('#privateLinkCheckbox').live('change', function() {
		var item = $('#dropdown').data('item');
		if (this.checked) {
			// Create a private link
			OC.Share.share(item, 'public', 0, function(token) {
				OC.Share.showPrivateLink(item, token);
				// Change icon
				OC.Share.icons[item] = OC.imagePath('core', 'actions/public');
			});
		} else {
			// Delete private link
			OC.Share.unshare(item, 'public', function() {
				OC.Share.hidePrivateLink();
				// Change icon
				if (OC.Share.itemUsers || OC.Share.itemGroups) {
					OC.Share.icons[item] = OC.imagePath('core', 'actions/shared');
				} else {
					OC.Share.icons[item] = OC.imagePath('core', 'actions/share');
				}
			});
		}
	});
	
	$('#privateLinkText').live('click', function() {
		$(this).focus();
		$(this).select();
	});

	$('#emailPrivateLink').live('submit', function() {
		OC.Share.emailPrivateLink();
	});
});
