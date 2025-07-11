/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Entry, Folder, Node } from '@nextcloud/files'

import { defineAsyncComponent } from 'vue'
import { spawnDialog } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import FileUploadSvg from '@mdi/svg/svg/file-upload.svg?raw'

import Config from '../services/ConfigService'
import { isPublicShare } from '@nextcloud/sharing/public'
const sharingConfig = new Config()

const NewFileRequestDialogVue = defineAsyncComponent(() => import('../components/NewFileRequestDialog.vue'))

export const EntryId = 'file-request'

export const entry = {
	id: EntryId,
	displayName: t('files_sharing', 'Create file request'),
	iconSvgInline: FileUploadSvg,
	order: 10,
	enabled(): boolean {
		// not on public shares
		if (isPublicShare()) {
			return false
		}
		if (!sharingConfig.isPublicUploadEnabled) {
			return false
		}
		// We will check for the folder permission on the dialog
		return sharingConfig.isPublicShareAllowed
	},
	async handler(context: Folder, content: Node[]) {
		spawnDialog(NewFileRequestDialogVue, {
			context,
			content,
		})
	},
} as Entry
