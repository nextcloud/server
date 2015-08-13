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
	 * @property {string} share_mail_send
	 * @property {bool} collection //TODO: verify
	 * @property {Date} expiration optional?
	 * @property {number} stime optional?
	 */

	/**
	 * @typedef {object} OC.Share.Types.ShareItemInfo
	 * @property {OC.Share.Types.Reshare} reshare
	 * @property {OC.Share.Types.ShareInfo[]} shares
	 */

	/**
	 * @class OCA.Share.ShareItemModel
	 * @classdesc
	 *
	 * Represents the GUI of the share dialogue
	 *
	 * // FIXME: use OC Share API once #17143 is done
	 */
	var ShareItemModel = OC.Backbone.Model.extend({
		initialize: function(attributes, options) {
			if(!_.isUndefined(options.configModel)) {
				this.configModel = options.configModel;
			}
			this.fetch();
		},

		defaults: {
			allowPublicUploadStatus: false
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
			return _.isObject(this.get('reshare')) && !_.isUndefined(this.get('reshare').uid_owner);
		},

		/**
		 * whether this item has reshare information
		 * @returns {boolean}
		 */
		hasShares: function() {
			return _.isObject(this.get('shares'));
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
		 * @returns {number}
		 */
		getPermissions: function() {
			var permissions = this.get('permissions');
			if(_.isUndefined(permissions)) {
				// model was not properly initialized
				console.warn('Sharing error: undefined permissions');
				permissions = 0;
			}
			return permissions;
		},

		/**
		 * @returns {boolean}
		 */
		hasSharePermission: function() {
			return (this.getPermissions() & OC.PERMISSION_SHARE) === OC.PERMISSION_SHARE;
		},

		/**
		 * @returns {boolean}
		 */
		hasCreatePermission: function() {
			return (this.getPermissions() & OC.PERMISSION_CREATE) === OC.PERMISSION_CREATE;
		},

		fetch: function() {
			var model = this;
			OC.Share.loadItem(this.get('itemType'), this.get('itemSource'), function(data) {
				model.set(model.parse(data));
			});
		},

		parse: function(data) {
			if(data === false) {
				console.warn('no data was returned');
				trigger('fetchError');
				return {};
			}

			var permissions = this.get('possiblePermissions');
			if(!_.isUndefined(data.reshare) && !_.isUndefined(data.reshare.permissions)) {
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

			var attributes = {
				reshare: data.reshare,
				shares: data.shares,
				permissions: permissions,
				allowPublicUploadStatus: allowPublicUploadStatus
			};

			return attributes;
		}
	});

	OC.Share.ShareItemModel = ShareItemModel;
})();
