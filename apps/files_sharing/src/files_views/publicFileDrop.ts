/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { VueConstructor } from 'vue'

import { Folder, Permission, View, getNavigation } from '@nextcloud/files'
import { defaultRemoteURL, defaultRootPath } from '@nextcloud/files/dav'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import svgCloudUpload from '@mdi/svg/svg/cloud-upload.svg?raw'
import Vue from 'vue'

export default () => {
	const foldername = loadState<string>('files_sharing', 'filename')

	let FilesViewFileDropEmptyContent: VueConstructor
	let fileDropEmptyContentInstance: Vue

	const view = new View({
		id: 'public-file-drop',
		name: t('files_sharing', 'File drop'),
		caption: t('files_sharing', 'Upload files to {foldername}', { foldername }),
		icon: svgCloudUpload,
		order: 1,

		emptyView: async (div: HTMLDivElement) => {
			if (FilesViewFileDropEmptyContent === undefined) {
				const { default: component } = await import('../views/FilesViewFileDropEmptyContent.vue')
				FilesViewFileDropEmptyContent = Vue.extend(component)
			}
			if (fileDropEmptyContentInstance) {
				fileDropEmptyContentInstance.$destroy()
			}
			fileDropEmptyContentInstance = new FilesViewFileDropEmptyContent({
				propsData: {
					foldername,
				},
			})
			fileDropEmptyContentInstance.$mount(div)
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
