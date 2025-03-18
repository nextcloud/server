/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node, View, Folder } from '@nextcloud/files'

import { emit } from '@nextcloud/event-bus'
import { FileListAction } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import {
	DialogSeverity,
	getDialogBuilder,
	showInfo,
} from '@nextcloud/dialogs'
import { emptyTrash } from '../services/api.ts'
import { TRASHBIN_VIEW_ID } from '../files_views/trashbinView.ts'

export type FilesTrashbinConfigState = {
	allow_delete: boolean;
}

export const emptyTrashAction = new FileListAction({
	id: 'empty-trash',

	displayName: () => t('files_trashbin', 'Empty deleted files'),
	order: 0,

	enabled(view: View, nodes: Node[], folder: Folder) {
		if (view.id !== TRASHBIN_VIEW_ID) {
			return false
		}

		const config = loadState<FilesTrashbinConfigState>('files_trashbin', 'config')
		if (!config.allow_delete) {
			return false
		}

		return nodes.length > 0 && folder.path === '/'
	},

	async exec(view: View, nodes: Node[]): Promise<null> {
		const askConfirmation = new Promise<boolean>((resolve) => {
			const dialog = getDialogBuilder(t('files_trashbin', 'Confirm permanent deletion'))
				.setSeverity(DialogSeverity.Warning)
				// TODO Add note for groupfolders
				.setText(t('files_trashbin', 'Are you sure you want to permanently delete all files and folders in the trash? This cannot be undone.'))
				.setButtons([
					{
						label: t('files_trashbin', 'Cancel'),
						type: 'secondary',
						callback: () => resolve(false),
					},
					{
						label: t('files_trashbin', 'Empty deleted files'),
						type: 'error',
						callback: () => resolve(true),
					},
				])
				.build()
			dialog.show().then(() => {
				resolve(false)
			})
		})

		const result = await askConfirmation
		if (result === true) {
			if (await emptyTrash()) {
				nodes.forEach((node) => emit('files:node:deleted', node))
			}
			return null
		}

		showInfo(t('files_trashbin', 'Deletion cancelled'))
		return null
	},
})
