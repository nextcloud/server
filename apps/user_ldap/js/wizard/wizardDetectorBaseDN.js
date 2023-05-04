
/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc a Base DN Detector. It executes the auto-detection of the base
	 * DN by the Nextcloud server, if requirements are met.
	 *
	 * @constructor
	 */
	var WizardDetectorBaseDN = OCA.LDAP.Wizard.WizardDetectorGeneric.subClass({
		/** @inheritdoc */
		init: function() {
			this.setTargetKey('ldap_base');
			this.runsOnRequest = true;
		},

		/**
		 * runs the detector, if specified configuration settings are set and
		 * base DN is not set.
		 *
		 * @param {OCA.LDAP.Wizard.ConfigModel} model
		 * @param {string} configID - the configuration prefix
		 * @returns {boolean|jqXHR}
		 * @abstract
		 */
		run: function(model, configID) {
			if(    !model.configuration['ldap_host']
				|| !model.configuration['ldap_port']

				)
			{
				return false;
			}
			model.notifyAboutDetectionStart(this.getTargetKey());
			var params = OC.buildQueryString({
				action: 'guessBaseDN',
				ldap_serverconfig_chooser: configID
			});
			return model.callWizard(params, this.processResult, this);
		}
	});

	OCA.LDAP.Wizard.WizardDetectorBaseDN = WizardDetectorBaseDN;
})();
