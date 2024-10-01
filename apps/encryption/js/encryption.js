/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * @namespace
 * @memberOf OC
 */
OC.Encryption = _.extend(OC.Encryption || {}, {
	displayEncryptionWarning: function () {
		if (!OC.currentUser || !OC.Notification.isHidden()) {
			return;
		}

		$.get(
			OC.generateUrl('/apps/encryption/ajax/getStatus'),
			function (result) {
				if (result.status === "interactionNeeded") {
					OC.Notification.show(result.data.message);
				}
			}
		);
	}
});
window.addEventListener('DOMContentLoaded', function() {
	// wait for other apps/extensions to register their event handlers and file actions
	// in the "ready" clause
	_.defer(function() {
		OC.Encryption.displayEncryptionWarning();
	});
});
