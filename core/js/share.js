/* global escapeHTML */

/**
 * @namespace
 */
OC.Share={
	SHARE_TYPE_USER:0,
	SHARE_TYPE_GROUP:1,
	SHARE_TYPE_LINK:3,
	SHARE_TYPE_EMAIL:4,
	SHARE_TYPE_REMOTE:6,

	/**
	 * Regular expression for splitting parts of remote share owners:
	 * "user@example.com/path/to/owncloud"
	 * "user@anotherexample.com@example.com/path/to/owncloud
	 */
	_REMOTE_OWNER_REGEXP: new RegExp("^([^@]*)@(([^@]*)@)?([^/]*)(.*)?$"),

	/**
	 * @deprecated use OC.Share.currentShares instead
	 */
	itemShares:[],
	/**
	 * Full list of all share statuses
	 */
	statuses:{},
	/**
	 * Shares for the currently selected file.
	 * (for which the dropdown is open)
	 *
	 * Key is item type and value is an array or
	 * shares of the given item type.
	 */
	currentShares: {},
	/**
	 * Whether the share dropdown is opened.
	 */
	droppedDown:false,
	/**
	 * Loads ALL share statuses from server, stores them in
	 * OC.Share.statuses then calls OC.Share.updateIcons() to update the
	 * files "Share" icon to "Shared" according to their share status and
	 * share type.
	 *
	 * If a callback is specified, the update step is skipped.
	 *
	 * @param itemType item type
	 * @param fileList file list instance, defaults to OCA.Files.App.fileList
	 * @param callback function to call after the shares were loaded
	 */
	loadIcons:function(itemType, fileList, callback) {
		// Load all share icons
		$.get(
			OC.filePath('core', 'ajax', 'share.php'),
			{
				fetch: 'getItemsSharedStatuses',
				itemType: itemType
			}, function(result) {
				if (result && result.status === 'success') {
					OC.Share.statuses = {};
					$.each(result.data, function(item, data) {
						OC.Share.statuses[item] = data;
					});
					if (_.isFunction(callback)) {
						callback(OC.Share.statuses);
					} else {
						OC.Share.updateIcons(itemType, fileList);
					}
				}
			}
		);
	},
	/**
	 * Updates the files' "Share" icons according to the known
	 * sharing states stored in OC.Share.statuses.
	 * (not reloaded from server)
	 *
	 * @param itemType item type
	 * @param fileList file list instance
	 * defaults to OCA.Files.App.fileList
	 */
	updateIcons:function(itemType, fileList){
		var item;
		var $fileList;
		var currentDir;
		if (!fileList && OCA.Files) {
			fileList = OCA.Files.App.fileList;
		}
		// fileList is usually only defined in the files app
		if (fileList) {
			$fileList = fileList.$fileList;
			currentDir = fileList.getCurrentDirectory();
		}
		// TODO: iterating over the files might be more efficient
		for (item in OC.Share.statuses){
			var image = OC.imagePath('core', 'actions/share');
			var data = OC.Share.statuses[item];
			var hasLink = data.link;
			// Links override shared in terms of icon display
			if (hasLink) {
				image = OC.imagePath('core', 'actions/public');
			}
			if (itemType !== 'file' && itemType !== 'folder') {
				$('a.share[data-item="'+item+'"]').css('background', 'url('+image+') no-repeat center');
			} else {
				// TODO: ultimately this part should be moved to files_sharing app
				var file = $fileList.find('tr[data-id="'+item+'"]');
				var shareFolder = OC.imagePath('core', 'filetypes/folder-shared');
				var img;
				if (file.length > 0) {
					this.markFileAsShared(file, true, hasLink);
				} else {
					var dir = currentDir;
					if (dir.length > 1) {
						var last = '';
						var path = dir;
						// Search for possible parent folders that are shared
						while (path != last) {
							if (path === data.path && !data.link) {
								var actions = $fileList.find('.fileactions .action[data-action="Share"]');
								var files = $fileList.find('.filename');
								var i;
								for (i = 0; i < actions.length; i++) {
									// TODO: use this.markFileAsShared()
									img = $(actions[i]).find('img');
									if (img.attr('src') !== OC.imagePath('core', 'actions/public')) {
										img.attr('src', image);
										$(actions[i]).addClass('permanent');
										$(actions[i]).html(' <span>'+t('core', 'Shared')+'</span>').prepend(img);
									}
								}
								for(i = 0; i < files.length; i++) {
									if ($(files[i]).closest('tr').data('type') === 'dir') {
										$(files[i]).find('.thumbnail').css('background-image', 'url('+shareFolder+')');
									}
								}
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
					image = OC.imagePath('core', 'actions/share');
				}
			}
		});
		if (itemType != 'file' && itemType != 'folder') {
			$('a.share[data-item="'+itemSource+'"]').css('background', 'url('+image+') no-repeat center');
		} else {
			var $tr = $('tr').filterAttr('data-id', String(itemSource));
			if ($tr.length > 0) {
				// it might happen that multiple lists exist in the DOM
				// with the same id
				$tr.each(function() {
					OC.Share.markFileAsShared($(this), shares, link);
				});
			}
		}
		if (shares) {
			OC.Share.statuses[itemSource] = OC.Share.statuses[itemSource] || {};
			OC.Share.statuses[itemSource]['link'] = link;
		} else {
			delete OC.Share.statuses[itemSource];
		}
	},
	/**
	 * Format remote share owner to make it more readable
	 *
	 * @param {String} owner full remote share owner name
	 * @return {String} HTML code for the owner display
	 */
	_formatSharedByOwner: function(owner) {
		var parts = this._REMOTE_OWNER_REGEXP.exec(owner);
		if (!parts) {
			// display as is, most likely to be a simple owner name
			return escapeHTML(owner);
		}

		var userName = parts[1];
		var userDomain = parts[3];
		var server = parts[4];
		var tooltip = userName;
		if (userDomain) {
			tooltip += '@' + userDomain;
		}
		if (server) {
			if (!userDomain) {
				userDomain = '…';
			}
			tooltip += '@' + server;
		}

		var html = '<span class="remoteOwner" title="' + escapeHTML(tooltip) + '">';
		html += '<span class="username">' + escapeHTML(userName) + '</span>';
		if (userDomain) {
			html += '<span class="userDomain">@' + escapeHTML(userDomain) + '</span>';
		}
		html += '</span>';
		return html;
	},
	/**
	 * Marks/unmarks a given file as shared by changing its action icon
	 * and folder icon.
	 *
	 * @param $tr file element to mark as shared
	 * @param hasShares whether shares are available
	 * @param hasLink whether link share is available
	 */
	markFileAsShared: function($tr, hasShares, hasLink) {
		var action = $tr.find('.fileactions .action[data-action="Share"]');
		var type = $tr.data('type');
		var img = action.find('img');
		var message;
		var recipients;
		var owner = $tr.attr('data-share-owner');
		var shareFolderIcon;
		var image = OC.imagePath('core', 'actions/share');
		// update folder icon
		if (type === 'dir' && (hasShares || hasLink || owner)) {
			if (hasLink) {
				shareFolderIcon = OC.imagePath('core', 'filetypes/folder-public');
			}
			else {
				shareFolderIcon = OC.imagePath('core', 'filetypes/folder-shared');
			}
			$tr.find('.filename .thumbnail').css('background-image', 'url(' + shareFolderIcon + ')');
		} else if (type === 'dir') {
			shareFolderIcon = OC.imagePath('core', 'filetypes/folder');
			$tr.find('.filename .thumbnail').css('background-image', 'url(' + shareFolderIcon + ')');
		}
		// update share action text / icon
		if (hasShares || owner) {
			recipients = $tr.attr('data-share-recipients');

			action.addClass('permanent');
			message = t('core', 'Shared');
			// even if reshared, only show "Shared by"
			if (owner) {
				message = this._formatSharedByOwner(owner);
			}
			else if (recipients) {
				message = t('core', 'Shared with {recipients}', {recipients: recipients});
			}
			action.html(' <span>' + message + '</span>').prepend(img);
			if (owner) {
				action.find('.remoteOwner').tipsy({gravity: 's'});
			}
		}
		else {
			action.removeClass('permanent');
			action.html(' <span>'+ escapeHTML(t('core', 'Share'))+'</span>').prepend(img);
		}
		if (hasLink) {
			image = OC.imagePath('core', 'actions/public');
		}
		img.attr('src', image);
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
	share:function(itemType, itemSource, shareType, shareWith, permissions, itemSourceName, expirationDate, callback) {
		// Add a fallback for old share() calls without expirationDate.
		// We should remove this in a later version,
		// after the Apps have been updated.
		if (typeof callback === 'undefined' &&
			typeof expirationDate === 'function') {
			callback = expirationDate;
			expirationDate = '';
			console.warn(
				"Call to 'OC.Share.share()' with too few arguments. " +
				"'expirationDate' was assumed to be 'callback'. " +
				"Please revisit the call and fix the list of arguments."
			);
		}

		return $.post(OC.filePath('core', 'ajax', 'share.php'),
			{
				action: 'share',
				itemType: itemType,
				itemSource: itemSource,
				shareType: shareType,
				shareWith: shareWith,
				permissions: permissions,
				itemSourceName: itemSourceName,
				expirationDate: expirationDate
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
			}
		);
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
		var html = '<div id="dropdown" class="drop shareDropDown" data-item-type="'+itemType+'" data-item-source="'+itemSource+'">';
		if (data !== false && data.reshare !== false && data.reshare.uid_owner !== undefined) {
			if (data.reshare.share_type == OC.Share.SHARE_TYPE_GROUP) {
				html += '<span class="reshare">'+t('core', 'Shared with you and the group {group} by {owner}', {group: data.reshare.share_with, owner: data.reshare.displayname_owner})+'</span>';
			} else {
				html += '<span class="reshare">'+t('core', 'Shared with you by {owner}', {owner: data.reshare.displayname_owner})+'</span>';
			}
			html += '<br />';
			// reduce possible permissions to what the original share allowed
			possiblePermissions = possiblePermissions & data.reshare.permissions;
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

			html += '<label for="shareWith" class="hidden-visually">'+t('core', 'Share')+'</label>';
			html += '<input id="shareWith" type="text" placeholder="'+t('core', 'Share with user or group …')+'" />';
			html += '<span class="shareWithLoading icon-loading-small hidden"></span>';
			html += '<ul id="shareWithList">';
			html += '</ul>';
			var linksAllowed = $('#allowShareWithLink').val() === 'yes';
			if (link && linksAllowed) {
				html += '<div id="link" class="linkShare">';
				html += '<span class="icon-loading-small hidden"></span>';
				html += '<input type="checkbox" name="linkCheckbox" id="linkCheckbox" value="1" /><label for="linkCheckbox">'+t('core', 'Share link')+'</label>';
				html += '<br />';

				var defaultExpireMessage = '';
				if ((itemType === 'folder' || itemType === 'file') && oc_appconfig.core.defaultExpireDateEnforced) {
					defaultExpireMessage = t('core', 'The public link will expire no later than {days} days after it is created',  {'days': oc_appconfig.core.defaultExpireDate}) + '<br/>';
				}

				html += '<label for="linkText" class="hidden-visually">'+t('core', 'Link')+'</label>';
				html += '<input id="linkText" type="text" readonly="readonly" />';
				html += '<input type="checkbox" name="showPassword" id="showPassword" value="1" style="display:none;" /><label for="showPassword" style="display:none;">'+t('core', 'Password protect')+'</label>';
				html += '<div id="linkPass">';
				html += '<label for="linkPassText" class="hidden-visually">'+t('core', 'Password')+'</label>';
				html += '<input id="linkPassText" type="password" placeholder="'+t('core', 'Choose a password for the public link')+'" />';
				html += '<span class="icon-loading-small hidden"></span>';
				html += '</div>';

				if (itemType === 'folder' && (possiblePermissions & OC.PERMISSION_CREATE) && publicUploadEnabled === 'yes') {
					html += '<div id="allowPublicUploadWrapper" style="display:none;">';
					html += '<span class="icon-loading-small hidden"></span>';
					html += '<input type="checkbox" value="1" name="allowPublicUpload" id="sharingDialogAllowPublicUpload"' + ((allowPublicUploadStatus) ? 'checked="checked"' : '') + ' />';
					html += '<label for="sharingDialogAllowPublicUpload">' + t('core', 'Allow editing') + '</label>';
					html += '</div>';
				}
				html += '</div>';
				var mailPublicNotificationEnabled = $('input:hidden[name=mailPublicNotificationEnabled]').val();
				if (mailPublicNotificationEnabled === 'yes') {
					html += '<form id="emailPrivateLink">';
					html += '<input id="email" style="display:none; width:62%;" value="" placeholder="'+t('core', 'Email link to person')+'" type="text" />';
					html += '<input id="emailButton" style="display:none;" type="submit" value="'+t('core', 'Send')+'" />';
					html += '</form>';
				}
			}

			html += '<div id="expiration">';
			html += '<input type="checkbox" name="expirationCheckbox" id="expirationCheckbox" value="1" /><label for="expirationCheckbox">'+t('core', 'Set expiration date')+'</label>';
			html += '<label for="expirationDate" class="hidden-visually">'+t('core', 'Expiration')+'</label>';
			html += '<input id="expirationDate" type="text" placeholder="'+t('core', 'Expiration date')+'" style="display:none; width:90%;" />';
			html += '<em id="defaultExpireMessage">'+defaultExpireMessage+'</em>';
			html += '</div>';
			dropDownEl = $(html);
			dropDownEl = dropDownEl.appendTo(appendTo);
			// Reset item shares
			OC.Share.itemShares = [];
			OC.Share.currentShares = {};
			if (data.shares) {
				$.each(data.shares, function(index, share) {
					if (share.share_type == OC.Share.SHARE_TYPE_LINK) {
						if (itemSource === share.file_source || itemSource === share.item_source) {
							OC.Share.showLink(share.token, share.share_with, itemSource);
						}
					} else {
						if (share.collection) {
							OC.Share.addShareWith(share.share_type, share.share_with, share.share_with_displayname, share.permissions, possiblePermissions, share.mail_send, share.collection);
						} else {
							if (share.share_type === OC.Share.SHARE_TYPE_REMOTE) {
								OC.Share.addShareWith(share.share_type, share.share_with, share.share_with_displayname, share.permissions, OC.PERMISSION_READ | OC.PERMISSION_UPDATE | OC.PERMISSION_CREATE, share.mail_send, false);
							} else {
								OC.Share.addShareWith(share.share_type, share.share_with, share.share_with_displayname, share.permissions, possiblePermissions, share.mail_send, false);
							}
						}
					}
					if (share.expiration != null) {
						OC.Share.showExpirationDate(share.expiration, share.stime);
					}
				});
			}
			$('#shareWith').autocomplete({minLength: 2, delay: 750, source: function(search, response) {
				var $loading = $('#dropdown .shareWithLoading');
				$loading.removeClass('hidden');
				$.get(OC.filePath('core', 'ajax', 'share.php'), { fetch: 'getShareWith', search: search.term.trim(), itemShares: OC.Share.itemShares, itemType: itemType }, function(result) {
					$loading.addClass('hidden');
					if (result.status == 'success' && result.data.length > 0) {
						$( "#shareWith" ).autocomplete( "option", "autoFocus", true );
						response(result.data);
					} else {
						response();
					}
				});
			},
			focus: function(event, focused) {
				event.preventDefault();
			},
			select: function(event, selected) {
				event.stopPropagation();
				var $dropDown = $('#dropdown');
				var itemType = $dropDown.data('item-type');
				var itemSource = $dropDown.data('item-source');
				var itemSourceName = $dropDown.data('item-source-name');
				var expirationDate = '';
				if ( $('#expirationCheckbox').is(':checked') === true ) {
					expirationDate = $( "#expirationDate" ).val();
				}
				var shareType = selected.item.value.shareType;
				var shareWith = selected.item.value.shareWith;
				$(this).val(shareWith);
				// Default permissions are Edit (CRUD) and Share
				// Check if these permissions are possible
				var permissions = OC.PERMISSION_READ;
				if (shareType === OC.Share.SHARE_TYPE_REMOTE) {
					permissions = OC.PERMISSION_CREATE | OC.PERMISSION_UPDATE | OC.PERMISSION_READ;
				} else {
					if (possiblePermissions & OC.PERMISSION_UPDATE) {
						permissions = permissions | OC.PERMISSION_UPDATE;
					}
					if (possiblePermissions & OC.PERMISSION_CREATE) {
						permissions = permissions | OC.PERMISSION_CREATE;
					}
					if (possiblePermissions & OC.PERMISSION_DELETE) {
						permissions = permissions | OC.PERMISSION_DELETE;
					}
					if (oc_appconfig.core.resharingAllowed && (possiblePermissions & OC.PERMISSION_SHARE)) {
						permissions = permissions | OC.PERMISSION_SHARE;
					}
				}

				var $input = $(this);
				var $loading = $dropDown.find('.shareWithLoading');
				$loading.removeClass('hidden');
				$input.val(t('core', 'Adding user...'));
				$input.prop('disabled', true);

				OC.Share.share(itemType, itemSource, shareType, shareWith, permissions, itemSourceName, expirationDate, function() {
					$input.prop('disabled', false);
					$loading.addClass('hidden');
					var posPermissions = possiblePermissions;
					if (shareType === OC.Share.SHARE_TYPE_REMOTE) {
						posPermissions = permissions;
					}
					OC.Share.addShareWith(shareType, shareWith, selected.item.label, permissions, posPermissions);
					$('#shareWith').val('');
					$('#dropdown').trigger(new $.Event('sharesChanged', {shares: OC.Share.currentShares}));
					OC.Share.updateIcon(itemType, itemSource);
				});
				return false;
			}
			})
			// customize internal _renderItem function to display groups and users differently
			.data("ui-autocomplete")._renderItem = function( ul, item ) {
				var insert = $( "<a>" );
				var text = item.label;
				if (item.value.shareType === OC.Share.SHARE_TYPE_GROUP) {
					text = text +  ' ('+t('core', 'group')+')';
				} else if (item.value.shareType === OC.Share.SHARE_TYPE_REMOTE) {
					text = text +  ' ('+t('core', 'remote')+')';
				}
				insert.text( text );
				if(item.value.shareType === OC.Share.SHARE_TYPE_GROUP) {
					insert = insert.wrapInner('<strong></strong>');
				}
				return $( "<li>" )
					.addClass((item.value.shareType === OC.Share.SHARE_TYPE_GROUP)?'group':'user')
					.append( insert )
					.appendTo( ul );
			};
			if (link && linksAllowed && $('#email').length != 0) {
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
					return $('<li>')
						.append('<a>' + escapeHTML(item.displayname) + "<br>" + escapeHTML(item.email) + '</a>' )
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
		OC.Share.currentShares = null;
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
		var shareItem = {
			share_type: shareType,
			share_with: shareWith,
			share_with_displayname: shareWithDisplayName,
			permissions: permissions
		};
		if (shareType === OC.Share.SHARE_TYPE_GROUP) {
			shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'group') + ')';
		}
		if (shareType === OC.Share.SHARE_TYPE_REMOTE) {
			shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'remote') + ')';
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
			html += '<a href="#" class="unshare"><img class="svg" alt="'+t('core', 'Unshare')+'" title="'+t('core', 'Unshare')+'" src="'+OC.imagePath('core', 'actions/delete')+'"/></a>';
			html += '<span class="username">' + escapeHTML(shareWithDisplayName) + '</span>';
			var mailNotificationEnabled = $('input:hidden[name=mailNotificationEnabled]').val();
			if (mailNotificationEnabled === 'yes' && shareType !== OC.Share.SHARE_TYPE_REMOTE) {
				var checked = '';
				if (mailSend === '1') {
					checked = 'checked';
				}
				html += '<label><input type="checkbox" name="mailNotification" class="mailNotification" ' + checked + ' />'+t('core', 'notify by email')+'</label> ';
			}
			if (oc_appconfig.core.resharingAllowed && (possiblePermissions & OC.PERMISSION_SHARE)) {
				html += '<input id="canShare-'+escapeHTML(shareWith)+'" type="checkbox" name="share" class="permissions" '+shareChecked+' data-permissions="'+OC.PERMISSION_SHARE+'" /><label for="canShare-'+escapeHTML(shareWith)+'">'+t('core', 'can share')+'</label>';
			}
			if (possiblePermissions & OC.PERMISSION_CREATE || possiblePermissions & OC.PERMISSION_UPDATE || possiblePermissions & OC.PERMISSION_DELETE) {
				html += '<input id="canEdit-'+escapeHTML(shareWith)+'" type="checkbox" name="edit" class="permissions" '+editChecked+' /><label for="canEdit-'+escapeHTML(shareWith)+'">'+t('core', 'can edit')+'</label>';
			}
			if (shareType !== OC.Share.SHARE_TYPE_REMOTE) {
				showCrudsButton = '<a href="#" class="showCruds"><img class="svg" alt="'+t('core', 'access control')+'" src="'+OC.imagePath('core', 'actions/triangle-s')+'"/></a>';
			}
			html += '<div class="cruds" style="display:none;">';
			if (possiblePermissions & OC.PERMISSION_CREATE) {
				html += '<input id="canCreate-' + escapeHTML(shareWith) + '" type="checkbox" name="create" class="permissions" ' + createChecked + ' data-permissions="' + OC.PERMISSION_CREATE + '"/><label for="canCreate-' + escapeHTML(shareWith) + '">' + t('core', 'create') + '</label>';
			}
			if (possiblePermissions & OC.PERMISSION_UPDATE) {
				html += '<input id="canUpdate-' + escapeHTML(shareWith) + '" type="checkbox" name="update" class="permissions" ' + updateChecked + ' data-permissions="' + OC.PERMISSION_UPDATE + '"/><label for="canUpdate-' + escapeHTML(shareWith) + '">' + t('core', 'change') + '</label>';
			}
			if (possiblePermissions & OC.PERMISSION_DELETE) {
				html += '<input id="canDelete-' + escapeHTML(shareWith) + '" type="checkbox" name="delete" class="permissions" ' + deleteChecked + ' data-permissions="' + OC.PERMISSION_DELETE + '"/><label for="canDelete-' + escapeHTML(shareWith) + '">' + t('core', 'delete') + '</label>';
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
			if (!OC.Share.currentShares[shareType]) {
				OC.Share.currentShares[shareType] = [];
			}
			OC.Share.currentShares[shareType].push(shareItem);
		}
	},
	showLink:function(token, password, itemSource) {
		OC.Share.itemShares[OC.Share.SHARE_TYPE_LINK] = true;
		$('#linkCheckbox').attr('checked', true);

		//check itemType
		var linkSharetype=$('#dropdown').data('item-type');

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
			// TODO: use oc webroot ?
			var link = parent.location.protocol+'//'+location.host+OC.linkTo('', 'public.php')+'?service=files&'+type+'='+encodeURIComponent(file);
		} else {
			//TODO add path param when showing a link to file in a subfolder of a public link share
			var service='';
			if(linkSharetype === 'folder' || linkSharetype === 'file'){
				service='files';
			}else{
				service=linkSharetype;
			}

			// TODO: use oc webroot ?
			if (service !== 'files') {
				var link = parent.location.protocol+'//'+location.host+OC.linkTo('', 'public.php')+'?service='+service+'&t='+token;
			} else {
				var link = parent.location.protocol+'//'+location.host+OC.generateUrl('/s/')+token;
			}
		}
		$('#linkText').val(link);
		$('#linkText').show('blind');
		$('#linkText').css('display','block');
		if (oc_appconfig.core.enforcePasswordForPublicLink === false || password === null) {
			$('#showPassword').show();
			$('#showPassword+label').show();
		}
		if (password != null) {
			$('#linkPass').show('blind');
			$('#showPassword').attr('checked', true);
			$('#linkPassText').attr('placeholder', '**********');
		}
		$('#expiration').show();
		$('#emailPrivateLink #email').show();
		$('#emailPrivateLink #emailButton').show();
		$('#allowPublicUploadWrapper').show();
	},
	hideLink:function() {
		$('#linkText').hide('blind');
		$('#defaultExpireMessage').hide();
		$('#showPassword').hide();
		$('#showPassword+label').hide();
		$('#linkPass').hide('blind');
		$('#emailPrivateLink #email').hide();
		$('#emailPrivateLink #emailButton').hide();
		$('#allowPublicUploadWrapper').hide();
	},
	dirname:function(path) {
		return path.replace(/\\/g,'/').replace(/\/[^\/]*$/, '');
	},
	/**
	 * Displays the expiration date field
	 *
	 * @param {Date} date current expiration date
	 * @param {int} [shareTime] share timestamp in seconds, defaults to now
	 */
	showExpirationDate:function(date, shareTime) {
		var now = new Date();
		// min date should always be the next day
		var minDate = new Date();
		minDate.setDate(minDate.getDate()+1);
		var datePickerOptions = {
			minDate: minDate,
			maxDate: null
		};
		if (_.isNumber(shareTime)) {
			shareTime = new Date(shareTime * 1000);
		}
		if (!shareTime) {
			shareTime = now;
		}
		$('#expirationCheckbox').attr('checked', true);
		$('#expirationDate').val(date);
		$('#expirationDate').show('blind');
		$('#expirationDate').css('display','block');
		$('#expirationDate').datepicker({
			dateFormat : 'dd-mm-yy'
		});
		if (oc_appconfig.core.defaultExpireDateEnforced) {
			$('#expirationCheckbox').attr('disabled', true);
			shareTime = OC.Util.stripTime(shareTime).getTime();
			// max date is share date + X days
			datePickerOptions.maxDate = new Date(shareTime + oc_appconfig.core.defaultExpireDate * 24 * 3600 * 1000);
		}
		if(oc_appconfig.core.defaultExpireDateEnabled) {
			$('#defaultExpireMessage').show('blind');
		}
		$.datepicker.setDefaults(datePickerOptions);
	}
};

$(document).ready(function() {

	if(typeof monthNames != 'undefined'){
		// min date should always be the next day
		var minDate = new Date();
		minDate.setDate(minDate.getDate()+1);
		$.datepicker.setDefaults({
			monthNames: monthNames,
			monthNamesShort: $.map(monthNames, function(v) { return v.slice(0,3)+'.'; }),
			dayNames: dayNames,
			dayNamesMin: $.map(dayNames, function(v) { return v.slice(0,2); }),
			dayNamesShort: $.map(dayNames, function(v) { return v.slice(0,3)+'.'; }),
			firstDay: firstDay,
			minDate : minDate
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
		var $button = $(this);

		if (!$button.is('a')) {
			$button = $button.closest('a');
		}

		if ($button.hasClass('icon-loading-small')) {
			// deletion in progress
			return false;
		}
		$button.empty().addClass('icon-loading-small');

		OC.Share.unshare(itemType, itemSource, shareType, shareWith, function() {
			$li.remove();
			var index = OC.Share.itemShares[shareType].indexOf(shareWith);
			OC.Share.itemShares[shareType].splice(index, 1);
			// updated list of shares
			OC.Share.currentShares[shareType].splice(index, 1);
			$('#dropdown').trigger(new $.Event('sharesChanged', {shares: OC.Share.currentShares}));
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
		var $dropDown = $('#dropdown');
		var itemType = $dropDown.data('item-type');
		var itemSource = $dropDown.data('item-source');
		var itemSourceName = $dropDown.data('item-source-name');
		var $loading = $dropDown.find('#link .icon-loading-small');
		var $button = $(this);

		if (!$loading.hasClass('hidden')) {
			// already in progress
			return false;
		}

		if (this.checked) {
			var expireDateString = '';
			if (oc_appconfig.core.defaultExpireDateEnabled) {
				var date = new Date().getTime();
				var expireAfterMs = oc_appconfig.core.defaultExpireDate * 24 * 60 * 60 * 1000;
				var expireDate = new Date(date + expireAfterMs);
				var month = expireDate.getMonth() + 1;
				var year = expireDate.getFullYear();
				var day = expireDate.getDate();
				expireDateString = year + "-" + month + '-' + day + ' 00:00:00';
			}
			// Create a link
			if (oc_appconfig.core.enforcePasswordForPublicLink === false) {
				$loading.removeClass('hidden');
				$button.addClass('hidden');
				$button.prop('disabled', true);

				OC.Share.share(itemType, itemSource, OC.Share.SHARE_TYPE_LINK, '', OC.PERMISSION_READ, itemSourceName, expireDateString, function(data) {
					$loading.addClass('hidden');
					$button.removeClass('hidden');
					$button.prop('disabled', false);
					OC.Share.showLink(data.token, null, itemSource);
					$('#dropdown').trigger(new $.Event('sharesChanged', {shares: OC.Share.currentShares}));
					OC.Share.updateIcon(itemType, itemSource);
				});
			} else {
				$('#linkPass').toggle('blind');
				$('#linkPassText').focus();
			}
			if (expireDateString !== '') {
				OC.Share.showExpirationDate(expireDateString);
			}
		} else {
			// Delete private link
			OC.Share.hideLink();
			$('#expiration').hide('blind');
			if ($('#linkText').val() !== '') {
				$loading.removeClass('hidden');
				$button.addClass('hidden');
				$button.prop('disabled', true);
				OC.Share.unshare(itemType, itemSource, OC.Share.SHARE_TYPE_LINK, '', function() {
					$loading.addClass('hidden');
					$button.removeClass('hidden');
					$button.prop('disabled', false);
					OC.Share.itemShares[OC.Share.SHARE_TYPE_LINK] = false;
					$('#dropdown').trigger(new $.Event('sharesChanged', {shares: OC.Share.currentShares}));
					OC.Share.updateIcon(itemType, itemSource);
					if (typeof OC.Share.statuses[itemSource] === 'undefined') {
						$('#expiration').hide('blind');
					}
				});
			}
		}
	});

	$(document).on('click', '#dropdown #linkText', function() {
		$(this).focus();
		$(this).select();
	});

	// Handle the Allow Public Upload Checkbox
	$(document).on('click', '#sharingDialogAllowPublicUpload', function() {

		// Gather data
		var $dropDown = $('#dropdown');
		var allowPublicUpload = $(this).is(':checked');
		var itemType = $dropDown.data('item-type');
		var itemSource = $dropDown.data('item-source');
		var itemSourceName = $dropDown.data('item-source-name');
		var expirationDate = '';
		if ($('#expirationCheckbox').is(':checked') === true) {
			expirationDate = $( "#expirationDate" ).val();
		}
		var permissions = 0;
		var $button = $(this);
		var $loading = $dropDown.find('#allowPublicUploadWrapper .icon-loading-small');

		if (!$loading.hasClass('hidden')) {
			// already in progress
			return false;
		}

		// Calculate permissions
		if (allowPublicUpload) {
			permissions = OC.PERMISSION_UPDATE + OC.PERMISSION_CREATE + OC.PERMISSION_READ;
		} else {
			permissions = OC.PERMISSION_READ;
		}

		// Update the share information
		$button.addClass('hidden');
		$button.prop('disabled', true);
		$loading.removeClass('hidden');
		OC.Share.share(itemType, itemSource, OC.Share.SHARE_TYPE_LINK, '', permissions, itemSourceName, expirationDate, function(data) {
			$loading.addClass('hidden');
			$button.removeClass('hidden');
			$button.prop('disabled', false);
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
			var $loading = $('#showPassword .icon-loading-small');

			// Calculate permissions
			if (allowPublicUpload) {
				permissions = OC.PERMISSION_UPDATE + OC.PERMISSION_CREATE + OC.PERMISSION_READ;
			} else {
				permissions = OC.PERMISSION_READ;
			}

			$loading.removeClass('hidden');
			OC.Share.share(itemType, itemSource, OC.Share.SHARE_TYPE_LINK, '', permissions, itemSourceName).then(function() {
				$loading.addClass('hidden');
				$('#linkPassText').attr('placeholder', t('core', 'Choose a password for the public link'));
			});
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
			var $loading = dropDown.find('#linkPass .icon-loading-small');

			// Calculate permissions
			if (allowPublicUpload) {
				permissions = OC.PERMISSION_UPDATE + OC.PERMISSION_CREATE + OC.PERMISSION_READ;
			} else {
				permissions = OC.PERMISSION_READ;
			}

			$loading.removeClass('hidden');
			OC.Share.share(itemType, itemSource, OC.Share.SHARE_TYPE_LINK, $('#linkPassText').val(), permissions, itemSourceName, function(data) {
				$loading.addClass('hidden');
				linkPassText.val('');
				linkPassText.attr('placeholder', t('core', 'Password protected'));

				if (oc_appconfig.core.enforcePasswordForPublicLink) {
					OC.Share.showLink(data.token, "password set", itemSource);
					OC.Share.updateIcon(itemType, itemSource);
				}
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
				if (oc_appconfig.core.defaultExpireDateEnforced === false) {
					$('#defaultExpireMessage').show('blind');
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
