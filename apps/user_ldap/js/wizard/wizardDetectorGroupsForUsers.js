
/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
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
