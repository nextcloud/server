/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc an Attributes Detector. It executes the auto-detection of
	 * available attributes by the Nextcloud server, if requirements are met.
	 *
	 * @constructor
	 */
	var WizardDetectorAvailableAttributes = OCA.LDAP.Wizard.WizardDetectorGeneric.subClass({
		/** @inheritdoc */
		init: function() {
			// given, it is not a configuration key
			this.setTargetKey('ldap_loginfilter_attributes');
			this.runsOnRequest = true;
		},

		/**
		 * runs the detector, if port is not set.
		 *
		 * @param {OCA.LDAP.Wizard.ConfigModel} model
		 * @param {string} configID - the configuration prefix
		 * @returns {boolean|jqXHR}
		 * @abstract
		 */
		run: function(model, configID) {
			model.notifyAboutDetectionStart(this.getTargetKey());
			var params = OC.buildQueryString({
				action: 'determineAttributes',
				ldap_serverconfig_chooser: configID
			});
			return model.callWizard(params, this.processResult, this);
		},

		/**
		 * @inheritdoc
		 */
		processResult: function(model, detector, result) {
			if(result.status === 'success') {
				var payload = {
					feature: 'AvailableAttributes',
					data: result.options[detector.getTargetKey()]
				};
				model.inform(payload);
			}
			this._super(model, detector, result);
		}
	});

	OCA.LDAP.Wizard.WizardDetectorAvailableAttributes = WizardDetectorAvailableAttributes;
})();
