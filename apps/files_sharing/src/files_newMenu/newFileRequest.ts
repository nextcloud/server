/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Folder, NewMenuEntry, Node } from '@nextcloud/files'

import FileUploadSvg from '@mdi/svg/svg/file-upload-outline.svg?raw'
import { t } from '@nextcloud/l10n'
import { isPublicShare } from '@nextcloud/sharing/public'
import { spawnDialog } from '@nextcloud/vue/functions/dialog'
import { defineAsyncComponent } from 'vue'
import Config from '../services/ConfigService.ts'

const sharingConfig = new Config()

const NewFileRequestDialogVue = defineAsyncComponent(() => import('../components/NewFileRequestDialog.vue'))

export const EntryId = 'file-request'

export const entry: NewMenuEntry = {
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
}
