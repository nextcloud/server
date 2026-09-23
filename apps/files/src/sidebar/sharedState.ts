/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ShallowRef } from 'vue'
import type Vue from 'vue'
import type { ISidebarDataProvider } from './types.ts'

import { shallowRef } from 'vue'

/**
 * The sidebar state that must be a singleton for the whole page.
 *
 * The `files-main` and `files-sidebar` entry points both contain the sidebar code,
 * but the Webpack build only shares modules from `node_modules` between bundles.
 * Module scoped state therefore exists once per entry point, so it is kept on the
 * `OCA.Files.Sidebar` namespace instead - the same approach `getPinia()` uses.
 */
export interface SidebarSharedState {
	/** The registered data provider backing the sidebar. */
	dataProvider: ShallowRef<ISidebarDataProvider | undefined>

	/** The data provider used on pages where no app provides the sidebar data. */
	standaloneProvider?: ISidebarDataProvider

	/** The rendered sidebar, if it is currently mounted. */
	instance?: Vue
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
