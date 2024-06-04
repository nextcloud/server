/**
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc detects groups for the groups tab
	 *
	 * @constructor
	 */
	var WizardDetectorGroupsForGroups = OCA.LDAP.Wizard.WizardDetectorFeatureAbstract.subClass({
		/** @inheritdoc */
		init: function() {
			// given, it is not a configuration key
			this.setTargetKey('ldap_groupfilter_groups');
			this.wizardMethod = 'determineGroupsForGroups';
			this.featureName = 'GroupsForGroups';
			this.runsOnRequest = true;
		}
	});

	OCA.LDAP.Wizard.WizardDetectorGroupsForGroups = WizardDetectorGroupsForGroups;
})();
