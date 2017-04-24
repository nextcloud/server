/**
 * Copyright (c) 2017 Georg Ehrke <oc.list@georgehrke.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

(function ($) {
	var ENTRY = ''
		+ '<li>'
		+ '    <a href="{{hyperlink}}">'
		+ '        {{#if icon}}<img src="{{icon}}">{{/if}}'
		+ '        <span>{{title}}</span>'
		+ '    </a>'
		+ '</li>';

	$.fn.contactsMenu = function(shareWith, shareType, appendTo) {
		if (typeof(shareWith) !== 'undefined') {
			shareWith = String(shareWith);
		} else {
			if (typeof(this.data('share-with')) !== 'undefined') {
				shareWith = this.data('share-with');
			}
		}
		if (typeof(shareType) !== 'undefined') {
			shareType = Number(shareType);
		} else {
			if (typeof(this.data('share-type')) !== 'undefined') {
				shareType = this.data('share-type');
			}
		}
		if (typeof(appendTo) === 'undefined') {
			appendTo = this;
		}

		// 0 - user, 4 - email, 6 - remote
		var allowedTypes = [0, 4, 6];
		if (allowedTypes.indexOf(shareType) === -1) {
			return;
		}

		var $div = this;
		appendTo.append('<div class="menu popovermenu bubble hidden contactsmenu-popover"><ul><li><a><span class="icon-loading-small"></span></a></li></ul></div>');
		var $list = appendTo.find('div.contactsmenu-popover');
		var url = OC.generateUrl('/contactsmenu/findOne');

		$div.click(function() {
			$list.show();

			if ($list.hasClass('loaded')) {
				return;
			}

			$list.addClass('loaded');
			$.ajax(url, {
				method: 'POST',
				data: {
					shareType: shareType,
					shareWith: shareWith
				}
			}).then(function(data) {
				$list.find('ul').find('li').addClass('hidden');

				var actions;
				if (!data.topAction) {
					actions = [{
						hyperlink: '#',
						title: t('core', 'No action available')
					}];
				} else {
					actions = [data.topAction].concat(data.actions);
				}

				actions.forEach(function(action) {
					var template = Handlebars.compile(ENTRY);
					$list.find('ul').append(template(action));
				});

				if (actions.length === 0) {

				}
			});
		});

		$(document).click(function(event) {
			var clickedList = $.contains($list, event.target);
			var clickedLi = $.contains($div, event.target);

			$div.each(function() {
				if ($(this).is(event.target)) {
					clickedLi = true;
				}
			});

			if (clickedList) {
				return;
			}

			if (clickedLi) {
				return;
			}

			$list.hide();

		});
	};
}(jQuery));
