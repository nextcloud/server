/**
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc detects groups for the users tab
	 *
	 * @constructor
	 */
	var WizardDetectorGroupsForUsers = OCA.LDAP.Wizard.WizardDetectorFeatureAbstract.subClass({
		/** @inheritdoc */
		init: function() {
			// given, it is not a configuration key
			this.setTargetKey('ldap_userfilter_groups');
			this.wizardMethod = 'determineGroupsForUsers';
			this.featureName = 'GroupsForUsers';
			this.runsOnRequest = true;
		}
	});

	OCA.LDAP.Wizard.WizardDetectorGroupsForUsers = WizardDetectorGroupsForUsers;
})();
