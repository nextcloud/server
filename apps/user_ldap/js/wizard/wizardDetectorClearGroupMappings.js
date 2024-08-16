/**
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc requests clearing of user mappings
	 *
	 * @constructor
	 */
	var WizardDetectorClearGroupMappings = OCA.LDAP.Wizard.WizardDetectorTestAbstract.subClass({
		/** @inheritdoc */
		init: function() {
			// given, it is not a configuration key
			this.setTargetKey('ldap_action_clear_group_mappings');
			this.testName = 'ClearMappings';
			this.isLegacy = true;
			this.legacyDestination = 'clearMappings.php';
			this.runsOnRequest = true;
		}
	});

	OCA.LDAP.Wizard.WizardDetectorClearGroupMappings = WizardDetectorClearGroupMappings;
})();
