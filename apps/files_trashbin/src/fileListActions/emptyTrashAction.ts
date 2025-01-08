/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node, View, Folder } from '@nextcloud/files'

import axios from '@nextcloud/axios'
import { FileListAction } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import {
	DialogSeverity,
	getDialogBuilder,
	showError,
	showInfo,
	showSuccess,
} from '@nextcloud/dialogs'

import { logger } from '../logger.ts'
import { generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { emit } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'

export type FilesTrashbinConfigState = {
	allow_delete: boolean;
}

const emptyTrash = async (): Promise<boolean> => {
	try {
		await axios.delete(generateRemoteUrl('dav') + `/trashbin/${getCurrentUser()?.uid}/trash`)
		showSuccess(t('files_trashbin', 'All files have been permanently deleted'))
		return true
	} catch (error) {
		showError(t('files_trashbin', 'Failed to empty deleted files'))
		logger.error('Failed to empty deleted files', { error })
		return false
	}
}

export const emptyTrashAction = new FileListAction({
	id: 'empty-trash',

	displayName: () => t('files_trashbin', 'Empty deleted files'),
	order: 0,

	enabled(view: View, nodes: Node[], folder: Folder) {
		if (view.id !== 'trashbin') {
			return false
		}

		const config = loadState<FilesTrashbinConfigState>('files_trashbin', 'config')
		if (!config.allow_delete) {
			return false
		}

		return nodes.length > 0 && folder.path === '/'
	},

	async exec(view: View, nodes: Node[]): Promise<void> {
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
			return
		}

		showInfo(t('files_trashbin', 'Deletion cancelled'))
	},
})
