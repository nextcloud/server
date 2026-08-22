/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ISidebar } from '@nextcloud/files'
import type { ISidebarDataProvider } from './types.ts'

import { getPinia } from '../store/index.ts'
import { useSidebarStore } from '../store/sidebar.ts'
import { logger } from '../utils/logger.ts'
import { isSidebarMounted, mountSidebar } from './mount.ts'
import { getSidebarDataProvider, hasSidebarDataProvider, setSidebarDataProvider } from './provider.ts'
import { createStandaloneDataProvider } from './providers/standalone.ts'

let standaloneProvider: ISidebarDataProvider | undefined

/**
 * Set up the sidebar for the current page.
 */
export function initializeSidebar(): void {
	if (rendersOwnSidebar()) {
		exposeSidebarApi()
		return
	}

	if (isSidebarMounted()) {
		logger.debug('sidebar: already rendered by the current app')
		return
	}

	const content = document.querySelector<HTMLElement>('body > .content')
		?? document.querySelector<HTMLElement>('body > #content')
	if (!content) {
		logger.error('sidebar: cannot render the sidebar as the page has no content element')
		return
	}

	logger.debug('sidebar: no data provider registered, rendering the sidebar for the current app')
	renderSidebar(content)
}

/**
 * Render the sidebar into an element of the current page.
 *
 * @param target - The element to render the sidebar into
 */
export function renderSidebar(target: HTMLElement): void {
	if (rendersOwnSidebar()) {
		logger.debug('sidebar: not rendering the sidebar as the current app renders it itself')
		return
	}

	if (!hasSidebarDataProvider()) {
		standaloneProvider = createStandaloneDataProvider()
		setSidebarDataProvider(standaloneProvider)
	}

	if (mountSidebar(target)) {
		exposeSidebarApi()
	}
}

/**
 * Expose the sidebar implementation which is proxied by the `@nextcloud/files` library.
 */
export function exposeSidebarApi(): void {
	window.OCA.Files ??= {}
	window.OCA.Files._sidebar = () => useSidebarStore(getPinia()) satisfies Omit<ISidebar, 'available' | 'mount' | 'registerTab' | 'registerAction'>
}

/**
 * Expose rendering the sidebar, so apps can render it within their own layout.
 */
export function exposeSidebarMount(): void {
	window.OCA.Files ??= {}
	window.OCA.Files._mountSidebar = renderSidebar
}

/**
 * Whether the app of the current page renders the sidebar on its own,
 * like the files app does as part of its app layout.
 */
function rendersOwnSidebar(): boolean {
	const provider = getSidebarDataProvider()
	return provider !== undefined && provider !== standaloneProvider
}
