/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Standalone entry for the waffle launcher (AppMenu). Mounts independently of
 * core-main so the app grid lives in its own chunk.
 *
 * Uses Vue 2 syntax because the legacy webpack pipeline (build/frontend-legacy/)
 * still resolves `vue` to 2.7.16. The modern Vite pipeline ships Vue 3 but does
 * not currently include core; migrating core is out of scope for this work.
 */
import Vue from 'vue'
import AppMenu from './AppMenu.vue'

interface AppMenuInstance {
	setNavigationCounter(id: string, counter: number): void
}

declare global {
	// eslint-disable-next-line no-var
	var OC: {
		setNavigationCounter?: (id: string, counter: number) => void
	}
}

function mount(): void {
	const container = document.getElementById('header-start__appmenu')
	if (!container) {
		// No container on this layout (e.g. public pages). Nothing to mount.
		return
	}
	const AppMenuApp = Vue.extend(AppMenu)
	const instance = new AppMenuApp({}).$mount(container) as unknown as AppMenuInstance

	globalThis.OC = globalThis.OC ?? {}
	globalThis.OC.setNavigationCounter = (id, counter) => {
		instance.setNavigationCounter(id, counter)
	}
}

// Loaded as a deferred core script, so the document is already parsed and the
// nav container exists by the time this runs.
mount()
