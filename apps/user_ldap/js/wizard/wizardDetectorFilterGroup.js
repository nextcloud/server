
/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc a Port Detector. It executes the auto-detection of the port
	 * by the ownCloud server, if requirements are met.
	 *
	 * @constructor
	 */
	var WizardDetectorFilterGroup = OCA.LDAP.Wizard.WizardDetectorFilterSimpleRequestAbstract.subClass({
		init: function() {
			this.setTrigger([
				'ldap_groupfilter_groups',
				'ldap_groupfilter_objectclass'
			]);
			this.setTargetKey('ldap_group_filter');

			this.wizardMethod = 'getGroupFilter';
		}
	});

	OCA.LDAP.Wizard.WizardDetectorFilterGroup = WizardDetectorFilterGroup;
})();
