/**
 * @copyright 2022 John Molakvoæ <skjnldsv@protonmail.com>
 *
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import { generateUrl } from '@nextcloud/router'
import Vue from 'vue'

import { browserStorageKey } from './utils/RedirectUnsupportedBrowsers.js'
import browserStorage from './services/BrowserStorageService.js'
import UnsupportedBrowser from './views/UnsupportedBrowser.vue'

// If the ignore token is set, redirect
if (browserStorage.getItem(browserStorageKey) === 'true') {
	window.location = generateUrl('/')
}

export default new Vue({
	el: '#unsupported-browser',
	// eslint-disable-next-line vue/match-component-file-name
	name: 'UnsupportedBrowserRoot',
	render: h => h(UnsupportedBrowser),
})
