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

	function filterFunction(model, term) {
		return model.get('name').substr(0, term.length) === term;
	}

	/**
	 * @class OCA.Comments.CommentsCollection
	 * @classdesc
	 *
	 * Collection of comments assigned to a file
	 *
	 */
	var CommentsCollection = OC.Backbone.Collection.extend(
		/** @lends OCA.Comments.CommentsCollection.prototype */ {

		sync: OC.Backbone.davSync,

		model: OCA.Comments.CommentModel,

		_objectType: 'files',
		_objectId: null,

		_endReached: false,
		_currentIndex: 0,

		initialize: function(models, options) {
			options = options || {};
			if (options.objectType) {
				this._objectType = options.objectType;
			}
			if (options.objectId) {
				this._objectId = options.objectId;
			}
		},

		url: function() {
			return OC.linkToRemote('dav') + '/comments/' +
				encodeURIComponent(this._objectType) + '/' +
				encodeURIComponent(this._objectId) + '/';
		},

		setObjectId: function(objectId) {
			this._objectId = objectId;
		},

		hasMoreResults: function() {
			return !this._endReached;
		},

		/**
		 * Fetch the next set of results
		 */
		fetchNext: function() {
			if (!this.hasMoreResults()) {
				return null;
			}
			if (this._currentIndex === 0) {
				return this.fetch();
			}
			return this.fetch({remove: false});
		},

		reset: function() {
			this._currentIndex = 0;
			OC.Backbone.Collection.prototype.reset.apply(this, arguments);
		}
	});

	OCA.Comments.CommentsCollection = CommentsCollection;
})(OC, OCA);

