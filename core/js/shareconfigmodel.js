/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

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
			isDefaultExpireDateEnforced: oc_appconfig.core.defaultExpireDateEnforced === true,
			isDefaultExpireDateEnabled: oc_appconfig.core.defaultExpireDateEnabled === true,
			isRemoteShareAllowed: oc_appconfig.core.remoteShareAllowed,
			defaultExpireDate: oc_appconfig.core.defaultExpireDate,
			isResharingAllowed: oc_appconfig.core.resharingAllowed
		},

		/**
		 * @returns {boolean}
		 */
		areAvatarsEnabled: function() {
			return oc_config.enable_avatars === true;
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
		isMailPublicNotificationEnabled: function() {
			return $('input:hidden[name=mailPublicNotificationEnabled]').val() === 'yes';
		},

		/**
		 * @returns {boolean}
		 */
		isMailNotificationEnabled: function() {
			return $('input:hidden[name=mailNotificationEnabled]').val() === 'yes';
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
				var date = new Date().getTime();
				var expireAfterMs = this.get('defaultExpireDate') * 24 * 60 * 60 * 1000;
				var expireDate = new Date(date + expireAfterMs);
				var month = expireDate.getMonth() + 1;
				var year = expireDate.getFullYear();
				var day = expireDate.getDate();
				expireDateString = year + "-" + month + '-' + day + ' 00:00:00';
			}
			return expireDateString;
		}
	});


	OC.Share.ShareConfigModel = ShareConfigModel;
})();
