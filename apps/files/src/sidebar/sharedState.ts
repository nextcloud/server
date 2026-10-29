/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { App, ShallowRef } from 'vue'
import type { ISidebarDataProvider } from './types.ts'

import { shallowRef } from 'vue'

/**
 * The sidebar state that must be a singleton for the whole page.
 *
 * The `files-main` and `files-sidebar` entry points both contain the sidebar code,
 * so the state is kept on the `OCA.Files.Sidebar` namespace to share it between them.
 */
export interface SidebarSharedState {
	/** The registered data provider backing the sidebar. */
	dataProvider: ShallowRef<ISidebarDataProvider | undefined>

	/** The data provider used on pages where no app provides the sidebar data. */
	standaloneProvider?: ISidebarDataProvider

	/** The rendered sidebar, if it is currently mounted. */
	instance?: {
		app: App
		mountpoint: HTMLElement
	}
}

/**
 * Get the sidebar state shared by all entry points of the current page.
 */
export function getSidebarSharedState(): SidebarSharedState {
	window.OCA.Files ??= {}
	window.OCA.Files.Sidebar ??= {}
	window.OCA.Files.Sidebar._sharedState ??= {
		dataProvider: shallowRef<ISidebarDataProvider>(),
	}

	return window.OCA.Files.Sidebar._sharedState
}
