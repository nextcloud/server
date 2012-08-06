OC.Share={
	SHARE_TYPE_USER:0,
	SHARE_TYPE_GROUP:1,
	SHARE_TYPE_PRIVATE_LINK:3,
	SHARE_TYPE_EMAIL:4,
	PERMISSION_CREATE:4,
	PERMISSION_READ:1,
	PERMISSION_UPDATE:2,
	PERMISSION_DELETE:8,
	PERMISSION_SHARE:16,
	itemShares:[],
	statuses:[],
	loadIcons:function(itemType) {
		// Load all share icons
		$.get(OC.filePath('core', 'ajax', 'share.php'), { fetch: 'getItemsSharedStatuses', itemType: itemType }, function(result) {
			if (result && result.status === 'success') {
				$.each(result.data, function(item, hasPrivateLink) {
					// Private links override shared in terms of icon display
					if (itemType != 'file' && itemType != 'folder') {
						if (hasPrivateLink) {
							$('.share').find('[data-item="'+item+'"]').attr('src', OC.imagePath('core', 'actions/public'));
						} else {
							$('.share').find('[data-item="'+item+'"]').attr('src', OC.imagePath('core', 'actions/shared'));
						}
					}
					OC.Share.statuses[item] = hasPrivateLink;
				});
			}
		});
	},
	loadItem:function(itemType, item) {
		var data = '';
		if (typeof OC.Share.statuses[item] !== 'undefined') {
			$.ajax({type: 'GET', url: OC.filePath('core', 'ajax', 'share.php'), data: { fetch: 'getItem', itemType: itemType, item: item }, async: false, success: function(result) {
				if (result && result.status === 'success') {
					data = result.data;
				} else {
					data = false;
				}
			}});
		}
		return data;
	},
	share:function(itemType, item, shareType, shareWith, permissions, callback) {
		$.post(OC.filePath('core', 'ajax', 'share.php'), { action: 'share', itemType: itemType, item: item, shareType: shareType, shareWith: shareWith, permissions: permissions }, function(result) {
			if (result && result.status === 'success') {
				if (callback) {
					callback(result.data);
				}
			} else {
				OC.dialogs.alert(result.data.message, 'Error while sharing');
			}
		});
	},
	unshare:function(itemType, item, shareType, shareWith, callback) {
		$.post(OC.filePath('core', 'ajax', 'share.php'), { action: 'unshare', itemType: itemType, item: item, shareType: shareType, shareWith: shareWith }, function(result) {
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
	showDropDown:function(itemType, item, appendTo, privateLink, possiblePermissions) {
		var html = '<div id="dropdown" class="drop" data-item-type="'+itemType+'" data-item="'+item+'">';
		// TODO replace with autocomplete textbox
		html += '<input id="shareWith" type="text" placeholder="Share with" style="width:90%;"/>';
		html += '<ul id="shareWithList">';
		html += '</ul>';
		if (privateLink) {
			html += '<div id="privateLink">';
			html += '<input type="checkbox" name="privateLinkCheckbox" id="privateLinkCheckbox" value="1" /><label for="privateLinkCheckbox">Share with private link</label>';
			html += '<br />';
			html += '<input id="privateLinkText" style="display:none; width:90%;" readonly="readonly" />';
			html += '</div>';
		}
		html += '</div>';
		$(html).appendTo(appendTo);
		var data = OC.Share.loadItem(itemType, item);
		if (data) {
			$.each(data, function(index, share) {
				if (share.share_type == OC.Share.SHARE_TYPE_PRIVATE_LINK) {
					OC.Share.showPrivateLink(item, share.share_with);
				} else {
					OC.Share.addShareWith(share.share_type, share.share_with, share.permissions, possiblePermissions);
					
				}
			});
		}
		$('#shareWith').autocomplete({minLength: 2, source: function(search, response) {
// 			if (cache[search.term]) {
// 				response(cache[search.term]);
// 			} else {
				$.get(OC.filePath('core', 'ajax', 'share.php'), { fetch: 'getShareWith', search: search.term, itemShares: OC.Share.itemShares }, function(result) {
					if (result.status == 'success' && result.data.length > 0) {
						response(result.data);
					} else {
						// Suggest sharing via email if valid email address
						var pattern = new RegExp(/^[+a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/i);
						if (pattern.test(search.term)) {
							response([{label: 'Share via email: '+search.term, value: {shareType: OC.Share.SHARE_TYPE_EMAIL, shareWith: search.term}}]);
						} else {
							response(['No people found']);
						}
					}
				});
// 			}
		},
		focus: function(event, focused) {
			event.preventDefault();
		},
		select: function(event, selected) {
			var shareType = selected.item.value.shareType;
			var shareWith = selected.item.value.shareWith;
			$(this).val(shareWith);
			// Default permissions are Read and Share
			var permissions = OC.Share.PERMISSION_READ | OC.Share.PERMISSION_SHARE;
			OC.Share.share($('#dropdown').data('item-type'), $('#dropdown').data('item'), shareType, shareWith, permissions, function() {
				OC.Share.addShareWith(shareType, shareWith, permissions, possiblePermissions);
				$('#shareWith').val('');
			});
			return false;
		}
		});
		$('#dropdown').show('blind');
		$('#shareWith').focus();
	},
	hideDropDown:function(callback) {
		$('#dropdown').hide('blind', function() {
			$('#dropdown').remove();
			if (callback) {
				callback.call();
			}
		});
	},
	addShareWith:function(shareType, shareWith, permissions, possiblePermissions) {
		if (!OC.Share.itemShares[shareType]) {
			OC.Share.itemShares[shareType] = [];
		}
		OC.Share.itemShares[shareType].push(shareWith);
		var editChecked = createChecked = updateChecked = deleteChecked = shareChecked = '';
		if (permissions & OC.Share.PERMISSION_CREATE) {
			createChecked = 'checked="checked"';
			editChecked = 'checked="checked"';
		}
		if (permissions & OC.Share.PERMISSION_UPDATE) {
			updateChecked = 'checked="checked"';
			editChecked = 'checked="checked"';
		}
		if (permissions & OC.Share.PERMISSION_DELETE) {
			deleteChecked = 'checked="checked"';
			editChecked = 'checked="checked"';
		}
		if (permissions & OC.Share.PERMISSION_SHARE) {
			shareChecked = 'checked="checked"';
		}
		var html = '<li data-share-type="'+shareType+'" data-share-with="'+shareWith+'">';
		html += shareWith;
		if (possiblePermissions & OC.Share.PERMISSION_CREATE || possiblePermissions & OC.Share.PERMISSION_UPDATE || possiblePermissions & OC.Share.PERMISSION_DELETE) {
			if (editChecked == '') {
				html += '<label style="display:none;">';
			} else {
				html += '<label>';
			}
			html += '<input type="checkbox" name="edit" class="permissions" '+editChecked+' />can edit</label>';
		}
		html += '<a href="#" class="showCruds" style="display:none;"><img class="svg" alt="Unshare" src="'+OC.imagePath('core', 'actions/triangle-s')+'"/></a>';
		html += '<a href="#" class="unshare" style="display:none;"><img class="svg" alt="Unshare" src="'+OC.imagePath('core', 'actions/delete')+'"/></a>';
		html += '<div class="cruds" style="display:none;">';
			if (possiblePermissions & OC.Share.PERMISSION_CREATE) {
				html += '<label><input type="checkbox" name="create" class="permissions" '+createChecked+' data-permissions="'+OC.Share.PERMISSION_CREATE+'" />create</label>';
			}
			if (possiblePermissions & OC.Share.PERMISSION_UPDATE) {
				html += '<label><input type="checkbox" name="update" class="permissions" '+updateChecked+' data-permissions="'+OC.Share.PERMISSION_UPDATE+'" />update</label>';
			}
			if (possiblePermissions & OC.Share.PERMISSION_DELETE) {
				html += '<label><input type="checkbox" name="delete" class="permissions" '+deleteChecked+' data-permissions="'+OC.Share.PERMISSION_DELETE+'" />delete</label>';
			}
			if (possiblePermissions & OC.Share.PERMISSION_SHARE) {
				html += '<label><input type="checkbox" name="share" class="permissions" '+shareChecked+' data-permissions="'+OC.Share.PERMISSION_SHARE+'" />share</label>';
			}
		html += '</div>';
		html += '</li>';
		$(html).appendTo('#shareWithList');
		
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
			var privateLink = false;
			if ($(this).data('private-link') !== undefined && $(this).data('private-link') == true) {
				privateLink = true;
			}
			OC.Share.showDropDown($(this).data('item-type'), $(this).data('item'), $(this).parent().parent(), privateLink, $(this).data('possible-permissions'));
		}
	});
	
	if (typeof FileActions !== 'undefined') {
		OC.Share.loadIcons('file');
		OC.Share.loadIcons('folder');
		FileActions.register('all', 'Share', FileActions.PERMISSION_SHARE, function(filename) {
			// Return the correct sharing icon
			if (scanFiles.scanning) { return; } // workaround to prevent additional http request block scanning feedback
			var item = $('#dir').val() + '/' + filename;
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
			if ($('tr').filterAttr('data-file', filename).data('type') == 'dir') {
				var itemType = 'folder';
				var possiblePermissions = OC.Share.PERMISSION_CREATE | OC.Share.PERMISSION_UPDATE | OC.Share.PERMISSION_DELETE | OC.Share.PERMISSION_SHARE;
			} else {
				var itemType = 'file';
				var possiblePermissions = OC.Share.PERMISSION_UPDATE | OC.Share.PERMISSION_DELETE | OC.Share.PERMISSION_SHARE;
			}
			var appendTo = $('tr').filterAttr('data-file', filename).find('td.filename');
			// Check if drop down is already visible for a different file
			if (($('#dropdown').length > 0)) {
				if (item != $('#dropdown').data('item')) {
					OC.Share.hideDropDown(function () {
						$('tr').removeClass('mouseOver');
						$('tr').filterAttr('data-file', filename).addClass('mouseOver');
						OC.Share.showDropDown(itemType, item, appendTo, true, possiblePermissions);
					});
				}
			} else {
				$('tr').filterAttr('data-file',filename).addClass('mouseOver');
				OC.Share.showDropDown(itemType, item, appendTo, true, possiblePermissions);
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

	$('#shareWithList li').live('mouseenter', function(event) {
		// Show permissions and unshare button
		$(':hidden', this).filter(':not(.cruds)').show();
	});
	
	$('#shareWithList li').live('mouseleave', function(event) {
		// Hide permissions and unshare button
		if (!$('.cruds', this).is(':visible')) {
			$('a', this).hide();
			if (!$('input[name="edit"]', this).is(':checked')) {
				$('input:[type=checkbox]', this).hide();
				$('label', this).hide();
			}
		} else {
			$('a.unshare', this).hide();
		}
	});

	$('.showCruds').live('click', function() {
		$(this).parent().find('.cruds').toggle();
	});

	$('.unshare').live('click', function() {
		var li = $(this).parent();
		OC.Share.unshare($('#dropdown').data('item-type'), $('#dropdown').data('item'), $(li).data('share-type'), $(li).data('share-with'), function() {
			$(li).remove();
		});
	});
	
	$('.permissions').live('change', function() {
		if ($(this).attr('name') == 'edit') {
			var li = $(this).parent().parent() 
			var checkboxes = $('.permissions', li);
			var checked = $(this).is(':checked');
			// Check/uncheck Create, Update, and Delete checkboxes if Edit is checked/unck
			$(checkboxes).filter('input[name="create"]').attr('checked', checked);
			$(checkboxes).filter('input[name="update"]').attr('checked', checked);
			$(checkboxes).filter('input[name="delete"]').attr('checked', checked);
		} else {
			var li = $(this).parent().parent().parent();
			var checkboxes = $('.permissions', li);
			// Uncheck Edit if Create, Update, and Delete are not checked
			if (!$(this).is(':checked') && !$(checkboxes).filter('input[name="create"]').is(':checked') && !$(checkboxes).filter('input[name="update"]').is(':checked') && !$(checkboxes).filter('input[name="delete"]').is(':checked')) {
				$(checkboxes).filter('input[name="edit"]').attr('checked', false);
			// Check Edit if Create, Update, or Delete is checked
			} else if (($(this).attr('name') == 'create' || $(this).attr('name') == 'update' || $(this).attr('name') == 'delete')) {
				$(checkboxes).filter('input[name="edit"]').attr('checked', true);
			}
		}
		var permissions = OC.Share.PERMISSION_READ;
		$(checkboxes).filter(':not(input[name="edit"])').filter(':checked').each(function(index, checkbox) {
			permissions |= $(checkbox).data('permissions');
		});
		OC.Share.setPermissions($('#dropdown').data('item-type'), $('#dropdown').data('item'), $(li).data('share-type'), $(li).data('share-with'), permissions);
	});
	
	$('#privateLinkCheckbox').live('change', function() {
		var itemType = $('#dropdown').data('item-type');
		var item = $('#dropdown').data('item');
		if (this.checked) {
			// Create a private link
			OC.Share.share(itemType, item, OC.Share.SHARE_TYPE_PRIVATE_LINK, 0, 0, function(token) {
				OC.Share.showPrivateLink(item, 'foo');
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
