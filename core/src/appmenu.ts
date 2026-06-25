/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Standalone entry for the waffle launcher (AppMenu). Mounts independently of
 * core-main so the app grid lives in its own chunk.
 */
import Vue from 'vue'
import AppMenu from './components/AppMenu/AppMenu.vue'

interface AppMenuInstance {
	setNavigationCounter(id: string, counter: number): void
}

declare global {
	var OC: {
		setNavigationCounter?: (id: string, counter: number) => void
	}
}

/**
 * Mount the AppMenu into the header container, if present on this layout.
 */
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

mount()
