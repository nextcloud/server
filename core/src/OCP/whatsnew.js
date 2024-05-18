/**
 * @copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
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

import _ from 'underscore'
import $ from 'jquery'
import { generateOcsUrl } from '@nextcloud/router'

/**
 * @param {any} options -
 */
export function query(options) {
	options = options || {}
	const dismissOptions = options.dismiss || {}
	$.ajax({
		type: 'GET',
		url: options.url || generateOcsUrl('core/whatsnew?format=json'),
		success: options.success || function(data, statusText, xhr) {
			onQuerySuccess(data, statusText, xhr, dismissOptions)
		},
		error: options.error || onQueryError,
	})
}

/**
 * @param {any} version -
 * @param {any} options -
 */
export function dismiss(version, options) {
	options = options || {}
	$.ajax({
		type: 'POST',
		url: options.url || generateOcsUrl('core/whatsnew'),
		data: { version: encodeURIComponent(version) },
		success: options.success || onDismissSuccess,
		error: options.error || onDismissError,
	})
	// remove element immediately
	$('.whatsNewPopover').remove()
}

/**
 * @param {any} data -
 * @param {any} statusText -
 * @param {any} xhr -
 * @param {any} dismissOptions -
 */
function onQuerySuccess(data, statusText, xhr, dismissOptions) {
	console.debug('querying Whats New data was successful: ' + statusText)
	console.debug(data)

	if (xhr.status !== 200) {
		return
	}

	let item, menuItem, text, icon

	const div = document.createElement('div')
	div.classList.add('popovermenu', 'open', 'whatsNewPopover', 'menu-left')

	const list = document.createElement('ul')

	// header
	item = document.createElement('li')
	menuItem = document.createElement('span')
	menuItem.className = 'menuitem'

	text = document.createElement('span')
	text.innerText = t('core', 'New in') + ' ' + data.ocs.data.product
	text.className = 'caption'
	menuItem.appendChild(text)

	icon = document.createElement('span')
	icon.className = 'icon-close'
	icon.onclick = function() {
		dismiss(data.ocs.data.version, dismissOptions)
	}
	menuItem.appendChild(icon)

	item.appendChild(menuItem)
	list.appendChild(item)

	// Highlights
	for (const i in data.ocs.data.whatsNew.regular) {
		const whatsNewTextItem = data.ocs.data.whatsNew.regular[i]
		item = document.createElement('li')

		menuItem = document.createElement('span')
		menuItem.className = 'menuitem'

		icon = document.createElement('span')
		icon.className = 'icon-checkmark'
		menuItem.appendChild(icon)

		text = document.createElement('p')
		text.innerHTML = _.escape(whatsNewTextItem)
		menuItem.appendChild(text)

		item.appendChild(menuItem)
		list.appendChild(item)
	}

	// Changelog URL
	if (!_.isUndefined(data.ocs.data.changelogURL)) {
		item = document.createElement('li')

		menuItem = document.createElement('a')
		menuItem.href = data.ocs.data.changelogURL
		menuItem.rel = 'noreferrer noopener'
		menuItem.target = '_blank'

		icon = document.createElement('span')
		icon.className = 'icon-link'
		menuItem.appendChild(icon)

		text = document.createElement('span')
		text.innerText = t('core', 'View changelog')
		menuItem.appendChild(text)

		item.appendChild(menuItem)
		list.appendChild(item)
	}

	div.appendChild(list)
	document.body.appendChild(div)
}

/**
 * @param {any} x -
 * @param {any} t -
 * @param {any} e -
 */
function onQueryError(x, t, e) {
	console.debug('querying Whats New Data resulted in an error: ' + t + e)
	console.debug(x)
}

/**
 * @param {any} data -
 */
function onDismissSuccess(data) {
	// noop
}

/**
 * @param {any} data -
 */
function onDismissError(data) {
	console.debug('dismissing Whats New data resulted in an error: ' + data)
}
