/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { subscribe } from '@nextcloud/event-bus'
import { createPinia } from 'pinia'
import { createApp } from 'vue'
import UserStatus from './UserStatus.vue'

import './user-status-icons.css'

const pinia = createPinia()
const mountPoint = document.getElementById('user_status-menu-entry')

/**
 *
 */
function mountMenuEntry() {
	const mountPoint = document.getElementById('user_status-menu-entry')
	// TODO: fix me after Core migration to Vue 3
	// In Vue 2 menu items were mounted in place to the menu items
	// In Vue 3 they are mounted inside the menu item
	// A workaround - replace the menu item with "display: contents" div
	const transparentMountPoint = document.createElement('div')
	transparentMountPoint.style.display = 'contents'
	mountPoint.replaceWith(transparentMountPoint)

	createApp(UserStatus)
		.use(pinia)
		.mount(transparentMountPoint)
}

if (mountPoint) {
	mountMenuEntry()
} else {
	subscribe('core:user-menu:mounted', mountMenuEntry)
}

// Register dashboard status
document.addEventListener('DOMContentLoaded', function() {
	if (!OCA.Dashboard) {
		return
	}

	OCA.Dashboard.registerStatus('status', (el) => {
		createApp(UserStatus, {
			inline: true,
		})
			.use(pinia)
			.mount(el)
	})
})
