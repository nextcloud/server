
/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc let's the wizard backend count the available users
	 *
	 * @constructor
	 */
	var WizardDetectorUserDisplayNameAttribute = OCA.LDAP.Wizard.WizardDetectorFilterSimpleRequestAbstract.subClass({
		init: function() {
			this.setTargetKey('ldap_user_count');
			this.wizardMethod = 'detectUserDisplayNameAttribute';
			this.runsOnRequest = true;
		},

		/**
		 * @inheritdoc
		 */
		run: function(model, configID) {
			// default value has capital N. Detected values are always lowercase
			if(model.configuration.ldap_display_name && model.configuration.ldap_display_name !== 'displayName') {
				// a value is already set. Don't overwrite and don't ask LDAP
				// without reason.
				return false;
			}
			this._super(model, configID);
		}
	});

	OCA.LDAP.Wizard.WizardDetectorUserDisplayNameAttribute = WizardDetectorUserDisplayNameAttribute;
})();
