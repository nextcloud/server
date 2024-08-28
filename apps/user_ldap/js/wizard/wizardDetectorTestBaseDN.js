/**
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc Tests, how many objects reside in the given base DN(s)
	 *
	 * @constructor
	 */
	var WizardDetectorTestBaseDN = OCA.LDAP.Wizard.WizardDetectorTestAbstract.subClass({
		/** @inheritdoc */
		init: function() {
			// given, it is not a configuration key
			this.setTargetKey('ldap_test_base');
			this.testName = 'TestBaseDN';
			this.wizardMethod = 'countInBaseDN';
			this.runsOnRequest = true;
		}
	});

	OCA.LDAP.Wizard.WizardDetectorTestBaseDN = WizardDetectorTestBaseDN;
})();
