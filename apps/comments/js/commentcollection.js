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
	 * @class OCA.Comments.CommentCollection
	 * @classdesc
	 *
	 * Collection of comments assigned to a file
	 *
	 */
	var CommentCollection = OC.Backbone.Collection.extend(
		/** @lends OCA.Comments.CommentCollection.prototype */ {

		sync: OC.Backbone.davSync,

		model: OCA.Comments.CommentModel,

		_objectType: 'files',
		_objectId: null,

		_endReached: false,
		_limit : 20,

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
				'<oc:filter-comments xmlns:D="DAV:" xmlns:oc="http://owncloud.org/ns">\n' +
				// load one more so we know there is more
				'    <oc:limit>' + (this._limit + 1) + '</oc:limit>\n' +
				'    <oc:offset>' + this.length + '</oc:offset>\n' +
				'</oc:filter-comments>\n';

			options = options || {};
			var success = options.success;
			options = _.extend({
				remove: false,
				data: body,
				davProperties: CommentCollection.prototype.model.prototype.davProperties,
				success: function(resp) {
					if (resp.length <= self._limit) {
						// no new entries, end reached
						self._endReached = true;
					} else {
						// remove last entry, for next page load
						resp = _.initial(resp);
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

	OCA.Comments.CommentCollection = CommentCollection;
})(OC, OCA);

