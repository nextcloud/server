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

	var ShareConfigModel = OC.Backbone.Model.extend({
		defaults: {
			publicUploadEnabled: false,
			enforcePasswordForPublicLink: oc_appconfig.core.enforcePasswordForPublicLink,
			isDefaultExpireDateEnforced: oc_appconfig.core.defaultExpireDateEnforced === true,
			defaultExpireDate: oc_appconfig.core.defaultExpireDate,
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
			return !_.isUndefined(publicUploadEnabled);
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
		isRemoteShareAllowed: function() {
			return oc_appconfig.core.remoteShareAllowed;
		},

		/**
		 * @returns {boolean}
		 */
		isResharingAllowed: function() {
			return oc_appconfig.core.resharingAllowed
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
		}
	});


	OC.Share.ShareConfigModel = ShareConfigModel;
})();
