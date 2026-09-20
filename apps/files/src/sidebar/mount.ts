/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { PiniaVuePlugin } from 'pinia'
import Vue from 'vue'
import FilesSidebar from '../views/FilesSidebar.vue'
import { getPinia } from '../store/index.ts'
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
		if (state.instance.$el.parentElement === target) {
			logger.debug('sidebar: already rendered within the requested element')
			return true
		}

		logger.debug('sidebar: moving the sidebar into the requested element')
		state.instance.$destroy()
		state.instance.$el.remove()
	}

	const mountpoint = document.createElement('div')
	mountpoint.id = 'app-sidebar'
	target.appendChild(mountpoint)

	Vue.use(PiniaVuePlugin)
	const SidebarRoot = Vue.extend(FilesSidebar)
	state.instance = new SidebarRoot({
		name: 'SidebarRoot',
		pinia: getPinia(),
	}).$mount(mountpoint)

	logger.debug('sidebar: rendered within the current app')
	return true
}
