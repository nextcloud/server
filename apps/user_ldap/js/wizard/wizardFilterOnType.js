/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc filters a select box when a text element is typed in
	 */
	var FilterOnType = OCA.LDAP.Wizard.WizardObject.subClass({
		/**
		 * initializes a type filter on a text input for a select element
		 *
		 * @param {jQuery} $select
		 * @param {jQuery} $textInput
		 */
		init: function($select, $textInput) {
			this.$select = $select;
			this.$textInput = $textInput;
			this.lastSearch = '';

			var fity = this;
			$textInput.bind('change keyup', function () {
				if(fity.runID) {
					window.clearTimeout(fity.runID);
				}
				fity.runID = window.setTimeout(function() {
					fity.filter(fity);
				}, 250);
			});
		},

		/**
		 * the actual search or filter method
		 *
		 * @param {FilterOnType} fity
		 */
		filter: function(fity) {
			var filterVal = fity.$textInput.val().toLowerCase();
			if(filterVal === fity.lastSearch) {
				return;
			}
			fity.lastSearch = filterVal;

			fity.$select.find('option').each(function() {
				if(!filterVal || $(this).val().toLowerCase().indexOf(filterVal) > -1) {
					$(this).removeAttr('hidden')
				} else {
					$(this).attr('hidden', 'hidden');
				}
			});
			delete(fity.runID);
		}
	});

	OCA.LDAP.Wizard.FilterOnType = FilterOnType;
})();
