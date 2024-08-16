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
	var WizardDetectorGroupCount = OCA.LDAP.Wizard.WizardDetectorFilterSimpleRequestAbstract.subClass({
		init: function() {
			this.setTargetKey('ldap_group_count');
			this.wizardMethod = 'countGroups';
			this.runsOnRequest = true;
		}
	});

	OCA.LDAP.Wizard.WizardDetectorGroupCount = WizardDetectorGroupCount;
})();
