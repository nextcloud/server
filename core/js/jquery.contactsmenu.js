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

	var LIST = ''
		+ '<div class="menu popovermenu bubble hidden contactsmenu-popover">'
		+ '    <ul>'
		+ '        <li>'
		+ '            <a>'
		+ '                <span class="icon-loading-small"></span>'
		+ '            </a>'
		+ '        </li>'
		+ '    </ul>'
		+ '</div>';

	$.fn.contactsMenu = function(shareWith, shareType, appendTo) {
		// 0 - user, 4 - email, 6 - remote
		var allowedTypes = [0, 4, 6];
		if (allowedTypes.indexOf(shareType) === -1) {
			return;
		}

		var $div = this;
		appendTo.append(LIST);
		var $list = appendTo.find('div.contactsmenu-popover');

		$div.click(function() {
			if (!$list.hasClass('hidden')) {
				$list.addClass('hidden');
				$list.hide();
				return;
			}

			$list.removeClass('hidden');
			$list.show();

			if ($list.hasClass('loaded')) {
				return;
			}

			$list.addClass('loaded');
			$.ajax(OC.generateUrl('/contactsmenu/findOne'), {
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
		}).catch(function(reason) {
			// TODO
		});

		$(document).click(function(event) {
			var clickedList = $.contains($list, event.target);
			var clickedTarget = $.contains($div, event.target);

			$div.each(function() {
				if ($(this).is(event.target)) {
					clickedTarget = true;
				}
			});

			if (clickedList || clickedTarget) {
				return;
			}

			$list.addClass('hidden');
			$list.hide();
		});
	};
}(jQuery));
