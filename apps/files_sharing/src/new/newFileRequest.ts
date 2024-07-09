/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Entry, Folder, Node } from '@nextcloud/files'

import { translate as t } from '@nextcloud/l10n'
import Vue, { defineAsyncComponent } from 'vue'
import FileUploadSvg from '@mdi/svg/svg/file-upload.svg?raw'

const NewFileRequestDialogVue = defineAsyncComponent(() => import('../components/NewFileRequestDialog.vue'))

export const entry = {
	id: 'file-request',
	displayName: t('files', 'Create new file request'),
	iconSvgInline: FileUploadSvg,
	order: 30,
	enabled(): boolean {
		// TODO: determine requirements
		// 1. user can share the root folder
		// 2. OR user can create subfolders ?
		return true
	},
	async handler(context: Folder, content: Node[]) {
		// Create document root
		const mountingPoint = document.createElement('div')
		mountingPoint.id = 'file-request-dialog'
		document.body.appendChild(mountingPoint)

		// Init vue app
		const NewFileRequestDialog = new Vue({
			name: 'NewFileRequestDialogRoot',
			render: (h) => h(
				NewFileRequestDialogVue,
				{
					props: {
						context,
						content,
					},
					on: {
						close: () => {
							NewFileRequestDialog.$destroy()
						},
					},
				},
			),
			el: mountingPoint,
		})
	},
} as Entry
