(function ($) {
	$.fn.singleSelect = function () {
		return this.each(function (i, select) {
			var input = $('<input/>'),
				gravity = $(select).attr('data-tipsy-gravity'),
				inputTooltip = $(select).attr('data-inputtitle');
			if (inputTooltip){
				input.attr('title', inputTooltip);
			}
			if (typeof gravity === 'undefined') {
				gravity = 'n'
			}
			select = $(select);
			input.css('position', 'absolute');
			input.css({
				'box-sizing': 'border-box',
				'-moz-box-sizing': 'border-box',
				'margin': 0,
				'width': (select.width() - 5) + 'px',
				'height': (select.outerHeight() - 2) + 'px',
				'border': 'none',
				'box-shadow': 'none',
				'margin-top': '1px',
				'margin-left': '1px',
				'z-index': 1000
			});
			input.hide();
			$('body').append(input);

			select.on('change', function (event) {
				var value = $(this).val(),
					newAttr = $('option:selected', $(this)).attr('data-new');
				if (!(typeof newAttr !== 'undefined' && newAttr !== false)) {
					input.hide();
					select.data('previous', value);
				} else {
					event.stopImmediatePropagation();
					// adjust offset, in case the user scrolled
					input.css(select.offset());
					input.show();
					if ($.fn.tipsy){
						input.tipsy({gravity: gravity, trigger: 'manual'});
						input.tipsy('show');
					}
					select.css('background-color', 'white');
					input.focus();
				}
			});

			$(select).data('previous', $(select).val());

			input.on('change', function () {
				var value = $(this).val();
				if (value) {
					select.children().attr('selected', null);
					var existingOption = select.children().filter(function (i, option) {
						return ($(option).val() == value);
					});
					if (existingOption.length) {
						existingOption.attr('selected', 'selected');
					} else {
						var option = $('<option/>');
						option.attr('selected', 'selected').attr('value', value).text(value);
						select.children().last().before(option);
					}
					select.val(value);
					select.css('background-color', null);
					input.val(null);
					input.hide();
					select.change();
				} else {
					var previous = select.data('previous');
					select.children().attr('selected', null);
					select.children().each(function (i, option) {
						if ($(option).val() == previous) {
							$(option).attr('selected', 'selected');
						}
					});
					select.removeClass('active');
					input.hide();
				}
			});

			input.on('blur', function () {
				$(this).change();
				if ($.fn.tipsy){
					$(this).tipsy('hide');
				}
			});
			input.click(function(ev) {
				// prevent clicks to close any container
				ev.stopPropagation();
			});
		});
	};
})(jQuery);
