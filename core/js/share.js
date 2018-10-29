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
	SHARE_TYPE_CIRCLE:7,
	SHARE_TYPE_GUEST:8,
	SHARE_TYPE_REMOTE_GROUP:9,
	SHARE_TYPE_ROOM:10,

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
			var iconClass = 'icon-shared';
			var data = OC.Share.statuses[item];
			var hasLink = data.link;
			// Links override shared in terms of icon display
			if (hasLink) {
				iconClass = 'icon-public';
			}
			if (itemType !== 'file' && itemType !== 'folder') {
				$('a.share[data-item="'+item+'"] .icon').removeClass('icon-shared icon-public').addClass(iconClass);
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
					iconClass = 'icon-shared';
				}
			}
		});
		if (itemType != 'file' && itemType != 'folder') {
			$('a.share[data-item="'+itemSource+'"] .icon').removeClass('icon-shared icon-public').addClass(iconClass);
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
			OC.Share.statuses[itemSource].link = link;
		} else {
			delete OC.Share.statuses[itemSource];
		}
	},
	/**
	 * Format a remote address
	 *
	 * @param {String} shareWith userid, full remote share, or whatever
	 * @param {String} shareWithDisplayName
	 * @param {String} message
	 * @return {String} HTML code to display
	 */
	_formatRemoteShare: function(shareWith, shareWithDisplayName, message) {
		var parts = this._REMOTE_OWNER_REGEXP.exec(shareWith);
		if (!parts) {
			// display avatar of the user
			var avatar = '<span class="avatar" data-username="' + escapeHTML(shareWith) + '" title="' + message + " " + escapeHTML(shareWithDisplayName) + '"></span>';
			var hidden = '<span class="hidden-visually">' + message + ' ' + escapeHTML(shareWithDisplayName) + '</span> ';
			return avatar + hidden;
		}

		var userName = parts[1];
		var userDomain = parts[3];
		var server = parts[4];
		var tooltip = message + ' ' + userName;
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
		html += '</span> ';
		return html;
	},
	/**
	 * Loop over all recipients in the list and format them using
	 * all kind of fancy magic.
	 *
	 * @param {Object} recipients array of all the recipients
	 * @return {String[]} modified list of recipients
	 */
	_formatShareList: function(recipients) {
		var _parent = this;
		recipients = _.toArray(recipients);
		recipients.sort(function(a, b) {
			return a.shareWithDisplayName.localeCompare(b.shareWithDisplayName);
		});
		return $.map(recipients, function(recipient) {
			return _parent._formatRemoteShare(recipient.shareWith, recipient.shareWithDisplayName, t('core', 'Shared with'));
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
		var message, recipients, avatars;
		var ownerId = $tr.attr('data-share-owner-id');
		var owner = $tr.attr('data-share-owner');
		var shareFolderIcon;
		var iconClass = 'icon-shared';
		action.removeClass('shared-style');
		// update folder icon
		if (type === 'dir' && (hasShares || hasLink || ownerId)) {
			if (hasLink) {
				shareFolderIcon = OC.MimeType.getIconUrl('dir-public');
			}
			else {
				shareFolderIcon = OC.MimeType.getIconUrl('dir-shared');
			}
			$tr.find('.filename .thumbnail').css('background-image', 'url(' + shareFolderIcon + ')');
			$tr.attr('data-icon', shareFolderIcon);
		} else if (type === 'dir') {
			var isEncrypted = $tr.attr('data-e2eencrypted');
			var mountType = $tr.attr('data-mounttype');
			// FIXME: duplicate of FileList._createRow logic for external folder,
			// need to refactor the icon logic into a single code path eventually
			if (isEncrypted === 'true') {
				shareFolderIcon = OC.MimeType.getIconUrl('dir-encrypted');
				$tr.attr('data-icon', shareFolderIcon);
			} else if (mountType && mountType.indexOf('external') === 0) {
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
		if (hasShares || ownerId) {
			recipients = $tr.data('share-recipient-data');
			action.addClass('shared-style');

			avatars = '<span>' + t('core', 'Shared') + '</span>';
			// even if reshared, only show "Shared by"
			if (ownerId) {
				message = t('core', 'Shared by');
				avatars = this._formatRemoteShare(ownerId, owner, message);
			} else if (recipients) {
				avatars = this._formatShareList(recipients);
			}
			action.html(avatars).prepend(icon);

			if (ownerId || recipients) {
				var avatarElement = action.find('.avatar');
				avatarElement.each(function () {
					$(this).avatar($(this).data('username'), 32);
				});
				action.find('span[title]').tooltip({placement: 'top'});
			}
		} else {
			action.html('<span class="hidden-visually">' + t('core', 'Shared') + '</span>').prepend(icon);
		}
		if (hasLink) {
			iconClass = 'icon-public';
		}
		icon.removeClass('icon-shared icon-public').addClass(iconClass);
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
