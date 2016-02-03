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
	 * @class OCA.Comments.CommentModel
	 * @classdesc
	 *
	 * Comment
	 *
	 */
	var CommentModel = OC.Backbone.Model.extend(
		/** @lends OCA.Comments.CommentModel.prototype */ {
		sync: OC.Backbone.davSync,

		defaults: {
			actorType: 'users',
			objectType: 'files'
		},

		davProperties: {
			'id': '{' + NS_OWNCLOUD + '}id',
			'message': '{' + NS_OWNCLOUD + '}message',
			'actorType': '{' + NS_OWNCLOUD + '}actorType',
			'actorId': '{' + NS_OWNCLOUD + '}actorId',
			'actorDisplayName': '{' + NS_OWNCLOUD + '}actorDisplayName',
			'creationDateTime': '{' + NS_OWNCLOUD + '}creationDateTime',
			'objectType': '{' + NS_OWNCLOUD + '}objectType',
			'objectId': '{' + NS_OWNCLOUD + '}objectId',
			'isUnread': '{' + NS_OWNCLOUD + '}isUnread'
		},

		parse: function(data) {
			data.isUnread = (data.isUnread === 'true');
			return data;
		}
	});

	OCA.Comments.CommentModel = CommentModel;
})(OC, OCA);

