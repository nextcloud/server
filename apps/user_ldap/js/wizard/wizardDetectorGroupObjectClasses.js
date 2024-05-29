/**
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc discovers object classes for the groups tab
	 *
	 * @constructor
	 */
	var WizardDetectorGroupObjectClasses = OCA.LDAP.Wizard.WizardDetectorFeatureAbstract.subClass({
		/** @inheritdoc */
		init: function() {
			// given, it is not a configuration key
			this.setTargetKey('ldap_groupfilter_objectclass');
			this.wizardMethod = 'determineGroupObjectClasses';
			this.featureName = 'GroupObjectClasses';
			this.runsOnRequest = true;
		}
	});

	OCA.LDAP.Wizard.WizardDetectorGroupObjectClasses = WizardDetectorGroupObjectClasses;
})();
