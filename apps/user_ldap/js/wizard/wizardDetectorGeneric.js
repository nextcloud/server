
/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCA = OCA || {};

(function() {
	/**
	 * @classdesc a generic (abstract) Detector template. A Detector's task is
	 * to kick off server side detection of certain LDAP features. It is invoked
	 * when changes to specified configuration keys happen.
	 *
	 * @constructor
	 */
	var WizardDetectorGeneric = OCA.LDAP.Wizard.WizardObject.subClass({
		/**
		 * initializes the instance. Always call it after creating the instance.
		 */
		init: function() {
			this.setTrigger([]);
			this.targetKey = '';
			this.runsOnRequest = false;
		},

		/**
		 * sets the configuration keys the detector is listening on
		 *
		 * @param {string[]} triggers
		 */
		setTrigger: function(triggers) {
			this.triggers = triggers;
		},

		/**
		 * tests whether the detector is triggered by the provided key
		 *
		 * @param {string} key
		 * @returns {boolean}
		 */
		triggersOn: function(key) {
			return ($.inArray(key, this.triggers) >= 0);
		},

		/**
		 * whether the detector runs on explicit request
		 *
		 * @param {string} key
		 * @returns {boolean}
		 */
		runsOnFeatureRequest: function(key) {
			return !!(this.runsOnRequest && this.targetKey === key);
		},

		/**
		 * sets the configuration key the detector is attempting to auto-detect
		 *
		 * @param {string} key
		 */
		setTargetKey: function(key) {
			this.targetKey = key;
		},

		/**
		 * returns the configuration key the detector is attempting to
		 * auto-detect
		 */
		getTargetKey: function() {
			return this.targetKey;
		},

		/**
		 * runs the detector. This method is supposed to be implemented by the
		 * concrete detector.
		 *
		 * Must return false if the detector decides not to run.
		 * Must return a jqXHR object otherwise, which is provided by the
		 * model's callWizard()
		 *
		 * @param {OCA.LDAP.Wizard.ConfigModel} model
		 * @param {string} configID - the configuration prefix
		 * @returns {boolean|jqXHR}
		 * @abstract
		 */
		run: function(model, configID) {
			// to be implemented by subClass
			return false;
		},

		/**
		 * processes the result of the Nextcloud server
		 *
		 * @param {OCA.LDAP.Wizard.ConfigModel} model
		 * @param {WizardDetectorGeneric} detector
		 * @param {object} result
		 */
		processResult: function(model, detector, result) {
			model['notifyAboutDetectionCompletion'](detector.getTargetKey());
			if(result.status === 'success') {
				for (var id in result.changes) {
					// update and not set method, as values are already stored
					model['update'](id, result.changes[id]);
				}
			} else {
				var payload = { relatedKey: detector.targetKey };
				if(!_.isUndefined(result.message)) {
					payload.message = result.message;
				}
				model.gotServerError(payload);
			}
		}
	});

	OCA.LDAP.Wizard.WizardDetectorGeneric = WizardDetectorGeneric;
})();
