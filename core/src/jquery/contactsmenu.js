/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import $ from 'jquery'

import { generateUrl } from '@nextcloud/router'
import { isA11yActivation } from '../Util/a11y.js'

const LIST = ''
	+ '<div class="menu popovermenu menu-left hidden contactsmenu-popover">'
	+ '    <ul>'
	+ '        <li>'
	+ '            <a>'
	+ '                <span class="icon-loading-small"></span>'
	+ '            </a>'
	+ '        </li>'
	+ '    </ul>'
	+ '</div>'

const entryTemplate = require('./contactsmenu/jquery_entry.handlebars')

$.fn.contactsMenu = function(shareWith, shareType, appendTo) {
	// 0 - user, 4 - email, 6 - remote
	const allowedTypes = [0, 4, 6]
	if (allowedTypes.indexOf(shareType) === -1) {
		return
	}

	const $div = this
	appendTo.append(LIST)
	const $list = appendTo.find('div.contactsmenu-popover')

	$div.on('click keydown', function(event) {
		if (!isA11yActivation(event)) {
			return
		}

		if (!$list.hasClass('hidden')) {
			$list.addClass('hidden')
			$list.hide()
			return
		}

		$list.removeClass('hidden')
		$list.show()

		if ($list.hasClass('loaded')) {
			return
		}

		$list.addClass('loaded')
		$.ajax(generateUrl('/contactsmenu/findOne'), {
			method: 'POST',
			data: {
				shareType,
				shareWith,
			},
		}).then(function(data) {
			$list.find('ul').find('li').addClass('hidden')

			let actions
			if (!data.topAction) {
				actions = [{
					hyperlink: '#',
					title: t('core', 'No action available'),
				}]
			} else {
				actions = [data.topAction].concat(data.actions)
			}

			actions.forEach(function(action) {
				$list.find('ul').append(entryTemplate(action))
			})

			$div.trigger('load')
		}, function(jqXHR) {
			$list.find('ul').find('li').addClass('hidden')

			let title
			if (jqXHR.status === 404) {
				title = t('core', 'No action available')
			} else {
				title = t('core', 'Error fetching contact actions')
			}

			$list.find('ul').append(entryTemplate({
				hyperlink: '#',
				title,
			}))

			$div.trigger('loaderror', jqXHR)
		})
	})

	$(document).click(function(event) {
		const clickedList = ($list.has(event.target).length > 0)
		let clickedTarget = ($div.has(event.target).length > 0)

		$div.each(function() {
			if ($(this).is(event.target)) {
				clickedTarget = true
			}
		})

		if (clickedList || clickedTarget) {
			return
		}

		$list.addClass('hidden')
		$list.hide()
	})
}
