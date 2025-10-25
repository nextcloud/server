/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type Tab from './apps/files/src/models/Tab.js'
import type RouterService from './apps/files/src/services/RouterService.ts'
import type Settings from './apps/files/src/services/Settings.js'
import type Sidebar from './apps/files/src/services/Sidebar.js'

type SidebarAPI = Sidebar & {
	open: (path: string) => Promise<void>
	close: () => void
	setFullScreenMode: (fullScreen: boolean) => void
	setShowTagsDefault: (showTagsDefault: boolean) => void
	Tab: typeof Tab
}

declare global {
	interface Window {
		OC: Nextcloud.v29.OC

		// Private Files namespace
		OCA: {
			Files: {
				Settings: Settings
				Sidebar: SidebarAPI
			}
		} & Record<string, any> // eslint-disable-line @typescript-eslint/no-explicit-any

		// Public Files namespace
		OCP: {
			Files: {
				Router: RouterService
			}
		} & Nextcloud.v29.OCP

		// Private global files pinia store
		_nc_files_pinia: Pinia
	}
}
