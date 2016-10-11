/**
 * Copyright (c) 2014
 *  Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
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
$(document).ready(function() {
	// wait for other apps/extensions to register their event handlers and file actions
	// in the "ready" clause
	_.defer(function() {
		OC.Encryption.displayEncryptionWarning();
	});
});
