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
		_limit : 5,

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

		reset: function() {
			this._endReached = false;
			return OC.Backbone.Collection.prototype.reset.apply(this, arguments);
		},

		/**
		 * Fetch the next set of results
		 */
		fetchNext: function(options) {
			var self = this;
			if (!this.hasMoreResults()) {
				return null;
			}

			var body = '<?xml version="1.0" encoding="utf-8" ?>\n' +
				'<D:report xmlns:D="DAV:" xmlns:oc="http://owncloud.org/ns">\n' +
				'   <oc:limit>' + this._limit + '</oc:limit>\n';

			if (this.length > 0) {
				body += '   <oc:datetime>' + this.first().get('creationDateTime') + '</oc:datetime>\n';
			}

			body += '</D:report>\n';

			var oldLength = this.length;

			options = options || {};
			var success = options.success;
			options = _.extend({
				remove: false,
				data: body,
				davProperties: CommentsCollection.prototype.model.prototype.davProperties,
				success: function(resp) {
					if (resp.length === oldLength) {
						// no new entries, end reached
						self._endReached = true;
					}
					if (!self.set(resp, options)) {
						return false;
					}
					if (success) {
						success.apply(null, arguments);
					}
					self.trigger('sync', 'REPORT', self, options);
				}
			}, options);

			return this.sync('REPORT', this, options);
		}
	});

	OCA.Comments.CommentsCollection = CommentsCollection;
})(OC, OCA);

