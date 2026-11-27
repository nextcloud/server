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
	// The id is on the anchor of the core menu entry, replace the whole list item
	// so the user status entry does not render nested inside another list item
	const menuEntry = document.getElementById('user_status-menu-entry').closest('li')
	const transparentMountPoint = document.createElement('div')
	transparentMountPoint.style.display = 'contents'
	menuEntry.replaceWith(transparentMountPoint)

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
