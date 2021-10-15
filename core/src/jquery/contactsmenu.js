/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import $ from 'jquery'

import { generateOcsUrl } from '@nextcloud/router'

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

	$div.click(function() {
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
		$.ajax(generateOcsUrl('/contactsmenu/find-one'), {
			method: 'POST',
			data: {
				format: 'json',
				shareType,
				shareWith,
			},
		}).then(function(data) {
			const contact = data.ocs.data

			$list.find('ul').find('li').addClass('hidden')

			let actions
			if (!contact.topAction) {
				actions = [{
					hyperlink: '#',
					title: t('core', 'No action available'),
				}]
			} else {
				actions = [contact.topAction].concat(contact.actions)
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
