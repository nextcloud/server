
/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc discovers object classes for the groups tab
	 *
	 * @constructor
	 */
	var WizardDetectorGroupObjectClasses = OCA.LDAP.Wizard.WizardDetectorFeatureAbstract.subClass({
		/** @inheritdoc */
		init: function() {
			// given, it is not a configuration key
			this.setTargetKey('ldap_groupfilter_objectclass');
			this.wizardMethod = 'determineGroupObjectClasses';
			this.featureName = 'GroupObjectClasses';
			this.runsOnRequest = true;
		}
	});

	OCA.LDAP.Wizard.WizardDetectorGroupObjectClasses = WizardDetectorGroupObjectClasses;
})();
