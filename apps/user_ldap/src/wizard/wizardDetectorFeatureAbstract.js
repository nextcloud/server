
/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc abstract detector for detecting groups and object classes
	 *
	 * @constructor
	 */
	var WizardDetectorFeatureAbstract = OCA.LDAP.Wizard.WizardDetectorGeneric.subClass({
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
				action: this.wizardMethod,
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
					feature: detector.featureName,
					data: result.options[detector.getTargetKey()]
				};
				model.inform(payload);
			}

			this._super(model, detector, result);
		}
	});

	OCA.LDAP.Wizard.WizardDetectorFeatureAbstract = WizardDetectorFeatureAbstract;
})();
