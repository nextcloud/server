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
	var WizardDetectorFilterLogin = OCA.LDAP.Wizard.WizardDetectorFilterSimpleRequestAbstract.subClass({
		init: function() {
			this.setTrigger([
				'ldap_loginfilter_username',
				'ldap_loginfilter_email',
				'ldap_loginfilter_attributes'
			]);
			this.setTargetKey('ldap_login_filter');
			this.runsOnRequest = true;

			this.wizardMethod = 'getUserLoginFilter';
		}
	});

	OCA.LDAP.Wizard.WizardDetectorFilterLogin = WizardDetectorFilterLogin;
})();
