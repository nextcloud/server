/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getDialogBuilder } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { FileListAction } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { TRASHBIN_VIEW_ID } from '../files_views/trashbinView.ts'
import { emptyTrash } from '../services/api.ts'

export type FilesTrashbinConfigState = {
	allow_delete: boolean
}

export const emptyTrashAction = new FileListAction({
	id: 'empty-trash',

	displayName: () => t('files_trashbin', 'Empty deleted files'),
	order: 0,

	enabled({ view, folder, contents }) {
		if (view.id !== TRASHBIN_VIEW_ID) {
			return false
		}

		const config = loadState<FilesTrashbinConfigState>('files_trashbin', 'config')
		if (!config.allow_delete) {
			return false
		}

		return contents.length > 0 && folder.path === '/'
	},

	async exec({ contents }): Promise<null> {
		const askConfirmation = new Promise<boolean>((resolve) => {
			const dialog = getDialogBuilder(t('files_trashbin', 'Confirm permanent deletion'))
				.setSeverity('warning')
				// TODO Add note for groupfolders
				.setText(t('files_trashbin', 'Are you sure you want to permanently delete all files and folders in the trash? This cannot be undone.'))
				.setButtons([
					{
						label: t('files_trashbin', 'Cancel'),
						variant: 'secondary',
						callback: () => resolve(false),
					},
					{
						label: t('files_trashbin', 'Empty deleted files'),
						variant: 'error',
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
				contents.forEach((node) => emit('files:node:deleted', node))
			}
			return null
		}

		return null
	},
})
