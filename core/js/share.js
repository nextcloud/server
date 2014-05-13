OC.Share={
	SHARE_TYPE_USER:0,
	SHARE_TYPE_GROUP:1,
	SHARE_TYPE_LINK:3,
	SHARE_TYPE_EMAIL:4,
	itemShares:[],
	statuses:{},
	droppedDown:false,
	/**
	 * Loads ALL share statuses from server, stores them in OC.Share.statuses then
	 * calls OC.Share.updateIcons() to update the files "Share" icon to "Shared"
	 * according to their share status and share type.
	 */
	loadIcons:function(itemType) {
		// Load all share icons
		$.get(OC.filePath('core', 'ajax', 'share.php'), { fetch: 'getItemsSharedStatuses', itemType: itemType }, function(result) {
			if (result && result.status === 'success') {
				OC.Share.statuses = {};
				$.each(result.data, function(item, data) {
					OC.Share.statuses[item] = data;
				});
				OC.Share.updateIcons(itemType);
			}
		});
	},
	/**
	 * Updates the files' "Share" icons according to the known
	 * sharing states stored in OC.Share.statuses.
	 * (not reloaded from server)
	 */
	updateIcons:function(itemType){
		var item;
		for (item in OC.Share.statuses){
			var data = OC.Share.statuses[item];

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
				var file = $('tr[data-id="'+item+'"]');
				if (file.length > 0) {
					var action = $(file).find('.fileactions .action[data-action="Share"]');
					var img = action.find('img').attr('src', image);
					action.addClass('permanent');
					action.html(' <span>'+t('core', 'Shared')+'</span>').prepend(img);
				} else {
					var dir = $('#dir').val();
					if (dir.length > 1) {
						var last = '';
						var path = dir;
						// Search for possible parent folders that are shared
						while (path != last) {
							if (path == data['path'] && !data['link']) {
								var actions = $('.fileactions .action[data-action="Share"]');
								$.each(actions, function(index, action) {
									var img = $(action).find('img');
									if (img.attr('src') != OC.imagePath('core', 'actions/public')) {
										img.attr('src', image);
										$(action).addClass('permanent');
										$(action).html(' <span>'+t('core', 'Shared')+'</span>').prepend(img);
									}
								});
							}
							last = path;
							path = OC.Share.dirname(path);
						}
					}
				}
			}
		}
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
					action.html(' <span>'+ escapeHTML(t('core', 'Shared'))+'</span>').prepend(img);
				} else {
					action.removeClass('permanent');
					action.html(' <span>'+ escapeHTML(t('core', 'Share'))+'</span>').prepend(img);
				}
			}
		}
		if (shares) {
			OC.Share.statuses[itemSource] = OC.Share.statuses[itemSource] || {};
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
			var checkShares = true;
		} else {
			var checkShares = true;
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
	share:function(itemType, itemSource, shareType, shareWith, permissions, itemSourceName, callback) {
		$.post(OC.filePath('core', 'ajax', 'share.php'),
			{
				action: 'share',
				itemType: itemType,
				itemSource: itemSource,
				shareType: shareType,
				shareWith: shareWith,
				permissions: permissions,
				itemSourceName: itemSourceName
			}, function (result) {
			if (result && result.status === 'success') {
				if (callback) {
					callback(result.data);
				}
			} else {
				if (result.data && result.data.message) {
					var msg = result.data.message;
				} else {
					var msg = t('core', 'Error');
				}
				OC.dialogs.alert(msg, t('core', 'Error while sharing'));
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
	showDropDown:function(itemType, itemSource, appendTo, link, possiblePermissions, filename) {
		var data = OC.Share.loadItem(itemType, itemSource);
		var dropDownEl;
		var html = '<div id="dropdown" class="drop" data-item-type="'+itemType+'" data-item-source="'+itemSource+'">';
		if (data !== false && data.reshare !== false && data.reshare.uid_owner !== undefined) {
			if (data.reshare.share_type == OC.Share.SHARE_TYPE_GROUP) {
				html += '<span class="reshare">'+t('core', 'Shared with you and the group {group} by {owner}', {group: escapeHTML(data.reshare.share_with), owner: escapeHTML(data.reshare.displayname_owner)})+'</span>';
			} else {
				html += '<span class="reshare">'+t('core', 'Shared with you by {owner}', {owner: escapeHTML(data.reshare.displayname_owner)})+'</span>';
			}
			html += '<br />';
		}

		if (possiblePermissions & OC.PERMISSION_SHARE) {
			// Determine the Allow Public Upload status.
			// Used later on to determine if the
			// respective checkbox should be checked or
			// not.

			var publicUploadEnabled = $('#filestable').data('allow-public-upload');
			if (typeof publicUploadEnabled == 'undefined') {
				publicUploadEnabled = 'no';
			}
			var allowPublicUploadStatus = false;

			$.each(data.shares, function(key, value) {
				if (value.share_type === OC.Share.SHARE_TYPE_LINK) {
					allowPublicUploadStatus = (value.permissions & OC.PERMISSION_CREATE) ? true : false;
					return true;
				}
			});

			html += '<input id="shareWith" type="text" placeholder="'+t('core', 'Share with user or group …')+'" />';
			html += '<ul id="shareWithList">';
			html += '</ul>';
			var linksAllowed = $('#allowShareWithLink').val() === 'yes';
			if (link && linksAllowed) {
				html += '<div id="link">';
				html += '<input type="checkbox" name="linkCheckbox" id="linkCheckbox" value="1" /><label for="linkCheckbox">'+t('core', 'Share link')+'</label>';
				html += '<br />';

				var defaultExpireMessage = '';
				if ((itemType === 'folder' || itemType === 'file') && oc_appconfig.core.defaultExpireDateEnabled === 'yes') {
					if (oc_appconfig.core.defaultExpireDateEnforced === 'yes') {
						defaultExpireMessage = t('core', 'The public link will expire no later than {days} days after it is created',  {'days': oc_appconfig.core.defaultExpireDate}) + '<br/>';
					} else {
						defaultExpireMessage = t('core', 'By default the public link will expire after {days} days', {'days': oc_appconfig.core.defaultExpireDate}) + '<br/>';
					}
				}

				html += '<input id="linkText" type="text" readonly="readonly" />';

				html += '<input type="checkbox" name="showPassword" id="showPassword" value="1" style="display:none;" /><label for="showPassword" style="display:none;">'+t('core', 'Password protect')+'</label>';
				html += '<div id="linkPass">';
				html += '<input id="linkPassText" type="password" placeholder="'+t('core', 'Password')+'" />';
				html += '</div>';
				if (itemType === 'folder' && (possiblePermissions & OC.PERMISSION_CREATE) && publicUploadEnabled === 'yes') {
					html += '<div id="allowPublicUploadWrapper" style="display:none;">';
					html += '<input type="checkbox" value="1" name="allowPublicUpload" id="sharingDialogAllowPublicUpload"' + ((allowPublicUploadStatus) ? 'checked="checked"' : '') + ' />';
					html += '<label for="sharingDialogAllowPublicUpload">' + t('core', 'Allow Public Upload') + '</label>';
					html += '</div>';
				}
				html += '</div><form id="emailPrivateLink" >';
				html += '<input id="email" style="display:none; width:62%;" value="" placeholder="'+t('core', 'Email link to person')+'" type="text" />';
				html += '<input id="emailButton" style="display:none;" type="submit" value="'+t('core', 'Send')+'" />';
				html += '</form>';
			}

			html += '<div id="expiration">';
			html += '<input type="checkbox" name="expirationCheckbox" id="expirationCheckbox" value="1" /><label for="expirationCheckbox">'+t('core', 'Set expiration date')+'</label>';
			html += '<input id="expirationDate" type="text" placeholder="'+t('core', 'Expiration date')+'" style="display:none; width:90%;" />';
			html += '<div id="defaultExpireMessage">'+defaultExpireMessage+'</div>';
			html += '</div>';
			dropDownEl = $(html);
			dropDownEl = dropDownEl.appendTo(appendTo);
			// Reset item shares
			OC.Share.itemShares = [];
			if (data.shares) {
				$.each(data.shares, function(index, share) {
					if (share.share_type == OC.Share.SHARE_TYPE_LINK) {
						if ( !('file_target' in share) ) {
							OC.Share.showLink(share.token, share.share_with, itemSource);
						}
					} else {
						if (share.collection) {
							OC.Share.addShareWith(share.share_type, share.share_with, share.share_with_displayname, share.permissions, possiblePermissions, share.mail_send, share.collection);
						} else {
							OC.Share.addShareWith(share.share_type, share.share_with, share.share_with_displayname, share.permissions, possiblePermissions, share.mail_send, false);
						}
					}
					if (share.expiration != null) {
						OC.Share.showExpirationDate(share.expiration);
					}
				});
			}
			$('#shareWith').autocomplete({minLength: 1, source: function(search, response) {
	//			if (cache[search.term]) {
	//				response(cache[search.term]);
	//			} else {
					$.get(OC.filePath('core', 'ajax', 'share.php'), { fetch: 'getShareWith', search: search.term, itemShares: OC.Share.itemShares }, function(result) {
						if (result.status == 'success' && result.data.length > 0) {
							$( "#shareWith" ).autocomplete( "option", "autoFocus", true );
							response(result.data);
						} else {
							// Suggest sharing via email if valid email address
//							var pattern = new RegExp(/^[+a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/i);
//							if (pattern.test(search.term)) {
//								response([{label: t('core', 'Share via email:')+' '+search.term, value: {shareType: OC.Share.SHARE_TYPE_EMAIL, shareWith: search.term}}]);
//							} else {
								$( "#shareWith" ).autocomplete( "option", "autoFocus", false );
								response([t('core', 'No people found')]);
//							}
						}
					});
	//			}
			},
			focus: function(event, focused) {
				event.preventDefault();
			},
			select: function(event, selected) {
				event.stopPropagation();
				var itemType = $('#dropdown').data('item-type');
				var itemSource = $('#dropdown').data('item-source');
				var itemSourceName = $('#dropdown').data('item-source-name');
				var shareType = selected.item.value.shareType;
				var shareWith = selected.item.value.shareWith;
				$(this).val(shareWith);
				// Default permissions are Edit (CRUD) and Share
				// Check if these permissions are possible
				var permissions = OC.PERMISSION_READ;
				if (possiblePermissions & OC.PERMISSION_UPDATE) {
					permissions = permissions | OC.PERMISSION_UPDATE;
				}
				if (possiblePermissions & OC.PERMISSION_CREATE) {
					permissions = permissions | OC.PERMISSION_CREATE;
				}
				if (possiblePermissions & OC.PERMISSION_DELETE) {
					permissions = permissions | OC.PERMISSION_DELETE;
				}
				if (possiblePermissions & OC.PERMISSION_SHARE) {
					permissions = permissions | OC.PERMISSION_SHARE;
				}

				OC.Share.share(itemType, itemSource, shareType, shareWith, permissions, itemSourceName, function() {
					OC.Share.addShareWith(shareType, shareWith, selected.item.label, permissions, possiblePermissions);
					$('#shareWith').val('');
					OC.Share.updateIcon(itemType, itemSource);
				});
				return false;
			}
			})
			// customize internal _renderItem function to display groups and users differently
			.data("ui-autocomplete")._renderItem = function( ul, item ) {
				var insert = $( "<a>" );
				var text = (item.value.shareType == 1)? item.label + ' ('+t('core', 'group')+')' : item.label;
				insert.text( text );
				if(item.value.shareType == 1) {
					insert = insert.wrapInner('<strong></strong>');
				}
				return $( "<li>" )
					.addClass((item.value.shareType == 1)?'group':'user')
					.append( insert )
					.appendTo( ul );
			};
			if (link) {
				$('#email').autocomplete({
					minLength: 1,
					source: function (search, response) {
						$.get(OC.filePath('core', 'ajax', 'share.php'), { fetch: 'getShareWithEmail', search: search.term }, function(result) {
							if (result.status == 'success' && result.data.length > 0) {
								response(result.data);
							}
						});
						},
					select: function( event, item ) {
						$('#email').val(item.item.email);
						return false;
					}
				})
				.data("ui-autocomplete")._renderItem = function( ul, item ) {
					return $( "<li>" )
						.append( "<a>" + item.displayname + "<br>" + item.email + "</a>" )
						.appendTo( ul );
				};
			}

		} else {
			html += '<input id="shareWith" type="text" placeholder="'+t('core', 'Resharing is not allowed')+'" style="width:90%;" disabled="disabled"/>';
			html += '</div>';
			dropDownEl = $(html);
			dropDownEl.appendTo(appendTo);
		}
		dropDownEl.attr('data-item-source-name', filename);
		$('#dropdown').show('blind', function() {
			OC.Share.droppedDown = true;
		});
		if ($('html').hasClass('lte9')){
			$('#dropdown input[placeholder]').placeholder();
		}
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
	addShareWith:function(shareType, shareWith, shareWithDisplayName, permissions, possiblePermissions, mailSend, collection) {
		if (shareType === 1) {
			shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'group') + ')';
		}
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
			var showCrudsButton;
			html += '<a href="#" class="unshare"><img class="svg" alt="'+t('core', 'Unshare')+'" src="'+OC.imagePath('core', 'actions/delete')+'"/></a>';
			html += '<span class="username">' + escapeHTML(shareWithDisplayName) + '</span>';
			var mailNotificationEnabled = $('input:hidden[name=mailNotificationEnabled]').val();
			if (mailNotificationEnabled === 'yes') {
				var checked = '';
				if (mailSend === '1') {
					checked = 'checked';
				}
				html += '<label><input type="checkbox" name="mailNotification" class="mailNotification" ' + checked + ' />'+t('core', 'notify by email')+'</label> ';
			}
			if (possiblePermissions & OC.PERMISSION_CREATE || possiblePermissions & OC.PERMISSION_UPDATE || possiblePermissions & OC.PERMISSION_DELETE) {
				html += '<label><input type="checkbox" name="edit" class="permissions" '+editChecked+' />'+t('core', 'can edit')+'</label> ';
			}
			showCrudsButton = '<a href="#" class="showCruds"><img class="svg" alt="'+t('core', 'access control')+'" src="'+OC.imagePath('core', 'actions/triangle-s')+'"/></a>';
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
			html = $(html).appendTo('#shareWithList');
			// insert cruds button into last label element
			var lastLabel = html.find('>label:last');
			if (lastLabel.exists()){
				lastLabel.append(showCrudsButton);
			}
			else{
				html.find('.cruds').before(showCrudsButton);
			}
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
			$('#linkPassText').attr('placeholder', '**********');
		}
		$('#expiration').show();
		$('#defaultExpireMessage').show();
		$('#emailPrivateLink #email').show();
		$('#emailPrivateLink #emailButton').show();
		$('#allowPublicUploadWrapper').show();
	},
	hideLink:function() {
		$('#linkText').hide('blind');
		$('#defaultExpireMessage').hide();
		$('#showPassword').hide();
		$('#showPassword+label').hide();
		$('#linkPass').hide();
		$('#emailPrivateLink #email').hide();
		$('#emailPrivateLink #emailButton').hide();
		$('#allowPublicUploadWrapper').hide();
	},
	dirname:function(path) {
		return path.replace(/\\/g,'/').replace(/\/[^\/]*$/, '');
	},
	showExpirationDate:function(date) {
		$('#expirationCheckbox').attr('checked', true);
		$('#expirationDate').val(date);
		$('#expirationDate').show('blind');
		$('#expirationDate').css('display','block');
		$('#expirationDate').datepicker({
			dateFormat : 'dd-mm-yy'
		});
	}
};

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
			&& !target.closest('#ui-datepicker-div').length && !target.closest('.ui-autocomplete').length;
		if (OC.Share.droppedDown && isMatched && $('#dropdown').has(event.target).length === 0) {
			OC.Share.hideDropDown();
		}
	});

	$(document).on('click', '#dropdown .showCruds', function() {
		$(this).closest('li').find('.cruds').toggle();
		return false;
	});

	$(document).on('click', '#dropdown .unshare', function() {
		var $li = $(this).closest('li');
		var itemType = $('#dropdown').data('item-type');
		var itemSource = $('#dropdown').data('item-source');
		var shareType = $li.data('share-type');
		var shareWith = $li.attr('data-share-with');
		OC.Share.unshare(itemType, itemSource, shareType, shareWith, function() {
			$li.remove();
			var index = OC.Share.itemShares[shareType].indexOf(shareWith);
			OC.Share.itemShares[shareType].splice(index, 1);
			OC.Share.updateIcon(itemType, itemSource);
			if (typeof OC.Share.statuses[itemSource] === 'undefined') {
				$('#expiration').hide('blind');
			}
		});
		return false;
	});

	$(document).on('change', '#dropdown .permissions', function() {
		var li = $(this).closest('li');
		if ($(this).attr('name') == 'edit') {
			var checkboxes = $('.permissions', li);
			var checked = $(this).is(':checked');
			// Check/uncheck Create, Update, and Delete checkboxes if Edit is checked/unck
			$(checkboxes).filter('input[name="create"]').attr('checked', checked);
			$(checkboxes).filter('input[name="update"]').attr('checked', checked);
			$(checkboxes).filter('input[name="delete"]').attr('checked', checked);
		} else {
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
			li.data('share-type'),
			li.attr('data-share-with'),
			permissions);
	});

	$(document).on('change', '#dropdown #linkCheckbox', function() {
		var itemType = $('#dropdown').data('item-type');
		var itemSource = $('#dropdown').data('item-source');
		var itemSourceName = $('#dropdown').data('item-source-name');
		if (this.checked) {
			// Create a link
			OC.Share.share(itemType, itemSource, OC.Share.SHARE_TYPE_LINK, '', OC.PERMISSION_READ, itemSourceName, function(data) {
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
					$('#expiration').hide('blind');
				}
			});
		}
	});

	$(document).on('click', '#dropdown #linkText', function() {
		$(this).focus();
		$(this).select();
	});

	// Handle the Allow Public Upload Checkbox
	$(document).on('click', '#sharingDialogAllowPublicUpload', function() {

		// Gather data
		var allowPublicUpload = $(this).is(':checked');
		var itemType = $('#dropdown').data('item-type');
		var itemSource = $('#dropdown').data('item-source');
		var itemSourceName = $('#dropdown').data('item-source-name');
		var permissions = 0;

		// Calculate permissions
		if (allowPublicUpload) {
			permissions = OC.PERMISSION_UPDATE + OC.PERMISSION_CREATE + OC.PERMISSION_READ;
		} else {
			permissions = OC.PERMISSION_READ;
		}

		// Update the share information
		OC.Share.share(itemType, itemSource, OC.Share.SHARE_TYPE_LINK, '', permissions, itemSourceName, function(data) {
		});
	});

	$(document).on('click', '#dropdown #showPassword', function() {
		$('#linkPass').toggle('blind');
		if (!$('#showPassword').is(':checked') ) {
			var itemType = $('#dropdown').data('item-type');
			var itemSource = $('#dropdown').data('item-source');
			var itemSourceName = $('#dropdown').data('item-source-name');
			var allowPublicUpload = $('#sharingDialogAllowPublicUpload').is(':checked');
			var permissions = 0;

			// Calculate permissions
			if (allowPublicUpload) {
				permissions = OC.PERMISSION_UPDATE + OC.PERMISSION_CREATE + OC.PERMISSION_READ;
			} else {
				permissions = OC.PERMISSION_READ;
			}


			OC.Share.share(itemType, itemSource, OC.Share.SHARE_TYPE_LINK, '', permissions, itemSourceName);
		} else {
			$('#linkPassText').focus();
		}
	});

	$(document).on('focusout keyup', '#dropdown #linkPassText', function(event) {
		var linkPassText = $('#linkPassText');
		if ( linkPassText.val() != '' && (event.type == 'focusout' || event.keyCode == 13) ) {

			var allowPublicUpload = $('#sharingDialogAllowPublicUpload').is(':checked');
			var dropDown = $('#dropdown');
			var itemType = dropDown.data('item-type');
			var itemSource = dropDown.data('item-source');
			var itemSourceName = $('#dropdown').data('item-source-name');
			var permissions = 0;

			// Calculate permissions
			if (allowPublicUpload) {
				permissions = OC.PERMISSION_UPDATE + OC.PERMISSION_CREATE + OC.PERMISSION_READ;
			} else {
				permissions = OC.PERMISSION_READ;
			}

			OC.Share.share(itemType, itemSource, OC.Share.SHARE_TYPE_LINK, $('#linkPassText').val(), permissions, itemSourceName, function() {
				console.log("password set to: '" + linkPassText.val() +"' by event: " + event.type);
				linkPassText.val('');
				linkPassText.attr('placeholder', t('core', 'Password protected'));
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
				$('#expirationDate').hide('blind');
				if (oc_appconfig.core.defaultExpireDateEnforced === 'no') {
					$('#defaultExpireMessage'). show('blind');
				}
			});
		}
	});

	$(document).on('change', '#dropdown #expirationDate', function() {
		var itemType = $('#dropdown').data('item-type');
		var itemSource = $('#dropdown').data('item-source');

		$(this).tipsy('hide');
		$(this).removeClass('error');

		$.post(OC.filePath('core', 'ajax', 'share.php'), { action: 'setExpirationDate', itemType: itemType, itemSource: itemSource, date: $(this).val() }, function(result) {
			if (!result || result.status !== 'success') {
				var expirationDateField = $('#dropdown #expirationDate');
				if (!result.data.message) {
					expirationDateField.attr('original-title', t('core', 'Error setting expiration date'));
				} else {
					expirationDateField.attr('original-title', result.data.message);
				}
				expirationDateField.tipsy({gravity: 'n', fade: true});
				expirationDateField.tipsy('show');
				expirationDateField.addClass('error');
			} else {
				if (oc_appconfig.core.defaultExpireDateEnforced === 'no') {
					$('#defaultExpireMessage'). hide('blind');
				}
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
		var expirationDate = '';
		if ( $('#expirationCheckbox').is(':checked') === true ) {
			expirationDate = $( "#expirationDate" ).val();
		}
		if (email != '') {
			$('#email').prop('disabled', true);
			$('#email').val(t('core', 'Sending ...'));
			$('#emailButton').prop('disabled', true);

			$.post(OC.filePath('core', 'ajax', 'share.php'), { action: 'email', toaddress: email, link: link, itemType: itemType, itemSource: itemSource, file: file, expiration: expirationDate},
				function(result) {
					$('#email').prop('disabled', false);
					$('#emailButton').prop('disabled', false);
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

	$(document).on('click', '#dropdown input[name=mailNotification]', function() {
		var $li = $(this).closest('li');
		var itemType = $('#dropdown').data('item-type');
		var itemSource = $('#dropdown').data('item-source');
		var action = '';
		if (this.checked) {
			action = 'informRecipients';
		} else {
			action = 'informRecipientsDisabled';
		}

		var shareType = $li.data('share-type');
		var shareWith = $li.attr('data-share-with');

		$.post(OC.filePath('core', 'ajax', 'share.php'), {action: action, recipient: shareWith, shareType: shareType, itemSource: itemSource, itemType: itemType}, function(result) {
			if (result.status !== 'success') {
				OC.dialogs.alert(t('core', result.data.message), t('core', 'Warning'));
			}
		});

});


});
