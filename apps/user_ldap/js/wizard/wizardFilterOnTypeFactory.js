/**
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc creates instances of OCA.LDAP.Wizard.FilterOnType upon request
	 */
	var FilterOnTypeFactory = OCA.LDAP.Wizard.WizardObject.subClass({
		/**
		 * initializes a type filter on a text input for a select element
		 *
		 * @param {jQuery} $select
		 * @param {jQuery} $textInput
		 */
		get: function($select, $textInput) {
			return new OCA.LDAP.Wizard.FilterOnType($select, $textInput);
		}
	});

	OCA.LDAP.Wizard.FilterOnTypeFactory = FilterOnTypeFactory;
})();
