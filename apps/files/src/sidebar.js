/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
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
import { translate as t } from '@nextcloud/l10n'

import SidebarView from './views/Sidebar.vue'
import Sidebar from './services/Sidebar.js'
import Tab from './models/Tab.js'

Vue.prototype.t = t

// Init Sidebar Service
if (!window.OCA.Files) {
	window.OCA.Files = {}
}
Object.assign(window.OCA.Files, { Sidebar: new Sidebar() })
Object.assign(window.OCA.Files.Sidebar, { Tab })

window.addEventListener('DOMContentLoaded', function() {
	const contentElement = document.querySelector('body > .content')
		|| document.querySelector('body > #content')

	// Make sure we have a proper layout
	if (contentElement) {
		// Make sure we have a mountpoint
		if (!document.getElementById('app-sidebar')) {
			const sidebarElement = document.createElement('div')
			sidebarElement.id = 'app-sidebar'
			contentElement.appendChild(sidebarElement)
		}
	}

	// Init vue app
	const View = Vue.extend(SidebarView)
	const AppSidebar = new View({
		name: 'SidebarRoot',
	})
	AppSidebar.$mount('#app-sidebar')
	window.OCA.Files.Sidebar.open = AppSidebar.open
	window.OCA.Files.Sidebar.close = AppSidebar.close
	window.OCA.Files.Sidebar.setFullScreenMode = AppSidebar.setFullScreenMode
})
