/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Entry, Folder, Node } from '@nextcloud/files'

import { Permission } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import FileUploadSvg from '@mdi/svg/svg/file-upload.svg?raw'
import Vue, { defineAsyncComponent } from 'vue'
import Config from '../services/ConfigService'

const NewFileRequestDialogVue = defineAsyncComponent(() => import('../components/NewFileRequestDialog.vue'))

const sharingConfig = new Config()

export const entry = {
	id: 'file-request',
	displayName: t('files_sharing', 'Create file request'),
	iconSvgInline: FileUploadSvg,
	order: 30,
	enabled(context: Folder): boolean {
		if ((context.permissions & Permission.SHARE) !== 0) {
			// We need to have either link shares creation permissions
			return sharingConfig.isPublicShareAllowed
		}
		return false
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
