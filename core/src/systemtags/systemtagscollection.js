/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* eslint-disable */
(function(OC) {

	function filterFunction(model, term) {
		return model.get('name').substr(0, term.length).toLowerCase() === term.toLowerCase()
	}

	/**
	 * @class OCA.SystemTags.SystemTagsCollection
	 * @classdesc
	 *
	 * Collection of tags assigned to a file
	 *
	 */
	var SystemTagsCollection = OC.Backbone.Collection.extend(
		/** @lends OC.SystemTags.SystemTagsCollection.prototype */ {

			sync: OC.Backbone.davSync,

			model: OC.SystemTags.SystemTagModel,

			url: function() {
				return OC.linkToRemote('dav') + '/systemtags/'
			},

			filterByName: function(name) {
				return this.filter(function(model) {
					return filterFunction(model, name)
				})
			},

			reset: function() {
				this.fetched = false
				return OC.Backbone.Collection.prototype.reset.apply(this, arguments)
			},

			/**
		 * Lazy fetch.
		 * Only fetches once, subsequent calls will directly call the success handler.
		 *
		 * @param {any} options -
		 * @param [options.force] true to force fetch even if cached entries exist
		 *
		 * @see Backbone.Collection#fetch
		 */
			fetch: function(options) {
				var self = this
				options = options || {}
				if (this.fetched || this.working || options.force) {
				// directly call handler
					if (options.success) {
						options.success(this, null, options)
					}
					// trigger sync event
					this.trigger('sync', this, null, options)
					return Promise.resolve()
				}

				this.working = true

				var success = options.success
				options = _.extend({}, options)
				options.success = function() {
					self.fetched = true
					self.working = false
					if (success) {
						return success.apply(this, arguments)
					}
				}

				return OC.Backbone.Collection.prototype.fetch.call(this, options)
			}
		})

	OC.SystemTags = OC.SystemTags || {}
	OC.SystemTags.SystemTagsCollection = SystemTagsCollection

	/**
	 * @type OC.SystemTags.SystemTagsCollection
	 */
	OC.SystemTags.collection = new OC.SystemTags.SystemTagsCollection()
})(OC)
