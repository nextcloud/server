/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { App } from 'vue'

import svgCloudUpload from '@mdi/svg/svg/cloud-upload.svg?raw'
import { Folder, getNavigation, Permission, View } from '@nextcloud/files'
import { defaultRemoteURL, defaultRootPath } from '@nextcloud/files/dav'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { createApp } from 'vue'

export default () => {
	const foldername = loadState<string>('files_sharing', 'filename')

	let fileDropEmptyContentApp: App | undefined

	const view = new View({
		id: 'public-file-drop',
		name: t('files_sharing', 'File drop'),
		caption: t('files_sharing', 'Upload files to {foldername}', { foldername }),
		icon: svgCloudUpload,
		order: 1,

		emptyView: async (div: HTMLDivElement) => {
			const { default: FilesViewFileDropEmptyContent } = await import('./FilesViewFileDropEmptyContent.vue')

			fileDropEmptyContentApp?.unmount()
			fileDropEmptyContentApp = createApp(FilesViewFileDropEmptyContent, { foldername })
			fileDropEmptyContentApp.mount(div)
		},

		getContents: async () => {
			return {
				contents: [],
				// Fake a writeonly folder as root
				folder: new Folder({
					id: 0,
					source: `${defaultRemoteURL}${defaultRootPath}`,
					root: defaultRootPath,
					owner: null,
					permissions: Permission.CREATE,
				}),
			}
		},
	})

	const Navigation = getNavigation()
	Navigation.register(view)
}
