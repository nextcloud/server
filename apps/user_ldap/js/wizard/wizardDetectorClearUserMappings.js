
/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc requests clearing of user mappings
	 *
	 * @constructor
	 */
	var WizardDetectorClearUserMappings = OCA.LDAP.Wizard.WizardDetectorTestAbstract.subClass({
		/** @inheritdoc */
		init: function() {
			// given, it is not a configuration key
			this.setTargetKey('ldap_action_clear_user_mappings');
			this.testName = 'ClearMappings';
			this.isLegacy = true;
			this.legacyDestination = 'clearMappings.php';
			this.runsOnRequest = true;
		}
	});

	OCA.LDAP.Wizard.WizardDetectorClearUserMappings = WizardDetectorClearUserMappings;
})();
