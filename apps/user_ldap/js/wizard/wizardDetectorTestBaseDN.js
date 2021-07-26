
/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
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
