/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { App, ComponentPublicInstance } from 'vue'

import BackupRestore from '@mdi/svg/svg/backup-restore.svg?raw'
import { t } from '@nextcloud/l10n'
import { createApp } from 'vue'
import FilesVersionsSidebarTab from './views/FilesVersionsSidebarTab.vue'

// Init FilesVersions tab component
let filesVersionsTabApp: App<Element> | null = null
let filesVersionsTabInstance: ComponentPublicInstance<typeof FilesVersionsSidebarTab> | null = null

window.addEventListener('DOMContentLoaded', function() {
	if (window.OCA.Files?.Sidebar === undefined) {
		return
	}

	window.OCA.Files.Sidebar.registerTab(new window.OCA.Files.Sidebar.Tab({
		id: 'files_versions',
		name: t('files_versions', 'Versions'),
		iconSvg: BackupRestore,

		async mount(el, fileInfo) {
			// destroy previous instance if available
			if (filesVersionsTabApp) {
				filesVersionsTabApp.unmount()
			}
			filesVersionsTabApp = createApp(FilesVersionsSidebarTab)
			filesVersionsTabInstance = filesVersionsTabApp.mount(el)
			filesVersionsTabInstance.update(fileInfo)
		},
		update(fileInfo) {
			filesVersionsTabInstance!.update(fileInfo)
		},
		setIsActive(isActive) {
			filesVersionsTabInstance?.setIsActive(isActive)
		},
		destroy() {
			filesVersionsTabApp?.unmount()
			filesVersionsTabApp = null
		},
		enabled(fileInfo) {
			return !(fileInfo?.isDirectory() ?? true)
		},
	}))
})
