/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Standalone entry for the waffle launcher (AppMenu). Mounts independently of
 * core-main so the app grid lives in its own chunk.
 */

import { createApp } from 'vue'
import AppMenu from './components/AppMenu/AppMenu.vue'
import { mountInPlace } from './utils/mountInPlace.ts'

const placeholder = document.getElementById('header-start__appmenu')
// No placeholder on layouts without the app menu, e.g. public pages.
if (placeholder) {
	const appMenu = mountInPlace(createApp(AppMenu), placeholder) as InstanceType<typeof AppMenu>

	Object.assign(window.OC, {
		setNavigationCounter(id: string, counter: number) {
			appMenu.setNavigationCounter(id, counter)
		},
	})
}
