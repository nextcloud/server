/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	window.OCA.Files.Sidebar.setShowTagsDefault = AppSidebar.setShowTagsDefault
})
