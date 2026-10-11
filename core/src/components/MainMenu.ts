/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import AppMenu from './AppMenu.vue'
import { mountInPlace } from '../utils/mountInPlace.ts'

/**
 * Set up the main menu component ("AppMenu")
 * This is the top left menu where users can navigate between different apps.
 */
export function setUp() {
	const container = document.getElementById('header-start__appmenu')
	if (!container) {
		// no container, possibly we're on a public page
		return
	}
	const appMenu = mountInPlace(createApp(AppMenu), container) as InstanceType<typeof AppMenu>

	Object.assign(window.OC, {
		setNavigationCounter(id: string, counter: number) {
			appMenu.setNavigationCounter(id, counter)
		},
	})
}
