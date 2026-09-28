/**
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
