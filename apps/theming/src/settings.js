/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import Vue from 'vue'
import App from './UserThemes.vue'

// bind to window
Vue.prototype.OC = OC
Vue.prototype.t = t

const View = Vue.extend(App)
const theming = new View()
theming.$mount('#theming')

theming.$on('update:background', () => {
	// Refresh server-side generated theming CSS
	[...document.head.querySelectorAll('link.theme')].forEach(theme => {
		const url = new URL(theme.href)
		url.searchParams.set('v', Date.now())
		const newTheme = theme.cloneNode()
		newTheme.href = url.toString()
		newTheme.onload = () => theme.remove()
		document.head.append(newTheme)
	})
})
