
/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
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
