/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc a Port Detector. It executes the auto-detection of the port
	 * by the Nextcloud server, if requirements are met.
	 *
	 * @constructor
	 */
	var WizardDetectorFilterUser = OCA.LDAP.Wizard.WizardDetectorFilterSimpleRequestAbstract.subClass({
		init: function() {
			this.setTrigger([
				'ldap_userfilter_groups',
				'ldap_userfilter_objectclass'
			]);
			this.setTargetKey('ldap_userlist_filter');
			this.runsOnRequest = true;

			this.wizardMethod = 'getUserListFilter';
		}
	});

	OCA.LDAP.Wizard.WizardDetectorFilterUser = WizardDetectorFilterUser;
})();
