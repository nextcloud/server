/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
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
			this.updateOptions();
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
		 * the options will be read in again. Should be called after a
		 * configuration switch.
		 */
		updateOptions: function() {
			var options = [];
			this.$select.find('option').each(function() {
				options.push({
						value: $(this).val(),
						normalized: $(this).val().toLowerCase()
					}
				);
			});
			this._options = options;
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
			fity.$select.empty();
			$.each(fity._options, function() {
				if(!filterVal || this.normalized.indexOf(filterVal) > -1) {
					fity.$select.append($('<option>').val(this.value).text(this.value));
				}
			});
			delete(fity.runID);
		}
	});

	OCA.LDAP.Wizard.FilterOnType = FilterOnType;
})();
