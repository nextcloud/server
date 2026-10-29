/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import FilesSidebar from '../views/FilesSidebar.vue'
import { pinia } from '../store/index.ts'
import { logger } from '../utils/logger.ts'
import { getSidebarSharedState } from './sharedState.ts'

/**
 * Whether the sidebar is currently rendered within the page.
 */
export function isSidebarMounted(): boolean {
	return getSidebarSharedState().instance !== undefined
}

/**
 * Render the sidebar into an element of the current page.
 *
 * @param target - The element to render the sidebar into
 * @return Whether the sidebar is rendered
 */
export function mountSidebar(target: HTMLElement): boolean {
	if (!(target instanceof HTMLElement)) {
		logger.error('sidebar: cannot render the sidebar as no element to render it into was provided', { target })
		return false
	}

	const state = getSidebarSharedState()
	if (state.instance !== undefined) {
		if (state.instance.mountpoint.parentElement === target) {
			logger.debug('sidebar: already rendered within the requested element')
			return true
		}

		logger.debug('sidebar: moving the sidebar into the requested element')
		state.instance.app.unmount()
		state.instance.mountpoint.remove()
	}

	// the sidebar is rendered within the mountpoint, so let it take part in the layout of the target
	const mountpoint = document.createElement('div')
	mountpoint.style.display = 'contents'
	target.appendChild(mountpoint)

	const app = createApp(FilesSidebar)
	app.use(pinia)
	app.mount(mountpoint)
	state.instance = { app, mountpoint }

	logger.debug('sidebar: rendered within the current app')
	return true
}

/**
 * Remove the sidebar from the page, if it is rendered.
 */
export function unmountSidebar(): void {
	const state = getSidebarSharedState()
	if (state.instance === undefined) {
		return
	}

	state.instance.app.unmount()
	state.instance.mountpoint.remove()
	state.instance = undefined
	logger.debug('sidebar: removed from the current app')
}
