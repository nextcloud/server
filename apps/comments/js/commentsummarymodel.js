/*
 * Copyright (c) 2016
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function(OC, OCA) {
	var NS_OWNCLOUD = 'http://owncloud.org/ns';
	/**
	 * @class OCA.Comments.CommentSummaryModel
	 * @classdesc
	 *
	 * Model containing summary information related to comments
	 * like the read marker. 
	 *
	 */
	var CommentSummaryModel = OC.Backbone.Model.extend(
		/** @lends OCA.Comments.CommentSummaryModel.prototype */ {
		sync: OC.Backbone.davSync,

		/**
		 * Object type
		 *
		 * @type string
		 */
		_objectType: 'files',

		/**
		 * Object id
		 *
		 * @type string
		 */
		_objectId: null,

		davProperties: {
			'readMarker': '{' + NS_OWNCLOUD + '}readMarker'
		},

		/**
		 * Initializes the summary model
		 *
		 * @param {string} [options.objectType] object type
		 * @param {string} [options.objectId] object id
		 */
		initialize: function(attrs, options) {
			options = options || {};
			if (options.objectType) {
				this._objectType = options.objectType;
			}
		},

		url: function() {
			return OC.linkToRemote('dav') + '/comments/' +
				encodeURIComponent(this._objectType) + '/' +
				encodeURIComponent(this.id) + '/';
		}
	});

	OCA.Comments.CommentSummaryModel = CommentSummaryModel;
})(OC, OCA);

