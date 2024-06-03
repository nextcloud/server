/* global OC */

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

(function() {
	'use strict';

	var errorNotification;

	/**
	 * Model for storing and saving user settings
	 *
	 * @class UserSettings
	 */
	var UserSettings = OC.Backbone.Model.extend({
		url: OC.generateUrl('/settings/users/{id}/settings', {id: OC.currentUser}),
		isNew: function() {
			return false; // Force PUT on .save()
		},
		parse: function(data) {
			if (_.isUndefined(data)) {
				return null;
			}

			if (errorNotification) {
				errorNotification.hide();
			}

			if (data.status && data.status === 'error') {
				errorNotification = OC.Notification.show(data.data.message, { type: 'error' });
			}

			if (_.isUndefined(data.data)) {
				return null;
			}
			data = data.data;

			var ignored = [
				'userId',
				'message'
			];

			_.each(ignored, function(ign) {
				if (!_.isUndefined(data[ign])) {
					delete data[ign];
				}
			});

			return data;
		}
	});

	OC.Settings = OC.Settings || {};

	OC.Settings.UserSettings = UserSettings;
})();
