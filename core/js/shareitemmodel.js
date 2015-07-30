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
	 */
	var ShareItemModel = function(itemType, itemSource) {
		this.initialize(itemType, itemSource);
	};

	/**
	 * @memberof OCA.Sharing
	 */
	ShareItemModel.prototype = {
		/** @var {string} **/
		_itemType: null,
		/** @var {mixed} **/	//TODO: what type?
		_itemSource: null,

		/** @var {OC.Share.Types.Reshare} **/
		_reshare: null,

		/** @var {OC.Share.Types.ShareInfo[]} **/
		_shares: null,

		initialize: function(itemType, itemSource) {
			this._itemType = itemType;
			this._itemSource = itemSource;
			this._retrieveData();
		},

		hasReshare: function() {
			return _.isObject(this._reshare) && !_.isUndefined(this._reshare.uid_owner);
		},

		getReshareOwner: function() {
			return this._reshare.uid_owner;
		},

		getReshareOwnerDisplayname: function() {
			return this._reshare.displayname_owner;
		},

		getReshareWith: function() {
			return this._reshare.share_with;
		},

		getReshareType: function() {
			return this._reshare.share_type;
		},

		_retrieveData: function() {
			/** var {OC.Share.Types.ShareItemInfo} **/
			var data = OC.Share.loadItem(this._itemType, this._itemSource);
			if(data === false) {
				console.warn('no data was returned');
				return;
			}
			this._reshare = data.reshare;
			this._shares = data.shares;

		}
	};

	OC.Share.ShareItemModel = ShareItemModel;
})();
