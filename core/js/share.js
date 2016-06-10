/* global escapeHTML */

/**
 * @namespace
 */
OC.Share = _.extend(OC.Share || {}, {
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
	_REMOTE_OWNER_REGEXP: new RegExp("^([^@]*)@(([^@]*)@)?([^/]*)([/](.*)?)?$"),

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
		var path = fileList.dirInfo.path;
		if (path === '/') {
			path = '';
		}
		path += '/' + fileList.dirInfo.name;

		// Load all share icons
		$.get(
			OC.linkToOCS('apps/files_sharing/api/v1', 2) + 'shares',
			{
				subfiles: 'true',
				path: path,
				format: 'json'
			}, function(result) {
				if (result && result.ocs.meta.statuscode === 200) {
					OC.Share.statuses = {};
					$.each(result.ocs.data, function(it, share) {
						if (!(share.item_source in OC.Share.statuses)) {
							OC.Share.statuses[share.item_source] = {link: false};
						}
						if (share.share_type === OC.Share.SHARE_TYPE_LINK) {
							OC.Share.statuses[share.item_source] = {link: true};
						}
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
			var iconClass = 'icon-share';
			var data = OC.Share.statuses[item];
			var hasLink = data.link;
			// Links override shared in terms of icon display
			if (hasLink) {
				iconClass = 'icon-public';
			}
			if (itemType !== 'file' && itemType !== 'folder') {
				$('a.share[data-item="'+item+'"] .icon').removeClass('icon-share icon-public').addClass(iconClass);
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
										$(actions[i]).html('<span> '+t('core', 'Shared')+'</span>').prepend(img);
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
		var iconClass = '';
		$.each(OC.Share.itemShares, function(index) {
			if (OC.Share.itemShares[index]) {
				if (index == OC.Share.SHARE_TYPE_LINK) {
					if (OC.Share.itemShares[index] == true) {
						shares = true;
						iconClass = 'icon-public';
						link = true;
						return;
					}
				} else if (OC.Share.itemShares[index].length > 0) {
					shares = true;
					iconClass = 'icon-share';
				}
			}
		});
		if (itemType != 'file' && itemType != 'folder') {
			$('a.share[data-item="'+itemSource+'"] .icon').removeClass('icon-share icon-public').addClass(iconClass);
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
	 * Format a remote address
	 *
	 * @param {String} remoteAddress full remote share
	 * @return {String} HTML code to display
	 */
	_formatRemoteShare: function(remoteAddress) {
		var parts = this._REMOTE_OWNER_REGEXP.exec(remoteAddress);
		if (!parts) {
			// display as is, most likely to be a simple owner name
			return escapeHTML(remoteAddress);
		}

		var userName = parts[1];
		var userDomain = parts[3];
		var server = parts[4];
		var dir = parts[6];
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

		var html = '<span class="remoteAddress" title="' + escapeHTML(tooltip) + '">';
		html += '<span class="username">' + escapeHTML(userName) + '</span>';
		if (userDomain) {
			html += '<span class="userDomain">@' + escapeHTML(userDomain) + '</span>';
		}
		html += '</span>';
		return html;
	},
	/**
	 * Loop over all recipients in the list and format them using
	 * all kind of fancy magic.
	 *
	 * @param {String[]} recipients array of all the recipients
	 * @return {String[]} modified list of recipients
	 */
	_formatShareList: function(recipients) {
		var _parent = this;
		return $.map(recipients, function(recipient) {
			recipient = _parent._formatRemoteShare(recipient);
			return recipient;
		});
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
		var icon = action.find('.icon');
		var message;
		var recipients;
		var owner = $tr.attr('data-share-owner');
		var shareFolderIcon;
		var iconClass = 'icon-share';
		action.removeClass('shared-style');
		// update folder icon
		if (type === 'dir' && (hasShares || hasLink || owner)) {
			if (hasLink) {
				shareFolderIcon = OC.MimeType.getIconUrl('dir-public');
			}
			else {
				shareFolderIcon = OC.MimeType.getIconUrl('dir-shared');
			}
			$tr.find('.filename .thumbnail').css('background-image', 'url(' + shareFolderIcon + ')');
			$tr.attr('data-icon', shareFolderIcon);
		} else if (type === 'dir') {
			var mountType = $tr.attr('data-mounttype');
			// FIXME: duplicate of FileList._createRow logic for external folder,
			// need to refactor the icon logic into a single code path eventually
			if (mountType && mountType.indexOf('external') === 0) {
				shareFolderIcon = OC.MimeType.getIconUrl('dir-external');
				$tr.attr('data-icon', shareFolderIcon);
			} else {
				shareFolderIcon = OC.MimeType.getIconUrl('dir');
				// back to default
				$tr.removeAttr('data-icon');
			}
			$tr.find('.filename .thumbnail').css('background-image', 'url(' + shareFolderIcon + ')');
		}
		// update share action text / icon
		if (hasShares || owner) {
			recipients = $tr.attr('data-share-recipients');
			action.addClass('shared-style');

			message = t('core', 'Shared');
			// even if reshared, only show "Shared by"
			if (owner) {
				message = this._formatRemoteShare(owner);
			}
			else if (recipients) {
				message = t('core', 'Shared with {recipients}', {recipients: this._formatShareList(recipients.split(", ")).join(", ")}, 0, {escape: false});
			}
			action.html('<span> ' + message + '</span>').prepend(icon);
			if (owner || recipients) {
				action.find('.remoteAddress').tipsy({gravity: 's'});
			}
		}
		else {
			action.html('<span></span>').prepend(icon);
		}
		if (hasLink) {
			iconClass = 'icon-public';
		}
		icon.removeClass('icon-share icon-public').addClass(iconClass);
	},
	/**
	 *
	 * @param itemType
	 * @param itemSource
	 * @param callback - optional. If a callback is given this method works
	 * asynchronous and the callback will be provided with data when the request
	 * is done.
	 * @returns {OC.Share.Types.ShareInfo}
	 */
	loadItem:function(itemType, itemSource, callback) {
		var data = '';
		var checkReshare = true;
		var async = !_.isUndefined(callback);
		if (typeof OC.Share.statuses[itemSource] === 'undefined') {
			// NOTE: Check does not always work and misses some shares, fix later
			var checkShares = true;
		} else {
			var checkShares = true;
		}
		$.ajax({type: 'GET', url: OC.filePath('core', 'ajax', 'share.php'), data: { fetch: 'getItem', itemType: itemType, itemSource: itemSource, checkReshare: checkReshare, checkShares: checkShares }, async: async, success: function(result) {
			if (result && result.status === 'success') {
				data = result.data;
			} else {
				data = false;
			}
			if(async) {
				callback(data);
			}
		}});

		return data;
	},
	share:function(itemType, itemSource, shareType, shareWith, permissions, itemSourceName, expirationDate, callback, errorCallback) {
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
					if (_.isUndefined(errorCallback)) {
						var msg = t('core', 'Error');
						if (result.data && result.data.message) {
							msg = result.data.message;
						}
						OC.dialogs.alert(msg, t('core', 'Error while sharing'));
					} else {
						errorCallback(result);
					}
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
	showDropDown:function(itemType, itemSource, appendTo, link, possiblePermissions, filename) {
		var configModel = new OC.Share.ShareConfigModel();
		var attributes = {itemType: itemType, itemSource: itemSource, possiblePermissions: possiblePermissions};
		var itemModel = new OC.Share.ShareItemModel(attributes, {configModel: configModel});
		var dialogView = new OC.Share.ShareDialogView({
			id: 'dropdown',
			model: itemModel,
			configModel: configModel,
			className: 'drop shareDropDown',
			attributes: {
				'data-item-source-name': filename,
				'data-item-type': itemType,
				'data-item-source': itemSource
			}
		});
		dialogView.setShowLink(link);
		var $dialog = dialogView.render().$el;
		$dialog.appendTo(appendTo);
		$dialog.slideDown(OC.menuSpeed, function() {
			OC.Share.droppedDown = true;
		});
		itemModel.fetch();
	},
	hideDropDown:function(callback) {
		OC.Share.currentShares = null;
		$('#dropdown').slideUp(OC.menuSpeed, function() {
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
	dirname:function(path) {
		return path.replace(/\\/g,'/').replace(/\/[^\/]*$/, '');
	}
});

$(document).ready(function() {
	if(typeof monthNames != 'undefined'){
		// min date should always be the next day
		var minDate = new Date();
		minDate.setDate(minDate.getDate()+1);
		$.datepicker.setDefaults({
			monthNames: monthNames,
			monthNamesShort: monthNamesShort,
			dayNames: dayNames,
			dayNamesMin: dayNamesMin,
			dayNamesShort: dayNamesShort,
			firstDay: firstDay,
			minDate : minDate
		});
	}

	$(this).click(function(event) {
		var target = $(event.target);
		var isMatched = !target.is('.drop, .ui-datepicker-next, .ui-datepicker-prev, .ui-icon')
			&& !target.closest('#ui-datepicker-div').length && !target.closest('.ui-autocomplete').length;
		if (OC.Share && OC.Share.droppedDown && isMatched && $('#dropdown').has(event.target).length === 0) {
			OC.Share.hideDropDown();
		}
	});



});
