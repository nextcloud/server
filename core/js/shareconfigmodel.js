/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global moment, OC */

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
			enforcePasswordForPublicLink: OC.appConfig.core.enforcePasswordForPublicLink,
			enableLinkPasswordByDefault: OC.appConfig.core.enableLinkPasswordByDefault,
			isDefaultExpireDateEnforced: OC.appConfig.core.defaultExpireDateEnforced === true,
			isDefaultExpireDateEnabled: OC.appConfig.core.defaultExpireDateEnabled === true,
			isRemoteShareAllowed: OC.appConfig.core.remoteShareAllowed,
			isMailShareAllowed: OC.appConfig.shareByMailEnabled !== undefined,
			defaultExpireDate: OC.appConfig.core.defaultExpireDate,
			isResharingAllowed: OC.appConfig.core.resharingAllowed,
			isPasswordForMailSharesRequired: (OC.appConfig.shareByMail === undefined) ? false : OC.appConfig.shareByMail.enforcePasswordProtection,
			allowGroupSharing: OC.appConfig.core.allowGroupSharing
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
			return OC.appConfig.core.federatedCloudShareDoc;
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
