
/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc a Port Detector. It executes the auto-detection of the port
	 * by the Nextcloud server, if requirements are met.
	 *
	 * @constructor
	 */
	var WizardDetectorPort = OCA.LDAP.Wizard.WizardDetectorGeneric.subClass({
		/** @inheritdoc */
		init: function() {
			this.setTargetKey('ldap_port');
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
			model.notifyAboutDetectionStart('ldap_port');
			model.notifyAboutDetectionStart('ldap_tls');
			var params = OC.buildQueryString({
				action: 'guessPortAndTLS',
				ldap_serverconfig_chooser: configID
			});
			return model.callWizard(params, this.processResultPort.bind(this), this);
		},

		/**
		 * processes the results and sends an extra signal
		 * about completion of the 'ldap_tls' item.
		 *
		 * @param {OCA.LDAP.Wizard.ConfigModel} model
		 * @param {WizardDetectorGeneric} detector
		 * @param {object} result
		 */
		processResultPort: function(model, detector, result) {
			this.processResult(model, detector, result);
			model['notifyAboutDetectionCompletion']('ldap_tls');
		}
	});

	OCA.LDAP.Wizard.WizardDetectorPort = WizardDetectorPort;
})();
