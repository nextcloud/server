
/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc checks whether the provided log in name can be resolved into
	 * a DN using the current login filter
	 *
	 * @constructor
	 */
	var WizardDetectorTestLoginName = OCA.LDAP.Wizard.WizardDetectorTestAbstract.subClass({
		/** @inheritdoc */
		init: function() {
			// given, it is not a configuration key
			this.setTargetKey('ldap_test_loginname');
			this.testName = 'TestLoginName';
			this.wizardMethod = 'testLoginName';
			this.runsOnRequest = true;
		}
	});

	OCA.LDAP.Wizard.WizardDetectorTestLoginName = WizardDetectorTestLoginName;
})();
