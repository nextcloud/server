OC.Share={
	SHARE_TYPE_USER:0,
	SHARE_TYPE_GROUP:1,
	SHARE_TYPE_LINK:3,
	SHARE_TYPE_EMAIL:4,
	itemShares:[],
	statuses:[],
	droppedDown:false,
	loadIcons:function(itemType) {
		// Load all share icons
		$.get(OC.filePath('core', 'ajax', 'share.php'), { fetch: 'getItemsSharedStatuses', itemType: itemType }, function(result) {
			if (result && result.status === 'success') {
				$.each(result.data, function(item, data) {
					OC.Share.statuses[item] = data;
					var hasLink = data['link'];
					// Links override shared in terms of icon display
					if (hasLink) {
						var image = OC.imagePath('core', 'actions/public');
					} else {
						var image = OC.imagePath('core', 'actions/shared');
					}
					if (itemType != 'file' && itemType != 'folder') {
						$('a.share[data-item="'+item+'"]').css('background', 'url('+image+') no-repeat center');
					} else {
						var file = $('tr').filterAttr('data-id', item);
						if (file.length > 0) {
							var action = $(file).find('.fileactions .action').filterAttr('data-action', 'Share');
							var img = action.find('img').attr('src', image);
							action.addClass('permanent');
							action.html(' '+t('core', 'Shared')).prepend(img);
						} else {
							var dir = $('#dir').val();
							if (dir.length > 1) {
								var last = '';
								var path = dir;
								// Search for possible parent folders that are shared
								while (path != last) {
									if (path == data['path']) {
										var actions = $('.fileactions .action').filterAttr('data-action', 'Share');
										$.each(actions, function(index, action) {
											var img = $(action).find('img');
											if (img.attr('src') != OC.imagePath('core', 'actions/public')) {
												img.attr('src', image);
												$(action).addClass('permanent');
												$(action).html(' '+t('core', 'Shared')).prepend(img);
											}
										});
									}
									last = path;
									path = OC.Share.dirname(path);
								}
							}
						}
					}
				});
			}
		});
	},
	updateIcon:function(itemType, itemSource) {
		var shares = false;
		var link = false;
		var image = OC.imagePath('core', 'actions/share');
		$.each(OC.Share.itemShares, function(index) {
			if (OC.Share.itemShares[index]) {
				if (index == OC.Share.SHARE_TYPE_LINK) {
					if (OC.Share.itemShares[index] == true) {
						shares = true;
						image = OC.imagePath('core', 'actions/public');
						link = true;
						return;
					}
				} else if (OC.Share.itemShares[index].length > 0) {
					shares = true;
					image = OC.imagePath('core', 'actions/shared');
				}
			}
		});
		if (itemType != 'file' && itemType != 'folder') {
			$('a.share[data-item="'+itemSource+'"]').css('background', 'url('+image+') no-repeat center');
		} else {
			var file = $('tr').filterAttr('data-id', String(itemSource));
			if (file.length > 0) {
				var action = $(file).find('.fileactions .action').filterAttr('data-action', 'Share');
				var img = action.find('img').attr('src', image);
				if (shares) {
					action.addClass('permanent');
					action.html(' '+ escapeHTML(t('core', 'Shared'))).prepend(img);
				} else {
					action.removeClass('permanent');
					action.html(' '+ escapeHTML(t('core', 'Share'))).prepend(img);
				}
			}
		}
		if (shares) {
			OC.Share.statuses[itemSource]['link'] = link;
		} else {
			delete OC.Share.statuses[itemSource];
		}
	},
	loadItem:function(itemType, itemSource) {
		var data = '';
		var checkReshare = true;
		if (typeof OC.Share.statuses[itemSource] === 'undefined') {
			// NOTE: Check does not always work and misses some shares, fix later
			checkShares = true;
		} else {
			checkShares = true;
		}
		$.ajax({type: 'GET', url: OC.filePath('core', 'ajax', 'share.php'), data: { fetch: 'getItem', itemType: itemType, itemSource: itemSource, checkReshare: checkReshare, checkShares: checkShares }, async: false, success: function(result) {
			if (result && result.status === 'success') {
				data = result.data;
			} else {
				data = false;
			}
		}});
		return data;
	},
	share:function(itemType, itemSource, shareType, shareWith, permissions, callback) {
		$.post(OC.filePath('core', 'ajax', 'share.php'), { action: 'share', itemType: itemType, itemSource: itemSource, shareType: shareType, shareWith: shareWith, permissions: permissions }, function(result) {
			if (result && result.status === 'success') {
				if (callback) {
					callback(result.data);
				}
			} else {
				OC.dialogs.alert(result.data.message, t('core', 'Error while sharing'));
			}
		});
	},
	unshare:function(itemType, itemSource, shareType, shareWith, callback) {
		$.post(OC.filePath('core', 'ajax', 'share.php'), { action: 'unshare', itemType: itemType, itemSource: itemSource, shareType: shareType, shareWith: shareWith }, function(result) {
			if (result && result.status === 'success') {
				if (callback) {
					callback();
				}
			} else {
				OC.dialogs.alert(t('core', 'Error while unsharing'), t('core', 'Error'));
			}
		});
	},
	setPermissions:function(itemType, itemSource, shareType, shareWith, permissions) {
		$.post(OC.filePath('core', 'ajax', 'share.php'), { action: 'setPermissions', itemType: itemType, itemSource: itemSource, shareType: shareType, shareWith: shareWith, permissions: permissions }, function(result) {
			if (!result || result.status !== 'success') {
				OC.dialogs.alert(t('core', 'Error while changing permissions'), t('core', 'Error'));
			}
		});
	},
	showDropDown:function(itemType, itemSource, appendTo, link, possiblePermissions) {
		var data = OC.Share.loadItem(itemType, itemSource);
		var html = '<div id="dropdown" class="drop" data-item-type="'+itemType+'" data-item-source="'+itemSource+'">';
		if (data !== false && data.reshare !== false && data.reshare.uid_owner !== undefined) {
			if (data.reshare.share_type == OC.Share.SHARE_TYPE_GROUP) {
				html += '<span class="reshare">'+t('core', 'Shared with you and the group {group} by {owner}', {group: data.reshare.share_with, owner: data.reshare.displayname_owner})+'</span>';
			} else {
				html += '<span class="reshare">'+t('core', 'Shared with you by {owner}', {owner: data.reshare.displayname_owner})+'</span>';
			}
			html += '<br />';
		}
		if (possiblePermissions & OC.PERMISSION_SHARE) {
			html += '<input id="shareWith" type="text" placeholder="'+t('core', 'Share with')+'" />';
			html += '<ul id="shareWithList">';
			html += '</ul>';
			if (link) {
				html += '<div id="link">';
				html += '<input type="checkbox" name="linkCheckbox" id="linkCheckbox" value="1" /><label for="linkCheckbox">'+t('core', 'Share with link')+'</label>';
				html += '<br />';
				html += '<input id="linkText" type="text" readonly="readonly" />';
				html += '<input type="checkbox" name="showPassword" id="showPassword" value="1" style="display:none;" /><label for="showPassword" style="display:none;">'+t('core', 'Password protect')+'</label>';
				html += '<div id="linkPass">';
				html += '<input id="linkPassText" type="password" placeholder="'+t('core', 'Password')+'" />';
				html += '</div>';
				html += '</div>';
				html += '<form id="emailPrivateLink" >';
				html += '<input id="email" style="display:none; width:62%;" value="" placeholder="'+t('core', 'Email link to person')+'" type="text" />';
				html += '<input id="emailButton" style="display:none;" type="submit" value="'+t('core', 'Send')+'" />';
				html += '</form>';
			}
			html += '<div id="expiration">';
			html += '<input type="checkbox" name="expirationCheckbox" id="expirationCheckbox" value="1" /><label for="expirationCheckbox">'+t('core', 'Set expiration date')+'</label>';
			html += '<input id="expirationDate" type="text" placeholder="'+t('core', 'Expiration date')+'" style="display:none; width:90%;" />';
			html += '</div>';
			$(html).appendTo(appendTo);
			// Reset item shares
			OC.Share.itemShares = [];
			if (data.shares) {
				$.each(data.shares, function(index, share) {
					if (share.share_type == OC.Share.SHARE_TYPE_LINK) {
						OC.Share.showLink(share.token, share.share_with, itemSource);
					} else {
						if (share.collection) {
							OC.Share.addShareWith(share.share_type, share.share_with, share.share_with_displayname, share.permissions, possiblePermissions, share.collection);
						} else {
							OC.Share.addShareWith(share.share_type, share.share_with, share.share_with_displayname,  share.permissions, possiblePermissions, false);
						}
					}
					if (share.expiration != null) {
						OC.Share.showExpirationDate(share.expiration);
					}
				});
			}
			$('#shareWith').autocomplete({minLength: 1, source: function(search, response) {
	// 			if (cache[search.term]) {
	// 				response(cache[search.term]);
	// 			} else {
					$.get(OC.filePath('core', 'ajax', 'share.php'), { fetch: 'getShareWith', search: search.term, itemShares: OC.Share.itemShares }, function(result) {
						if (result.status == 'success' && result.data.length > 0) {
							response(result.data);
						} else {
							// Suggest sharing via email if valid email address
// 							var pattern = new RegExp(/^[+a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/i);
// 							if (pattern.test(search.term)) {
// 								response([{label: t('core', 'Share via email:')+' '+search.term, value: {shareType: OC.Share.SHARE_TYPE_EMAIL, shareWith: search.term}}]);
// 							} else {
								response([t('core', 'No people found')]);
// 							}
						}
					});
	// 			}
			},
			focus: function(event, focused) {
				event.preventDefault();
			},
			select: function(event, selected) {
				event.stopPropagation();
				var itemType = $('#dropdown').data('item-type');
				var itemSource = $('#dropdown').data('item-source');
				var shareType = selected.item.value.shareType;
				var shareWith = selected.item.value.shareWith;
				$(this).val(shareWith);
				// Default permissions are Read and Share
				var permissions = OC.PERMISSION_READ | OC.PERMISSION_SHARE;
				OC.Share.share(itemType, itemSource, shareType, shareWith, permissions, function() {
					OC.Share.addShareWith(shareType, shareWith, selected.item.label, permissions, possiblePermissions);
					$('#shareWith').val('');
					OC.Share.updateIcon(itemType, itemSource);
				});
				return false;
			}
			});
		} else {
			html += '<input id="shareWith" type="text" placeholder="'+t('core', 'Resharing is not allowed')+'" style="width:90%;" disabled="disabled"/>';
			html += '</div>';
			$(html).appendTo(appendTo);
		}
		$('#dropdown').show('blind', function() {
			OC.Share.droppedDown = true;
		});
		$('#shareWith').focus();
	},
	hideDropDown:function(callback) {
		$('#dropdown').hide('blind', function() {
			OC.Share.droppedDown = false;
			$('#dropdown').remove();
			if (typeof FileActions !== 'undefined') {
				$('tr').removeClass('mouseOver');
			}
			if (callback) {
				callback.call();
			}
		});
	},
	addShareWith:function(shareType, shareWith, shareWithDisplayName, permissions, possiblePermissions, collection) {
		if (!OC.Share.itemShares[shareType]) {
			OC.Share.itemShares[shareType] = [];
		}
		OC.Share.itemShares[shareType].push(shareWith);
		if (collection) {
			if (collection.item_type == 'file' || collection.item_type == 'folder') {
				var item = collection.path;
			} else {
				var item = collection.item_source;
			}
			var collectionList = $('#shareWithList li').filterAttr('data-collection', item);
			if (collectionList.length > 0) {
				$(collectionList).append(', '+shareWithDisplayName);
			} else {
				var html = '<li style="clear: both;" data-collection="'+item+'">'+t('core', 'Shared in {item} with {user}', {'item': item, user: shareWithDisplayName})+'</li>';
				$('#shareWithList').prepend(html);
			}
		} else {
			var editChecked = createChecked = updateChecked = deleteChecked = shareChecked = '';
			if (permissions & OC.PERMISSION_CREATE) {
				createChecked = 'checked="checked"';
				editChecked = 'checked="checked"';
			}
			if (permissions & OC.PERMISSION_UPDATE) {
				updateChecked = 'checked="checked"';
				editChecked = 'checked="checked"';
			}
			if (permissions & OC.PERMISSION_DELETE) {
				deleteChecked = 'checked="checked"';
				editChecked = 'checked="checked"';
			}
			if (permissions & OC.PERMISSION_SHARE) {
				shareChecked = 'checked="checked"';
			}
			var html = '<li style="clear: both;" data-share-type="'+escapeHTML(shareType)+'" data-share-with="'+escapeHTML(shareWith)+'" title="' + escapeHTML(shareWith) + '">';
			html += '<a href="#" class="unshare" style="display:none;"><img class="svg" alt="'+t('core', 'Unshare')+'" src="'+OC.imagePath('core', 'actions/delete')+'"/></a>';
			if(shareWith.length > 14){
				html += escapeHTML(shareWithDisplayName.substr(0,11) + '...');
			}else{
				html += escapeHTML(shareWithDisplayName);
			}
			if (possiblePermissions & OC.PERMISSION_CREATE || possiblePermissions & OC.PERMISSION_UPDATE || possiblePermissions & OC.PERMISSION_DELETE) {
				if (editChecked == '') {
					html += '<label style="display:none;">';
				} else {
					html += '<label>';
				}
				html += '<input type="checkbox" name="edit" class="permissions" '+editChecked+' />'+t('core', 'can edit')+'</label>';
			}
			html += '<a href="#" class="showCruds" style="display:none;"><img class="svg" alt="'+t('core', 'access control')+'" src="'+OC.imagePath('core', 'actions/triangle-s')+'"/></a>';
			html += '<div class="cruds" style="display:none;">';
				if (possiblePermissions & OC.PERMISSION_CREATE) {
					html += '<label><input type="checkbox" name="create" class="permissions" '+createChecked+' data-permissions="'+OC.PERMISSION_CREATE+'" />'+t('core', 'create')+'</label>';
				}
				if (possiblePermissions & OC.PERMISSION_UPDATE) {
					html += '<label><input type="checkbox" name="update" class="permissions" '+updateChecked+' data-permissions="'+OC.PERMISSION_UPDATE+'" />'+t('core', 'update')+'</label>';
				}
				if (possiblePermissions & OC.PERMISSION_DELETE) {
					html += '<label><input type="checkbox" name="delete" class="permissions" '+deleteChecked+' data-permissions="'+OC.PERMISSION_DELETE+'" />'+t('core', 'delete')+'</label>';
				}
				if (possiblePermissions & OC.PERMISSION_SHARE) {
					html += '<label><input type="checkbox" name="share" class="permissions" '+shareChecked+' data-permissions="'+OC.PERMISSION_SHARE+'" />'+t('core', 'share')+'</label>';
				}
			html += '</div>';
			html += '</li>';
			$(html).appendTo('#shareWithList');
			$('#expiration').show();
		}
	},
	showLink:function(token, password, itemSource) {
		OC.Share.itemShares[OC.Share.SHARE_TYPE_LINK] = true;
		$('#linkCheckbox').attr('checked', true);
		if (! token) {
			//fallback to pre token link
			var filename = $('tr').filterAttr('data-id', String(itemSource)).data('file');
			var type = $('tr').filterAttr('data-id', String(itemSource)).data('type');
			if ($('#dir').val() == '/') {
				var file = $('#dir').val() + filename;
			} else {
				var file = $('#dir').val() + '/' + filename;
			}
			file = '/'+OC.currentUser+'/files'+file;
			var link = parent.location.protocol+'//'+location.host+OC.linkTo('', 'public.php')+'?service=files&'+type+'='+encodeURIComponent(file);
		} else {
			//TODO add path param when showing a link to file in a subfolder of a public link share
			var link = parent.location.protocol+'//'+location.host+OC.linkTo('', 'public.php')+'?service=files&t='+token;
		}
		$('#linkText').val(link);
		$('#linkText').show('blind');
		$('#linkText').css('display','block');
		$('#showPassword').show();
		$('#showPassword+label').show();
		if (password != null) {
			$('#linkPass').show('blind');
			$('#showPassword').attr('checked', true);
			$('#linkPassText').attr('placeholder', t('core', 'Password protected'));
		}
		$('#expiration').show();
		$('#emailPrivateLink #email').show();
		$('#emailPrivateLink #emailButton').show();
	},
	hideLink:function() {
		$('#linkText').hide('blind');
		$('#showPassword').hide();
		$('#showPassword+label').hide();
		$('#linkPass').hide();
		$('#emailPrivateLink #email').hide();
		$('#emailPrivateLink #emailButton').hide();
	},
	dirname:function(path) {
		return path.replace(/\\/g,'/').replace(/\/[^\/]*$/, '');
	},
	showExpirationDate:function(date) {
		$('#expirationCheckbox').attr('checked', true);
		$('#expirationDate').before('<br />');
		$('#expirationDate').val(date);
		$('#expirationDate').show();
		$('#expirationDate').datepicker({
			dateFormat : 'dd-mm-yy'
		});
	}
}

$(document).ready(function() {

	if(typeof monthNames != 'undefined'){
		$.datepicker.setDefaults({
			monthNames: monthNames,
			monthNamesShort: $.map(monthNames, function(v) { return v.slice(0,3)+'.'; }),
			dayNames: dayNames,
			dayNamesMin: $.map(dayNames, function(v) { return v.slice(0,2); }),
			dayNamesShort: $.map(dayNames, function(v) { return v.slice(0,3)+'.'; }),
			firstDay: firstDay
		});
	}
	$(document).on('click', 'a.share', function(event) {
		event.stopPropagation();
		if ($(this).data('item-type') !== undefined && $(this).data('item') !== undefined) {
			var itemType = $(this).data('item-type');
			var itemSource = $(this).data('item');
			var appendTo = $(this).parent().parent();
			var link = false;
			var possiblePermissions = $(this).data('possible-permissions');
			if ($(this).data('link') !== undefined && $(this).data('link') == true) {
				link = true;
			}
			if (OC.Share.droppedDown) {
				if (itemSource != $('#dropdown').data('item')) {
					OC.Share.hideDropDown(function () {
						OC.Share.showDropDown(itemType, itemSource, appendTo, link, possiblePermissions);
					});
				} else {
					OC.Share.hideDropDown();
				}
			} else {
				OC.Share.showDropDown(itemType, itemSource, appendTo, link, possiblePermissions);
			}
		}
	});

	$(this).click(function(event) {
		var target = $(event.target);
		var isMatched = !target.is('.drop, .ui-datepicker-next, .ui-datepicker-prev, .ui-icon')
			&& !target.closest('#ui-datepicker-div').length;
		if (OC.Share.droppedDown && isMatched && $('#dropdown').has(event.target).length === 0) {
			OC.Share.hideDropDown();
		}
	});

	$(document).on('mouseenter', '#dropdown #shareWithList li', function(event) {
		// Show permissions and unshare button
		$(':hidden', this).filter(':not(.cruds)').show();
	});

	$(document).on('mouseleave', '#dropdown #shareWithList li', function(event) {
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

	$(document).on('click', '#dropdown .showCruds', function() {
		$(this).parent().find('.cruds').toggle();
	});

	$(document).on('click', '#dropdown .unshare', function() {
		var li = $(this).parent();
		var itemType = $('#dropdown').data('item-type');
		var itemSource = $('#dropdown').data('item-source');
		var shareType = $(li).data('share-type');
		var shareWith = $(li).data('share-with');
		OC.Share.unshare(itemType, itemSource, shareType, shareWith, function() {
			$(li).remove();
			var index = OC.Share.itemShares[shareType].indexOf(shareWith);
			OC.Share.itemShares[shareType].splice(index, 1);
			OC.Share.updateIcon(itemType, itemSource);
			if (typeof OC.Share.statuses[itemSource] === 'undefined') {
				$('#expiration').hide();
			}
		});
	});

	$(document).on('change', '#dropdown .permissions', function() {
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
			if (!$(this).is(':checked')
				&& !$(checkboxes).filter('input[name="create"]').is(':checked')
				&& !$(checkboxes).filter('input[name="update"]').is(':checked')
				&& !$(checkboxes).filter('input[name="delete"]').is(':checked'))
			{
				$(checkboxes).filter('input[name="edit"]').attr('checked', false);
			// Check Edit if Create, Update, or Delete is checked
			} else if (($(this).attr('name') == 'create'
					 || $(this).attr('name') == 'update'
					 || $(this).attr('name') == 'delete'))
			{
				$(checkboxes).filter('input[name="edit"]').attr('checked', true);
			}
		}
		var permissions = OC.PERMISSION_READ;
		$(checkboxes).filter(':not(input[name="edit"])').filter(':checked').each(function(index, checkbox) {
			permissions |= $(checkbox).data('permissions');
		});
		OC.Share.setPermissions($('#dropdown').data('item-type'),
			$('#dropdown').data('item-source'),
			$(li).data('share-type'),
			$(li).data('share-with'),
			permissions);
	});

	$(document).on('change', '#dropdown #linkCheckbox', function() {
		var itemType = $('#dropdown').data('item-type');
		var itemSource = $('#dropdown').data('item-source');
		if (this.checked) {
			// Create a link
			OC.Share.share(itemType, itemSource, OC.Share.SHARE_TYPE_LINK, '', OC.PERMISSION_READ, function(data) {
				OC.Share.showLink(data.token, null, itemSource);
				OC.Share.updateIcon(itemType, itemSource);
			});
		} else {
			// Delete private link
			OC.Share.unshare(itemType, itemSource, OC.Share.SHARE_TYPE_LINK, '', function() {
				OC.Share.hideLink();
				OC.Share.itemShares[OC.Share.SHARE_TYPE_LINK] = false;
				OC.Share.updateIcon(itemType, itemSource);
				if (typeof OC.Share.statuses[itemSource] === 'undefined') {
					$('#expiration').hide();
				}
			});
		}
	});

	$(document).on('click', '#dropdown #linkText', function() {
		$(this).focus();
		$(this).select();
	});

	$(document).on('click', '#dropdown #showPassword', function() {
		$('#linkPass').toggle('blind');
		if (!$('#showPassword').is(':checked') ) {
			var itemType = $('#dropdown').data('item-type');
			var itemSource = $('#dropdown').data('item-source');
			OC.Share.share(itemType, itemSource, OC.Share.SHARE_TYPE_LINK, '', OC.PERMISSION_READ);
		} else {
			$('#linkPassText').focus();
		}
	});

	$(document).on('focusout keyup', '#dropdown #linkPassText', function(event) {
		if ( $('#linkPassText').val() != '' && (event.type == 'focusout' || event.keyCode == 13) ) {
			var itemType = $('#dropdown').data('item-type');
			var itemSource = $('#dropdown').data('item-source');
			OC.Share.share(itemType, itemSource, OC.Share.SHARE_TYPE_LINK, $('#linkPassText').val(), OC.PERMISSION_READ, function() {
				console.log("password set to: '" + $('#linkPassText').val() +"' by event: " + event.type);
				$('#linkPassText').val('');
				$('#linkPassText').attr('placeholder', t('core', 'Password protected'));
			});
		}
	});

	$(document).on('click', '#dropdown #expirationCheckbox', function() {
		if (this.checked) {
			OC.Share.showExpirationDate('');
		} else {
			var itemType = $('#dropdown').data('item-type');
			var itemSource = $('#dropdown').data('item-source');
			$.post(OC.filePath('core', 'ajax', 'share.php'), { action: 'setExpirationDate', itemType: itemType, itemSource: itemSource, date: '' }, function(result) {
				if (!result || result.status !== 'success') {
					OC.dialogs.alert(t('core', 'Error unsetting expiration date'), t('core', 'Error'));
				}
				$('#expirationDate').hide();
			});
		}
	});

	$(document).on('change', '#dropdown #expirationDate', function() {
		var itemType = $('#dropdown').data('item-type');
		var itemSource = $('#dropdown').data('item-source');
		$.post(OC.filePath('core', 'ajax', 'share.php'), { action: 'setExpirationDate', itemType: itemType, itemSource: itemSource, date: $(this).val() }, function(result) {
			if (!result || result.status !== 'success') {
				OC.dialogs.alert(t('core', 'Error setting expiration date'), t('core', 'Error'));
			}
		});
	});


	$(document).on('submit', '#dropdown #emailPrivateLink', function(event) {
		event.preventDefault();
		var link = $('#linkText').val();
		var itemType = $('#dropdown').data('item-type');
		var itemSource = $('#dropdown').data('item-source');
		var file = $('tr').filterAttr('data-id', String(itemSource)).data('file');
		var email = $('#email').val();
		if (email != '') {
			$('#email').attr('disabled', "disabled");
			$('#email').val(t('core', 'Sending ...'));
			$('#emailButton').attr('disabled', "disabled");

			$.post(OC.filePath('core', 'ajax', 'share.php'), { action: 'email', toaddress: email, link: link, itemType: itemType, itemSource: itemSource, file: file},
				function(result) {
					$('#email').attr('disabled', "false");
					$('#emailButton').attr('disabled', "false");
				if (result && result.status == 'success') {
					$('#email').css('font-weight', 'bold');
					$('#email').animate({ fontWeight: 'normal' }, 2000, function() {
						$(this).val('');
					}).val(t('core','Email sent'));
				} else {
					OC.dialogs.alert(result.data.message, t('core', 'Error while sharing'));
				}
			});
		}
	});


});
