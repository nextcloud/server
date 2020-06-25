
/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc discovers object classes for the users tab
	 *
	 * @constructor
	 */
	var WizardDetectorUserObjectClasses = OCA.LDAP.Wizard.WizardDetectorFeatureAbstract.subClass({
		/** @inheritdoc */
		init: function() {
			// given, it is not a configuration key
			this.setTargetKey('ldap_userfilter_objectclass');
			this.wizardMethod = 'determineUserObjectClasses';
			this.featureName = 'UserObjectClasses';
			this.runsOnRequest = true;
		}
	});

	OCA.LDAP.Wizard.WizardDetectorUserObjectClasses = WizardDetectorUserObjectClasses;
})();
