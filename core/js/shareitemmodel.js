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
	 * @property {string} token
	 * @property {bool} hideDownload
	 * @property {string|null} password
	 * @property {bool} sendPasswordByTalk
	 * @property {number} permissions
	 * @property {Date} expiration
	 * @property {number} stime share time
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
	 * @property {string} share_with_avatar
	 * @property {string} mail_send
	 * @property {Date} expiration optional?
	 * @property {number} stime optional?
	 * @property {string} uid_owner
	 * @property {string} displayname_owner
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
		/**
		 * share id of the link share, if applicable
		 */
		_linkShareId: null,

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
			linkShares: []
		},

		/**
		 * Saves the current link share information.
		 *
		 * This will trigger an ajax call and, if successful, refetch the model
		 * afterwards. Callbacks "success", "error" and "complete" can be given
		 * in the options object; "success" is called after a successful save
		 * once the model is refetch, "error" is called after a failed save, and
		 * "complete" is called both after a successful save and after a failed
		 * save. Note that "complete" is called before "success" and "error" are
		 * called (unlike in jQuery, in which it is called after them); this
		 * ensures that "complete" is called even if refetching the model fails.
		 *
		 * TODO: this should be a separate model
		 */
		saveLinkShare: function(attributes, options) {
			options = options || {};
			attributes = _.extend({}, attributes);

			var shareId = null;
			var call;

			// oh yeah...
			if (attributes.expiration) {
				attributes.expireDate = attributes.expiration;
				delete attributes.expiration;
			}

			var linkShares = this.get('linkShares');
			var shareIndex = _.findIndex(linkShares, function(share) {return share.id === attributes.cid})

			if (linkShares.length > 0 && shareIndex !== -1) {
				shareId = linkShares[shareIndex].id;

				// note: update can only update a single value at a time
				call = this.updateShare(shareId, attributes, options);
			} else {
				attributes = _.defaults(attributes, {
					hideDownload: false,
					password: '',
					passwordChanged: false,
					sendPasswordByTalk: false,
					permissions: OC.PERMISSION_READ,
					expireDate: this.configModel.getDefaultExpirationDateString(),
					shareType: OC.Share.SHARE_TYPE_LINK
				});

				call = this.addShare(attributes, options);
			}

			return call;
		},

		addShare: function(attributes, options) {
			var shareType = attributes.shareType;
			attributes = _.extend({}, attributes);

			// get default permissions
			var defaultPermissions = OC.getCapabilities()['files_sharing']['default_permissions'] || OC.PERMISSION_ALL;
			var possiblePermissions = OC.PERMISSION_READ;

			if (this.updatePermissionPossible()) {
				possiblePermissions = possiblePermissions | OC.PERMISSION_UPDATE;
			}
			if (this.createPermissionPossible()) {
				possiblePermissions = possiblePermissions | OC.PERMISSION_CREATE;
			}
			if (this.deletePermissionPossible()) {
				possiblePermissions = possiblePermissions | OC.PERMISSION_DELETE;
			}
			if (this.configModel.get('isResharingAllowed') && (this.sharePermissionPossible())) {
				possiblePermissions = possiblePermissions | OC.PERMISSION_SHARE;
			}

			attributes.permissions = defaultPermissions & possiblePermissions;
			if (_.isUndefined(attributes.path)) {
				attributes.path = this.fileInfoModel.getFullPath();
			}

			return this._addOrUpdateShare({
				type: 'POST',
				url: this._getUrl('shares'),
				data: attributes,
				dataType: 'json'
			}, options);
		},

		updateShare: function(shareId, attrs, options) {
			return this._addOrUpdateShare({
				type: 'PUT',
				url: this._getUrl('shares/' + encodeURIComponent(shareId)),
				data: attrs,
				dataType: 'json'
			}, options);
		},

		_addOrUpdateShare: function(ajaxSettings, options) {
			var self = this;
			options = options || {};

			return $.ajax(
				ajaxSettings
			).always(function() {
				if (_.isFunction(options.complete)) {
					options.complete(self);
				}
			}).done(function() {
				self.fetch().done(function() {
					if (_.isFunction(options.success)) {
						options.success(self);
					}
				});
			}).fail(function(xhr) {
				var msg = t('core', 'Error');
				var result = xhr.responseJSON;
				if (result && result.ocs && result.ocs.meta) {
					msg = result.ocs.meta.message;
				}

				if (_.isFunction(options.error)) {
					options.error(self, msg);
				} else {
					OC.dialogs.alert(msg, t('core', 'Error while sharing'));
				}
			});
		},

		/**
		 * Deletes the share with the given id
		 *
		 * @param {int} shareId share id
		 * @return {jQuery}
		 */
		removeShare: function(shareId, options) {
			var self = this;
			options = options || {};
			return $.ajax({
				type: 'DELETE',
				url: this._getUrl('shares/' + encodeURIComponent(shareId)),
			}).done(function() {
				self.fetch({
					success: function() {
						if (_.isFunction(options.success)) {
							options.success(self);
						}
					}
				});
			}).fail(function(xhr) {
				var msg = t('core', 'Error');
				var result = xhr.responseJSON;
				if (result.ocs && result.ocs.meta) {
					msg = result.ocs.meta.message;
				}

				if (_.isFunction(options.error)) {
					options.error(self, msg);
				} else {
					OC.dialogs.alert(msg, t('core', 'Error removing share'));
				}
			});
		},

		/**
		 * @returns {boolean}
		 */
		isPublicUploadAllowed: function() {
			return this.get('allowPublicUploadStatus');
		},

		isPublicEditingAllowed: function() {
			return this.get('allowPublicEditingStatus');
		},

		/**
		 * @returns {boolean}
		 */
		isHideFileListSet: function() {
			return this.get('hideFileListStatus');
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
		 * Returns whether this item has link shares
		 *
		 * @return {bool} true if a link share exists, false otherwise
		 */
		hasLinkShares: function() {
			var linkShares = this.get('linkShares');
			if (linkShares && linkShares.length > 0) {
				return true;
			}
			return false;
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
		getReshareNote: function() {
			return this.get('reshare').note;
		},

		/**
		 * @returns {string}
		 */
		getReshareWith: function() {
			return this.get('reshare').share_with;
		},

		/**
		 * @returns {string}
		 */
		getReshareWithDisplayName: function() {
			var reshare = this.get('reshare');
			return reshare.share_with_displayname || reshare.share_with;
		},

		/**
		 * @returns {number}
		 */
		getReshareType: function() {
			return this.get('reshare').share_type;
		},

		getExpireDate: function(shareIndex) {
			return this._shareExpireDate(shareIndex);
		},

		getNote: function(shareIndex) {
			return this._shareNote(shareIndex);
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


		/**
		 * @param shareIndex
		 * @returns {string}
		 */
		getShareWithAvatar: function(shareIndex) {
			/** @type OC.Share.Types.ShareInfo **/
			var share = this.get('shares')[shareIndex];
			if(!_.isObject(share)) {
				throw "Unknown Share";
			}
			return share.share_with_avatar;
		},

		/**
		 * @param shareIndex
		 * @returns {string}
		 */
		getSharedBy: function(shareIndex) {
			/** @type OC.Share.Types.ShareInfo **/
			var share = this.get('shares')[shareIndex];
			if(!_.isObject(share)) {
				throw "Unknown Share";
			}
			return share.uid_owner;
		},

		/**
		 * @param shareIndex
		 * @returns {string}
		 */
		getSharedByDisplayName: function(shareIndex) {
			/** @type OC.Share.Types.ShareInfo **/
			var share = this.get('shares')[shareIndex];
			if(!_.isObject(share)) {
				throw "Unknown Share";
			}
			return share.displayname_owner;
		},

		/**
		 * @param shareIndex
		 * @returns {string}
		 */
		getFileOwnerUid: function(shareIndex) {
			/** @type OC.Share.Types.ShareInfo **/
			var share = this.get('shares')[shareIndex];
			if(!_.isObject(share)) {
				throw "Unknown Share";
			}
			return share.uid_file_owner;
		},

		/**
		 * returns the array index of a sharee for a provided shareId
		 *
		 * @param shareId
		 * @returns {number}
		 */
		findShareWithIndex: function(shareId) {
			var shares = this.get('shares');
			if(!_.isArray(shares)) {
				throw "Unknown Share";
			}
			for(var i = 0; i < shares.length; i++) {
				var shareWith = shares[i];
				if(shareWith.id === shareId) {
					return i;
				}
			}
			throw "Unknown Sharee";
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
			return (share.permissions & permission) === permission;
		},


		_shareExpireDate: function(shareIndex) {
			var share = this.get('shares')[shareIndex];
			if(!_.isObject(share)) {
				throw "Unknown Share";
			}
			var date2 = share.expiration;
			return date2;
		},


		_shareNote: function(shareIndex) {
			var share = this.get('shares')[shareIndex];
			if(!_.isObject(share)) {
				throw "Unknown Share";
			}
			return share.note;
		},

		/**
		 * @return {int}
		 */
		getPermissions: function() {
			return this.get('permissions');
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

		hasReadPermission: function(shareIndex) {
			return this._shareHasPermission(shareIndex, OC.PERMISSION_READ);
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
		 * @returns {string}
		 *     The state that the 'can edit' permission checkbox should have.
		 *     Possible values:
		 *     - empty string: no permission
		 *     - 'checked': all applicable permissions
		 *     - 'indeterminate': some but not all permissions
		 */
		editPermissionState: function(shareIndex) {
			var hcp = this.hasCreatePermission(shareIndex);
			var hup = this.hasUpdatePermission(shareIndex);
			var hdp = this.hasDeletePermission(shareIndex);
			if (this.isFile()) {
				if (hcp || hup || hdp) {
					return 'checked';
				}
				return '';
			}
			if (!hcp && !hup && !hdp) {
				return '';
			}
			if (   (this.createPermissionPossible() && !hcp)
				|| (this.updatePermissionPossible() && !hup)
				|| (this.deletePermissionPossible() && !hdp)   ) {
				return 'indeterminate';
			}
			return 'checked';
		},

		/**
		 * @returns {int}
		 */
		linkSharePermissions: function(shareId) {
			var linkShares = this.get('linkShares');
			var shareIndex = _.findIndex(linkShares, function(share) {return share.id === shareId})

			if (!this.hasLinkShares()) {
				return -1;
			} else if (linkShares.length > 0 && shareIndex !== -1) {
				return linkShares[shareIndex].permissions;
			}
			return -1;
		},

		_getUrl: function(base, params) {
			params = _.extend({format: 'json'}, params || {});
			return OC.linkToOCS('apps/files_sharing/api/v1', 2) + base + '?' + OC.buildQueryString(params);
		},

		_fetchShares: function() {
			var path = this.fileInfoModel.getFullPath();
			return $.ajax({
				type: 'GET',
				url: this._getUrl('shares', {path: path, reshares: true})
			});
		},

		_fetchReshare: function() {
			// only fetch original share once
			if (!this._reshareFetched) {
				var path = this.fileInfoModel.getFullPath();
				this._reshareFetched = true;
				return $.ajax({
					type: 'GET',
					url: this._getUrl('shares', {path: path, shared_with_me: true})
				});
			} else {
				return $.Deferred().resolve([{
					ocs: {
						data: [this.get('reshare')]
					}
				}]);
			}
		},

		/**
		 * Group reshares into a single super share element.
		 * Does this by finding the most precise share and
		 * combines the permissions to be the most permissive.
		 *
		 * @param {Array} reshares
		 * @return {Object} reshare
		 */
		_groupReshares: function(reshares) {
			if (!reshares || !reshares.length) {
				return false;
			}

			var superShare = reshares.shift();
			var combinedPermissions = superShare.permissions;
			_.each(reshares, function(reshare) {
				// use share have higher priority than group share
				if (reshare.share_type === OC.Share.SHARE_TYPE_USER && superShare.share_type === OC.Share.SHARE_TYPE_GROUP) {
					superShare = reshare;
				}
				combinedPermissions |= reshare.permissions;
			});

			superShare.permissions = combinedPermissions;
			return superShare;
		},

		fetch: function(options) {
			var model = this;
			this.trigger('request', this);

			var deferred = $.when(
				this._fetchShares(),
				this._fetchReshare()
			);
			deferred.done(function(data1, data2) {
				model.trigger('sync', 'GET', this);
				var sharesMap = {};
				_.each(data1[0].ocs.data, function(shareItem) {
					sharesMap[shareItem.id] = shareItem;
				});

				var reshare = false;
				if (data2[0].ocs.data.length) {
					reshare = model._groupReshares(data2[0].ocs.data);
				}

				model.set(model.parse({
					shares: sharesMap,
					reshare: reshare
				}));

				if(!_.isUndefined(options) && _.isFunction(options.success)) {
					options.success();
				}
			});

			return deferred;
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
				this.trigger('fetchError');
				return {};
			}

			var permissions = this.fileInfoModel.get('permissions');
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

			var allowPublicEditingStatus = true;
			if(!_.isUndefined(data.shares)) {
				$.each(data.shares, function (key, value) {
					if (value.share_type === OC.Share.SHARE_TYPE_LINK) {
						allowPublicEditingStatus = (value.permissions & OC.PERMISSION_UPDATE) ? true : false;
						return true;
					}
				});
			}


			var hideFileListStatus = false;
			if(!_.isUndefined(data.shares)) {
				$.each(data.shares, function (key, value) {
					if (value.share_type === OC.Share.SHARE_TYPE_LINK) {
						hideFileListStatus = (value.permissions & OC.PERMISSION_READ) ? false : true;
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

			var linkShares =  [];
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
						/**
						 * Ignore reshared link shares for now
						 * FIXME: Find a way to display properly
						 */
						if (share.uid_owner !== OC.currentUser) {
							return;
						}

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
						linkShares.push(_.extend({}, share, {
							// hide_download is returned as an int, so force it
							// to a boolean
							hideDownload: !!share.hide_download,
							password: share.share_with,
							sendPasswordByTalk: share.send_password_by_talk
						}));

						return share;
					}
				},
				this
			);

			return {
				reshare: data.reshare,
				shares: shares,
				linkShares: linkShares,
				permissions: permissions,
				allowPublicUploadStatus: allowPublicUploadStatus,
				allowPublicEditingStatus: allowPublicEditingStatus,
				hideFileListStatus: hideFileListStatus
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
		},

		/**
		 * Returns a list of share types from the existing shares.
		 *
		 * @return {Array.<int>} array of share types
		 */
		getShareTypes: function() {
			var result;
			result = _.pluck(this.getSharesWithCurrentItem(), 'share_type');
			if (this.hasLinkShares()) {
				result.push(OC.Share.SHARE_TYPE_LINK);
			}
			return _.uniq(result);
		}
	});

	OC.Share.ShareItemModel = ShareItemModel;
})();
