/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {
	if(!OC.Share) {
		OC.Share = {};
		OC.Share.Types = {};
	}

	/**
	 * @typedef {object} OC.Share.Types.LinkShareInfo
	 * @property {bool} isLinkShare
	 * @property {string} token
	 * @property {string|null} password
	 * @property {string} link
	 * @property {number} permissions
	 * @property {Date} expiration
	 * @property {number} stime share time
	 */

	/**
	 * @typedef {object} OC.Share.Types.Collection
	 * @property {string} item_type
	 * @property {string} path
	 * @property {string} item_source TODO: verify
	 */

	/**
	 * @typedef {object} OC.Share.Types.Reshare
	 * @property {string} uid_owner
	 * @property {number} share_type
	 * @property {string} share_with
	 * @property {string} displayname_owner
	 * @property {number} permissions
	 */

	/**
	 * @typedef {object} OC.Share.Types.ShareInfo
	 * @property {number} share_type
	 * @property {number} permissions
	 * @property {number} file_source optional
	 * @property {number} item_source
	 * @property {string} token
	 * @property {string} share_with
	 * @property {string} share_with_displayname
	 * @property {string} mail_send
	 * @property {OC.Share.Types.Collection|undefined} collection
	 * @property {Date} expiration optional?
	 * @property {number} stime optional?
	 */

	/**
	 * @typedef {object} OC.Share.Types.ShareItemInfo
	 * @property {OC.Share.Types.Reshare} reshare
	 * @property {OC.Share.Types.ShareInfo[]} shares
	 * @property {OC.Share.Types.LinkShareInfo|undefined} linkShare
	 */

	/**
	 * These properties are sometimes returned by the server as strings instead
	 * of integers, so we need to convert them accordingly...
	 */
	var SHARE_RESPONSE_INT_PROPS = [
		'id', 'file_parent', 'mail_send', 'file_source', 'item_source', 'permissions',
		'storage', 'share_type', 'parent', 'stime'
	];

	/**
	 * @class OCA.Share.ShareItemModel
	 * @classdesc
	 *
	 * Represents the GUI of the share dialogue
	 *
	 * // FIXME: use OC Share API once #17143 is done
	 *
	 * // TODO: this really should be a collection of share item models instead,
	 * where the link share is one of them
	 */
	var ShareItemModel = OC.Backbone.Model.extend({
		initialize: function(attributes, options) {
			if(!_.isUndefined(options.configModel)) {
				this.configModel = options.configModel;
			}
			if(!_.isUndefined(options.fileInfoModel)) {
				/** @type {OC.Files.FileInfo} **/
				this.fileInfoModel = options.fileInfoModel;
			}

			_.bindAll(this, 'addShare');
		},

		defaults: {
			allowPublicUploadStatus: false,
			permissions: 0,
			linkShare: {}
		},

		/**
		 * Saves the current link share information.
		 *
		 * This will trigger an ajax call and refetch the model afterwards.
		 *
		 * TODO: this should be a separate model
		 */
		saveLinkShare: function(attributes, options) {
			var model = this;
			var itemType = this.get('itemType');
			var itemSource = this.get('itemSource');

			// TODO: use backbone's default value mechanism once this is a separate model
			var requiredAttributes = [
				{ name: 'password', defaultValue: '' },
				{ name: 'passwordChanged', defaultValue: false },
				{ name: 'permissions', defaultValue: OC.PERMISSION_READ },
				{ name: 'expiration', defaultValue: this.configModel.getDefaultExpirationDateString() }
			];

			attributes = attributes || {};

			// get attributes from the model and fill in with default values
			_.each(requiredAttributes, function(attribute) {
				// a provided options overrides a present value of the link
				// share. If neither is given, the default value is used.
				if(_.isUndefined(attribute[attribute.name])) {
					attributes[attribute.name] = attribute.defaultValue;
					var currentValue = model.get('linkShare')[attribute.name];
					if(!_.isUndefined(currentValue)) {
						attributes[attribute.name] = currentValue;
					}
				}
			});

			var password = {
				password: attributes.password,
				passwordChanged: attributes.passwordChanged
			};

			OC.Share.share(
				itemType,
				itemSource,
				OC.Share.SHARE_TYPE_LINK,
				password,
				attributes.permissions,
				this.fileInfoModel.get('name'),
				attributes.expiration,
				function(result) {
					if (!result || result.status !== 'success') {
						model.fetch({
							success: function() {
								if (options && _.isFunction(options.success)) {
									options.success(model);
								}
							}
						});
					} else {
						if (options && _.isFunction(options.error)) {
							options.error(model);
						}
					}
				},
				function(result) {
					var msg = t('core', 'Error');
					if (result.data && result.data.message) {
						msg = result.data.message;
					}

					if (options && _.isFunction(options.error)) {
						options.error(model, msg);
					} else {
						OC.dialogs.alert(msg, t('core', 'Error while sharing'));
					}
				}
			);
		},

		removeLinkShare: function() {
			this.removeShare(OC.Share.SHARE_TYPE_LINK, '');
		},

		/**
		 * Sets the public upload flag
		 *
		 * @param {bool} allow whether public upload is allowed
		 */
		setPublicUpload: function(allow) {
			var permissions = OC.PERMISSION_READ;
			if(allow) {
				permissions = OC.PERMISSION_UPDATE | OC.PERMISSION_CREATE | OC.PERMISSION_READ;
			}

			this.get('linkShare').permissions = permissions;
		},

		/**
		 * Sets the expiration date of the public link
		 *
		 * @param {string} expiration expiration date
		 */
		setExpirationDate: function(expiration) {
			this.get('linkShare').expiration = expiration;
		},

		/**
		 * Set password of the public link share
		 *
		 * @param {string} password
		 */
		setPassword: function(password) {
			this.get('linkShare').password = password;
			this.get('linkShare').passwordChanged = true;
		},

		addShare: function(attributes, options) {
			var shareType = attributes.shareType;
			var shareWith = attributes.shareWith;
			var fileName = this.fileInfoModel.get('name');
			options = options || {};

			// Default permissions are Edit (CRUD) and Share
			// Check if these permissions are possible
			var permissions = OC.PERMISSION_READ;
			if (shareType === OC.Share.SHARE_TYPE_REMOTE) {
				permissions = OC.PERMISSION_CREATE | OC.PERMISSION_UPDATE | OC.PERMISSION_READ;
			} else {
				if (this.updatePermissionPossible()) {
					permissions = permissions | OC.PERMISSION_UPDATE;
				}
				if (this.createPermissionPossible()) {
					permissions = permissions | OC.PERMISSION_CREATE;
				}
				if (this.deletePermissionPossible()) {
					permissions = permissions | OC.PERMISSION_DELETE;
				}
				if (this.configModel.get('isResharingAllowed') && (this.sharePermissionPossible())) {
					permissions = permissions | OC.PERMISSION_SHARE;
				}
			}

			var model = this;
			var itemType = this.get('itemType');
			var itemSource = this.get('itemSource');
			OC.Share.share(itemType, itemSource, shareType, shareWith, permissions, fileName, options.expiration, function() {
				model.fetch();
			});
		},

		setPermissions: function(shareType, shareWith, permissions) {
			var itemType = this.get('itemType');
			var itemSource = this.get('itemSource');

			// TODO: in the future, only set the permissions on the model but don't save directly
			OC.Share.setPermissions(itemType, itemSource, shareType, shareWith, permissions);
		},

		removeShare: function(shareType, shareWith) {
			var model = this;
			var itemType = this.get('itemType');
			var itemSource = this.get('itemSource');

			OC.Share.unshare(itemType, itemSource, shareType, shareWith, function() {
				model.fetch();
			});
		},

		/**
		 * @returns {boolean}
		 */
		isPublicUploadAllowed: function() {
			return this.get('allowPublicUploadStatus');
		},

		/**
		 * @returns {boolean}
		 */
		isFolder: function() {
			return this.get('itemType') === 'folder';
		},

		/**
		 * @returns {boolean}
		 */
		isFile: function() {
			return this.get('itemType') === 'file';
		},

		/**
		 * whether this item has reshare information
		 * @returns {boolean}
		 */
		hasReshare: function() {
			var reshare = this.get('reshare');
			return _.isObject(reshare) && !_.isUndefined(reshare.uid_owner);
		},

		/**
		 * whether this item has user share information
		 * @returns {boolean}
		 */
		hasUserShares: function() {
			return this.getSharesWithCurrentItem().length > 0;
		},

		/**
		 * Returns whether this item has a link share
		 *
		 * @return {bool} true if a link share exists, false otherwise
		 */
		hasLinkShare: function() {
			var linkShare = this.get('linkShare');
			if (linkShare && linkShare.isLinkShare) {
				return true;
			}
			return false;
		},

		/**
		 * @param {number} shareIndex
		 * @returns {string}
		 */
		getCollectionType: function(shareIndex) {
			/** @type OC.Share.Types.ShareInfo **/
			var share = this.get('shares')[shareIndex];
			if(!_.isObject(share)) {
				throw "Unknown Share";
			} else if(_.isUndefined(share.collection)) {
				throw "Share is not a collection";
			}

			return share.collection.item_type;
		},

		/**
		 * @param {number} shareIndex
		 * @returns {string}
		 */
		getCollectionPath: function(shareIndex) {
			/** @type OC.Share.Types.ShareInfo **/
			var share = this.get('shares')[shareIndex];
			if(!_.isObject(share)) {
				throw "Unknown Share";
			} else if(_.isUndefined(share.collection)) {
				throw "Share is not a collection";
			}

			return share.collection.path;
		},

		/**
		 * @param {number} shareIndex
		 * @returns {string}
		 */
		getCollectionSource: function(shareIndex) {
			/** @type OC.Share.Types.ShareInfo **/
			var share = this.get('shares')[shareIndex];
			if(!_.isObject(share)) {
				throw "Unknown Share";
			} else if(_.isUndefined(share.collection)) {
				throw "Share is not a collection";
			}

			return share.collection.item_source;
		},

		/**
		 * @param {number} shareIndex
		 * @returns {boolean}
		 */
		isCollection: function(shareIndex) {
			/** @type OC.Share.Types.ShareInfo **/
			var share = this.get('shares')[shareIndex];
			if(!_.isObject(share)) {
				throw "Unknown Share";
			}
			if(_.isUndefined(share.collection)) {
				return false;
			}
			return true;
		},


		/**
		 * @returns {string}
		 */
		getReshareOwner: function() {
			return this.get('reshare').uid_owner;
		},

		/**
		 * @returns {string}
		 */
		getReshareOwnerDisplayname: function() {
			return this.get('reshare').displayname_owner;
		},

		/**
		 * @returns {string}
		 */
		getReshareWith: function() {
			return this.get('reshare').share_with;
		},

		/**
		 * @returns {number}
		 */
		getReshareType: function() {
			return this.get('reshare').share_type;
		},

		/**
		 * Returns all share entries that only apply to the current item
		 * (file/folder)
		 *
		 * @return {Array.<OC.Share.Types.ShareInfo>}
		 */
		getSharesWithCurrentItem: function() {
			var shares = this.get('shares') || [];
			var fileId = this.fileInfoModel.get('id');
			return _.filter(shares, function(share) {
				return share.item_source === fileId;
			});
		},

		/**
		 * @param shareIndex
		 * @returns {string}
		 */
		getShareWith: function(shareIndex) {
			/** @type OC.Share.Types.ShareInfo **/
			var share = this.get('shares')[shareIndex];
			if(!_.isObject(share)) {
				throw "Unknown Share";
			}
			return share.share_with;
		},

		/**
		 * @param shareIndex
		 * @returns {string}
		 */
		getShareWithDisplayName: function(shareIndex) {
			/** @type OC.Share.Types.ShareInfo **/
			var share = this.get('shares')[shareIndex];
			if(!_.isObject(share)) {
				throw "Unknown Share";
			}
			return share.share_with_displayname;
		},

		getShareType: function(shareIndex) {
			/** @type OC.Share.Types.ShareInfo **/
			var share = this.get('shares')[shareIndex];
			if(!_.isObject(share)) {
				throw "Unknown Share";
			}
			return share.share_type;
		},

		/**
		 * whether a share from shares has the requested permission
		 *
		 * @param {number} shareIndex
		 * @param {number} permission
		 * @returns {boolean}
		 * @private
		 */
		_shareHasPermission: function(shareIndex, permission) {
			/** @type OC.Share.Types.ShareInfo **/
			var share = this.get('shares')[shareIndex];
			if(!_.isObject(share)) {
				throw "Unknown Share";
			}
			if(   share.share_type === OC.Share.SHARE_TYPE_REMOTE
			   && (   permission === OC.PERMISSION_SHARE
				   || permission === OC.PERMISSION_DELETE))
			{
				return false;
			}
			return (share.permissions & permission) === permission;
		},

		notificationMailWasSent: function(shareIndex) {
			/** @type OC.Share.Types.ShareInfo **/
			var share = this.get('shares')[shareIndex];
			if(!_.isObject(share)) {
				throw "Unknown Share";
			}
			return share.mail_send === 1;
		},

		/**
		 * Sends an email notification for the given share
		 *
		 * @param {int} shareType share type
		 * @param {string} shareWith recipient
		 * @param {bool} state whether to set the notification flag or remove it
		 */
		sendNotificationForShare: function(shareType, shareWith, state) {
			var itemType = this.get('itemType');
			var itemSource = this.get('itemSource');

			return $.post(
				OC.generateUrl('core/ajax/share.php'),
				{
					action: state ? 'informRecipients' : 'informRecipientsDisabled',
					recipient: shareWith,
					shareType: shareType,
					itemSource: itemSource,
					itemType: itemType
				},
				function(result) {
					if (result.status !== 'success') {
						// FIXME: a model should not show dialogs
						OC.dialogs.alert(t('core', result.data.message), t('core', 'Warning'));
					}
				}
			);
		},

		/**
		 * Send the link share information by email
		 *
		 * @param {string} recipientEmail recipient email address
		 */
		sendEmailPrivateLink: function(recipientEmail) {
			var deferred = $.Deferred();
			var itemType = this.get('itemType');
			var itemSource = this.get('itemSource');
			var linkShare = this.get('linkShare');

			$.post(
				OC.generateUrl('core/ajax/share.php'), {
					action: 'email',
					toaddress: recipientEmail,
					link: linkShare.link,
					itemType: itemType,
					itemSource: itemSource,
					file: this.fileInfoModel.get('name'),
					expiration: linkShare.expiration || ''
				},
				function(result) {
					if (!result || result.status !== 'success') {
						// FIXME: a model should not show dialogs
						OC.dialogs.alert(result.data.message, t('core', 'Error while sending notification'));
						deferred.reject();
					} else {
						deferred.resolve();
					}
			});

			return deferred.promise();
		},

		/**
		 * @returns {boolean}
		 */
		sharePermissionPossible: function() {
			return (this.get('permissions') & OC.PERMISSION_SHARE) === OC.PERMISSION_SHARE;
		},

		/**
		 * @param {number} shareIndex
		 * @returns {boolean}
		 */
		hasSharePermission: function(shareIndex) {
			return this._shareHasPermission(shareIndex, OC.PERMISSION_SHARE);
		},

		/**
		 * @returns {boolean}
		 */
		createPermissionPossible: function() {
			return (this.get('permissions') & OC.PERMISSION_CREATE) === OC.PERMISSION_CREATE;
		},

		/**
		 * @param {number} shareIndex
		 * @returns {boolean}
		 */
		hasCreatePermission: function(shareIndex) {
			return this._shareHasPermission(shareIndex, OC.PERMISSION_CREATE);
		},

		/**
		 * @returns {boolean}
		 */
		updatePermissionPossible: function() {
			return (this.get('permissions') & OC.PERMISSION_UPDATE) === OC.PERMISSION_UPDATE;
		},

		/**
		 * @param {number} shareIndex
		 * @returns {boolean}
		 */
		hasUpdatePermission: function(shareIndex) {
			return this._shareHasPermission(shareIndex, OC.PERMISSION_UPDATE);
		},

		/**
		 * @returns {boolean}
		 */
		deletePermissionPossible: function() {
			return (this.get('permissions') & OC.PERMISSION_DELETE) === OC.PERMISSION_DELETE;
		},

		/**
		 * @param {number} shareIndex
		 * @returns {boolean}
		 */
		hasDeletePermission: function(shareIndex) {
			return this._shareHasPermission(shareIndex, OC.PERMISSION_DELETE);
		},

		/**
		 * @returns {boolean}
		 */
		editPermissionPossible: function() {
			return    this.createPermissionPossible()
				   || this.updatePermissionPossible()
				   || this.deletePermissionPossible();
		},

		/**
		 * @returns {boolean}
		 */
		hasEditPermission: function(shareIndex) {
			return    this.hasCreatePermission(shareIndex)
				   || this.hasUpdatePermission(shareIndex)
				   || this.hasDeletePermission(shareIndex);
		},

		fetch: function() {
			var model = this;
			this.trigger('request', this);
			OC.Share.loadItem(this.get('itemType'), this.get('itemSource'), function(data) {
				model.trigger('sync', 'GET', this);
				model.set(model.parse(data));
			});
		},

		/**
		 * Updates OC.Share.itemShares and OC.Share.statuses.
		 *
		 * This is required in case the user navigates away and comes back,
		 * the share statuses from the old arrays are still used to fill in the icons
		 * in the file list.
		 */
		_legacyFillCurrentShares: function(shares) {
			var fileId = this.fileInfoModel.get('id');
			if (!shares || !shares.length) {
				delete OC.Share.statuses[fileId];
				OC.Share.currentShares = {};
				OC.Share.itemShares = [];
				return;
			}

			var currentShareStatus = OC.Share.statuses[fileId];
			if (!currentShareStatus) {
				currentShareStatus = {link: false};
				OC.Share.statuses[fileId] = currentShareStatus;
			}
			currentShareStatus.link = false;

			OC.Share.currentShares = {};
			OC.Share.itemShares = [];
			_.each(shares,
				/**
				 * @param {OC.Share.Types.ShareInfo} share
				 */
				function(share) {
					if (share.share_type === OC.Share.SHARE_TYPE_LINK) {
						OC.Share.itemShares[share.share_type] = true;
						currentShareStatus.link = true;
					} else {
						if (!OC.Share.itemShares[share.share_type]) {
							OC.Share.itemShares[share.share_type] = [];
						}
						OC.Share.itemShares[share.share_type].push(share.share_with);
					}
				}
			);
		},

		parse: function(data) {
			if(data === false) {
				console.warn('no data was returned');
				trigger('fetchError');
				return {};
			}

			var permissions = this.get('possiblePermissions');
			if(!_.isUndefined(data.reshare) && !_.isUndefined(data.reshare.permissions) && data.reshare.uid_owner !== OC.currentUser) {
				permissions = permissions & data.reshare.permissions;
			}

			var allowPublicUploadStatus = false;
			if(!_.isUndefined(data.shares)) {
				$.each(data.shares, function (key, value) {
					if (value.share_type === OC.Share.SHARE_TYPE_LINK) {
						allowPublicUploadStatus = (value.permissions & OC.PERMISSION_CREATE) ? true : false;
						return true;
					}
				});
			}

			/** @type {OC.Share.Types.ShareInfo[]} **/
			var shares = _.map(data.shares, function(share) {
				// properly parse some values because sometimes the server
				// returns integers as string...
				var i;
				for (i = 0; i < SHARE_RESPONSE_INT_PROPS.length; i++) {
					var prop = SHARE_RESPONSE_INT_PROPS[i];
					if (!_.isUndefined(share[prop])) {
						share[prop] = parseInt(share[prop], 10);
					}
				}
				return share;
			});

			this._legacyFillCurrentShares(shares);

			var linkShare = { isLinkShare: false };
			// filter out the share by link
			shares = _.reject(shares,
				/**
				 * @param {OC.Share.Types.ShareInfo} share
				 */
				function(share) {
					var isShareLink =
						share.share_type === OC.Share.SHARE_TYPE_LINK
						&& (   share.file_source === this.get('itemSource')
						|| share.item_source === this.get('itemSource'));

					if (isShareLink) {
						var link = window.location.protocol + '//' + window.location.host;
						if (!share.token) {
							// pre-token link
							var fullPath = this.fileInfoModel.get('path') + '/' +
								this.fileInfoModel.get('name');
							var location = '/' + OC.currentUser + '/files' + fullPath;
							var type = this.fileInfoModel.isDirectory() ? 'folder' : 'file';
							link += OC.linkTo('', 'public.php') + '?service=files&' +
								type + '=' + encodeURIComponent(location);
						} else {
							link += OC.generateUrl('/s/') + share.token;
						}
						linkShare = {
							isLinkShare: true,
							token: share.token,
							password: share.share_with,
							link: link,
							permissions: share.permissions,
							// currently expiration is only effective for link shares.
							expiration: share.expiration,
							stime: share.stime
						};

						return share;
					}
				},
				this
			);

			return {
				reshare: data.reshare,
				shares: shares,
				linkShare: linkShare,
				permissions: permissions,
				allowPublicUploadStatus: allowPublicUploadStatus
			};
		},

		/**
		 * Parses a string to an valid integer (unix timestamp)
		 * @param time
		 * @returns {*}
		 * @internal Only used to work around a bug in the backend
		 */
		_parseTime: function(time) {
			if (_.isString(time)) {
				// skip empty strings and hex values
				if (time === '' || (time.length > 1 && time[0] === '0' && time[1] === 'x')) {
					return null;
				}
				time = parseInt(time, 10);
				if(isNaN(time)) {
					time = null;
				}
			}
			return time;
		}
	});

	OC.Share.ShareItemModel = ShareItemModel;
})();
