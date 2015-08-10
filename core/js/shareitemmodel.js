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

	// FIXME: migration is to Backbone.Model still WIP, only pushing for the night.

	/**
	 * @memberof OCA.Sharing
	 */
	var ShareItemModel = OC.Backbone.Model.extend({
		initialize: function() {
			this._retrieveData(); // TODO I need to get my head around fetch() respectively sync() and url(). Left for later, it's late.
		},

		hasReshare: function() {
			return _.isObject(this.get('reshare')) && !_.isUndefined(this.get('reshare').uid_owner);
		},

		getReshareOwner: function() {
			return this.get('reshare').uid_owner;
		},

		getReshareOwnerDisplayname: function() {
			return this.get('reshare').displayname_owner;
		},

		getReshareWith: function() {
			return this.get('reshare').share_with;
		},

		getReshareType: function() {
			return this.get('reshare').share_type;
		},

		_retrieveData: function() {
			/** var {OC.Share.Types.ShareItemInfo} **/
			var data = OC.Share.loadItem(this.get('itemType'), this.get('itemSource'));
			if(data === false) {
				console.warn('no data was returned');
				return;
			}
			var attributes = {
				reshare: data.reshare,
				shares: data.shares
			};
			this.set(attributes);
		}
	});

	OC.Share.ShareItemModel = ShareItemModel;
})();
