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
			publicUploadEnabled: false
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
		isDefaultExpireDateEnforced: function() {
			return oc_appconfig.core.defaultExpireDateEnforced === true;
		},

		/**
		 * @returns {number}
		 */
		getDefaultExpireDate: function () {
			return oc_appconfig.core.defaultExpireDate;
		}
	});


	OC.Share.ShareConfigModel = ShareConfigModel;
})();
