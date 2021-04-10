
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
	var WizardDetectorFilterSimpleRequestAbstract = OCA.LDAP.Wizard.WizardDetectorGeneric.subClass({
		runsOnRequest: true,

		/**
		 * runs the detector, if port is not set.
		 *
		 * @param {OCA.LDAP.Wizard.ConfigModel} model
		 * @param {string} configID - the configuration prefix
		 * @returns {boolean|jqXHR}
		 * @abstract
		 */
		run: function(model, configID) {
			if(_.isUndefined(this.wizardMethod)) {
				console.warn('wizardMethod not set! ' + this.constructor);
				return false;
			}
			model.notifyAboutDetectionStart(this.targetKey);
			var params = OC.buildQueryString({
				action: this.wizardMethod,
				ldap_serverconfig_chooser: configID
			});
			return model.callWizard(params, this.processResult, this);
		}
	});

	OCA.LDAP.Wizard.WizardDetectorFilterSimpleRequestAbstract = WizardDetectorFilterSimpleRequestAbstract;
})();
