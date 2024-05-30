/**
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc let's the wizard backend count the available users
	 *
	 * @constructor
	 */
	var WizardDetectorUserCount = OCA.LDAP.Wizard.WizardDetectorFilterSimpleRequestAbstract.subClass({
		init: function() {
			this.setTargetKey('ldap_user_count');
			this.wizardMethod = 'countUsers';
			this.runsOnRequest = true;
		}
	});

	OCA.LDAP.Wizard.WizardDetectorUserCount = WizardDetectorUserCount;
})();
