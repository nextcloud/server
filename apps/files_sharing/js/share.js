/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {

	_.extend(OC.Files.Client, {
		PROPERTY_SHARE_TYPES:	'{' + OC.Files.Client.NS_OWNCLOUD + '}share-types',
		PROPERTY_OWNER_DISPLAY_NAME:	'{' + OC.Files.Client.NS_OWNCLOUD + '}owner-display-name'
	});

	if (!OCA.Sharing) {
		OCA.Sharing = {};
	}
	/**
	 * @namespace
	 */
	OCA.Sharing.Util = {
		/**
		 * Initialize the sharing plugin.
		 *
		 * Registers the "Share" file action and adds additional
		 * DOM attributes for the sharing file info.
		 *
		 * @param {OCA.Files.FileList} fileList file list to be extended
		 */
		attach: function(fileList) {
			// core sharing is disabled/not loaded
			if (!OC.Share) {
				return;
			}
			if (fileList.id === 'trashbin' || fileList.id === 'files.public') {
				return;
			}
			var fileActions = fileList.fileActions;
			var oldCreateRow = fileList._createRow;
			fileList._createRow = function(fileData) {
				var tr = oldCreateRow.apply(this, arguments);
				var sharePermissions = OCA.Sharing.Util.getSharePermissions(fileData);
				tr.attr('data-share-permissions', sharePermissions);
				if (fileData.shareOwner) {
					tr.attr('data-share-owner', fileData.shareOwner);
					tr.attr('data-share-owner-id', fileData.shareOwnerId);
					// user should always be able to rename a mount point
					if (fileData.mountType === 'shared-root') {
						tr.attr('data-permissions', fileData.permissions | OC.PERMISSION_UPDATE);
					}
				}
				if (fileData.recipientData && !_.isEmpty(fileData.recipientData)) {
					tr.attr('data-share-recipient-data', JSON.stringify(fileData.recipientData));
				}
				if (fileData.shareTypes) {
					tr.attr('data-share-types', fileData.shareTypes.join(','));
				}
				return tr;
			};

			var oldElementToFile = fileList.elementToFile;
			fileList.elementToFile = function($el) {
				var fileInfo = oldElementToFile.apply(this, arguments);
				fileInfo.sharePermissions = $el.attr('data-share-permissions') || undefined;
				fileInfo.shareOwner = $el.attr('data-share-owner') || undefined;

				if( $el.attr('data-share-types')){
					fileInfo.shareTypes = $el.attr('data-share-types').split(',');
				}

				if( $el.attr('data-expiration')){
					var expirationTimestamp = parseInt($el.attr('data-expiration'));
					fileInfo.shares = [];
					fileInfo.shares.push({expiration: expirationTimestamp});
				}

				return fileInfo;
			};

			var oldGetWebdavProperties = fileList._getWebdavProperties;
			fileList._getWebdavProperties = function() {
				var props = oldGetWebdavProperties.apply(this, arguments);
				props.push(OC.Files.Client.PROPERTY_OWNER_DISPLAY_NAME);
				props.push(OC.Files.Client.PROPERTY_SHARE_TYPES);
				return props;
			};

			fileList.filesClient.addFileInfoParser(function(response) {
				var data = {};
				var props = response.propStat[0].properties;
				var permissionsProp = props[OC.Files.Client.PROPERTY_PERMISSIONS];

				if (permissionsProp && permissionsProp.indexOf('S') >= 0) {
					data.shareOwner = props[OC.Files.Client.PROPERTY_OWNER_DISPLAY_NAME];
				}

				var shareTypesProp = props[OC.Files.Client.PROPERTY_SHARE_TYPES];
				if (shareTypesProp) {
					data.shareTypes = _.chain(shareTypesProp).filter(function(xmlvalue) {
						return (xmlvalue.namespaceURI === OC.Files.Client.NS_OWNCLOUD && xmlvalue.nodeName.split(':')[1] === 'share-type');
					}).map(function(xmlvalue) {
						return parseInt(xmlvalue.textContent || xmlvalue.text, 10);
					}).value();
				}

				return data;
			});

			// use delegate to catch the case with multiple file lists
			fileList.$el.on('fileActionsReady', function(ev){
				var $files = ev.$files;

				_.each($files, function(file) {
					var $tr = $(file);
					var shareTypes = $tr.attr('data-share-types') || '';
					var shareOwner = $tr.attr('data-share-owner');
					if (shareTypes || shareOwner) {
						var hasLink = false;
						var hasShares = false;
						_.each(shareTypes.split(',') || [], function(shareType) {
							shareType = parseInt(shareType, 10);
							if (shareType === OC.Share.SHARE_TYPE_LINK) {
								hasLink = true;
							} else if (shareType === OC.Share.SHARE_TYPE_EMAIL) {
								hasLink = true;
							} else if (shareType === OC.Share.SHARE_TYPE_USER) {
								hasShares = true;
							} else if (shareType === OC.Share.SHARE_TYPE_GROUP) {
								hasShares = true;
							} else if (shareType === OC.Share.SHARE_TYPE_REMOTE) {
								hasShares = true;
							} else if (shareType === OC.Share.SHARE_TYPE_CIRCLE) {
								hasShares = true;
							}
						});
						OCA.Sharing.Util._updateFileActionIcon($tr, hasShares, hasLink);
					}
				});
			});


			fileList.$el.on('changeDirectory', function() {
				OCA.Sharing.sharesLoaded = false;
			});

			fileActions.registerAction({
				name: 'Share',
				displayName: '',
				altText: t('core', 'Share'),
				mime: 'all',
				permissions: OC.PERMISSION_ALL,
				iconClass: 'icon-shared',
				type: OCA.Files.FileActions.TYPE_INLINE,
				actionHandler: function(fileName) {
					fileList.showDetailsView(fileName, 'shareTabView');
				},
				render: function(actionSpec, isDefault, context) {
					var permissions = parseInt(context.$file.attr('data-permissions'), 10);
					// if no share permissions but share owner exists, still show the link
					if ((permissions & OC.PERMISSION_SHARE) !== 0 || context.$file.attr('data-share-owner')) {
						return fileActions._defaultRenderAction.call(fileActions, actionSpec, isDefault, context);
					}
					// don't render anything
					return null;
				}
			});

			var shareTab = new OCA.Sharing.ShareTabView('shareTabView', {order: -20});
			// detect changes and change the matching list entry
			shareTab.on('sharesChanged', function(shareModel) {
				var fileInfoModel = shareModel.fileInfoModel;
				var $tr = fileList.findFileEl(fileInfoModel.get('name'));

				// We count email shares as link share
				var hasLinkShare = shareModel.hasLinkShare();
				shareModel.get('shares').forEach(function (share) {
					if (share.share_type === OC.Share.SHARE_TYPE_EMAIL) {
						hasLinkShare = true;
					}
				});

				OCA.Sharing.Util._updateFileListDataAttributes(fileList, $tr, shareModel);
				if (!OCA.Sharing.Util._updateFileActionIcon($tr, shareModel.hasUserShares(), hasLinkShare)) {
					// remove icon, if applicable
					OC.Share.markFileAsShared($tr, false, false);
				}

				// FIXME: this is too convoluted. We need to get rid of the above updates
				// and only ever update the model and let the events take care of rerendering
				fileInfoModel.set({
					shareTypes: shareModel.getShareTypes(),
					// in case markFileAsShared decided to change the icon,
					// we need to modify the model
					// (FIXME: yes, this is hacky)
					icon: $tr.attr('data-icon')
				});
			});
			fileList.registerTabView(shareTab);

			var breadCrumbSharingDetailView = new OCA.Sharing.ShareBreadCrumbView({shareTab: shareTab});
			fileList.registerBreadCrumbDetailView(breadCrumbSharingDetailView);
		},

		/**
		 * Update file list data attributes
		 */
		_updateFileListDataAttributes: function(fileList, $tr, shareModel) {
			// files app current cannot show recipients on load, so we don't update the
			// icon when changed for consistency
			if (fileList.id === 'files') {
				return;
			}
			var recipients = _.pluck(shareModel.get('shares'), 'share_with_displayname');
			// note: we only update the data attribute because updateIcon()
			if (recipients.length) {
				var recipientData = _.mapObject(shareModel.get('shares'), function (share) {
					return {shareWith: share.share_with, shareWithDisplayName: share.share_with_displayname};
				});
				$tr.attr('data-share-recipient-data', JSON.stringify(recipientData));
			}
			else {
				$tr.removeAttr('data-share-recipient-data');
			}
		},

		/**
		 * Update the file action share icon for the given file
		 *
		 * @param $tr file element of the file to update
		 * @param {boolean} hasUserShares true if a user share exists
		 * @param {boolean} hasLinkShare true if a link share exists
		 *
		 * @return {boolean} true if the icon was set, false otherwise
		 */
		_updateFileActionIcon: function($tr, hasUserShares, hasLinkShare) {
			// if the statuses are loaded already, use them for the icon
			// (needed when scrolling to the next page)
			if (hasUserShares || hasLinkShare || $tr.attr('data-share-recipient-data') || $tr.attr('data-share-owner')) {
				OC.Share.markFileAsShared($tr, true, hasLinkShare);
				return true;
			}
			return false;
		},

		/**
		 * @param {Array} fileData
		 * @returns {String}
		 */
		getSharePermissions: function(fileData) {
			var sharePermissions = fileData.permissions;
			if (fileData.mountType && fileData.mountType === "external-root"){
				// for external storages we can't use the permissions of the mountpoint
				// instead we show all permissions and only use the share permissions from the mountpoint to handle resharing
				sharePermissions = sharePermissions | (OC.PERMISSION_ALL & ~OC.PERMISSION_SHARE);
			}
			if (fileData.type === 'file') {
				// files can't be shared with delete permissions
				sharePermissions = sharePermissions & ~OC.PERMISSION_DELETE;

				// create permissions don't mean anything for files
				sharePermissions = sharePermissions & ~OC.PERMISSION_CREATE;
			}
			return sharePermissions;
		}
	};
})();

OC.Plugins.register('OCA.Files.FileList', OCA.Sharing.Util);
