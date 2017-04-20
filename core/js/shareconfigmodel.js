/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global moment, oc_appconfig, oc_config */

(function() {
	if (!OC.Share) {
		OC.Share = {};
		OC.Share.Types = {};
	}

	// FIXME: the config model should populate its own model attributes based on
	// the old DOM-based config
	var ShareConfigModel = OC.Backbone.Model.extend({
		defaults: {
			publicUploadEnabled: false,
			enforcePasswordForPublicLink: oc_appconfig.core.enforcePasswordForPublicLink,
			enableLinkPasswordByDefault: oc_appconfig.core.enableLinkPasswordByDefault,
			isDefaultExpireDateEnforced: oc_appconfig.core.defaultExpireDateEnforced === true,
			isDefaultExpireDateEnabled: oc_appconfig.core.defaultExpireDateEnabled === true,
			isRemoteShareAllowed: oc_appconfig.core.remoteShareAllowed,
			isMailShareAllowed: oc_appconfig.shareByMailEnabled !== undefined,
			defaultExpireDate: oc_appconfig.core.defaultExpireDate,
			isResharingAllowed: oc_appconfig.core.resharingAllowed,
			isPasswordForMailSharesRequired: (oc_appconfig.shareByMail === undefined) ? false : oc_appconfig.shareByMail.enforcePasswordProtection,
			allowGroupSharing: oc_appconfig.core.allowGroupSharing
		},

		/**
		 * @returns {boolean}
		 * @deprecated here for legacy reasons - will always return true
		 */
		areAvatarsEnabled: function() {
			return true;
		},

		/**
		 * @returns {boolean}
		 */
		isPublicUploadEnabled: function() {
			var publicUploadEnabled = $('#filestable').data('allow-public-upload');
			return publicUploadEnabled === 'yes';
		},

		/**
		 * @returns {boolean}
		 */
		isShareWithLinkAllowed: function() {
			return $('#allowShareWithLink').val() === 'yes';
		},

		/**
		 * @returns {string}
		 */
		getFederatedShareDocLink: function() {
			return oc_appconfig.core.federatedCloudShareDoc;
		},

		getDefaultExpirationDateString: function () {
			var expireDateString = '';
			if (this.get('isDefaultExpireDateEnabled')) {
				var date = moment.utc();
				var expireAfterDays = this.get('defaultExpireDate');
				date.add(expireAfterDays, 'days');
				expireDateString = date.format('YYYY-MM-DD 00:00:00');
			}
			return expireDateString;
		}
	});


	OC.Share.ShareConfigModel = ShareConfigModel;
})();
