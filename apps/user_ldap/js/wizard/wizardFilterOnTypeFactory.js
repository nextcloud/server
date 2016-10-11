/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
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
