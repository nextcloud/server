/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global Select2 */

/**
 * Select2 extension for toggling values in a multi-select dropdown
 *
 * Inspired by http://stackoverflow.com/a/27466159 and adjusted
 */
(function(Select2) {

	var Select2FindHighlightableChoices = Select2.class.multi.prototype.findHighlightableChoices;
	Select2.class.multi.prototype.findHighlightableChoices = function () {
		if (this.opts.toggleSelect) {
			return this.results.find('.select2-result-selectable:not(.select2-disabled)');
		}
		return Select2FindHighlightableChoices.apply(this, arguments);
	};

	var Select2TriggerSelect = Select2.class.multi.prototype.triggerSelect;
	Select2.class.multi.prototype.triggerSelect = function (data) {
		if (this.opts.toggleSelect && this.val().indexOf(this.id(data)) !== -1) {

			var val = this.id(data);
			var evt = $.Event('select2-removing');
			evt.val = val;
			evt.choice = data;
			this.opts.element.trigger(evt);

			if (evt.isDefaultPrevented()) {
				return false;
			}

			var self = this;
			this.results.find('.select2-result.select2-selected').each(function () {
				var $this = $(this);
				if (self.id($this.data('select2-data')) === val) {
					$this.removeClass('select2-selected');
				}
			});

			this.opts.element.trigger({ type: "select2-removed", val: this.id(data), choice: data });
			this.triggerChange({ removed: data });

		} else {
			return Select2TriggerSelect.apply(this, arguments);
		}
	};

})(Select2);

