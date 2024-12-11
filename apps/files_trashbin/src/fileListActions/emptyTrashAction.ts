/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Node } from '@nextcloud/files'

import PQueue from 'p-queue'
import { FileListAction } from '@nextcloud/files'
import {
	DialogSeverity,
	getDialogBuilder,
	showError,
	showInfo,
	showSuccess,
	TOAST_PERMANENT_TIMEOUT,
} from '@nextcloud/dialogs'

import { deleteNode } from '../../../files/src/actions/deleteUtils.ts'
import { logger } from '../logger.ts'

type Toast = ReturnType<typeof showInfo>

const queue = new PQueue({ concurrency: 5 })

const showLoadingToast = (): null | Toast => {
	const message = t('files_trashbin', 'Deleting files…')
	let toast: null | Toast = null
	toast = showInfo(
		`<span class="icon icon-loading-small toast-loading-icon"></span> ${message}`,
		{
			isHTML: true,
			timeout: TOAST_PERMANENT_TIMEOUT,
			onRemove: () => {
				toast?.hideToast()
				toast = null
			},
		},
	)
	return toast
}

const emptyTrash = async (nodes: Node[]) => {
	const promises = nodes.map((node) => {
		const { promise, resolve, reject } = Promise.withResolvers<void>()
		queue.add(async () => {
			try {
				await deleteNode(node)
				resolve()
			} catch (error) {
				logger.error('Failed to delete node', { error, node })
				reject(error)
			}
		})
		return promise
	})

	const toast = showLoadingToast()
	const results = await Promise.allSettled(promises)
	if (results.some((result) => result.status === 'rejected')) {
		toast?.hideToast()
		showError(t('files_trashbin', 'Failed to delete all previously deleted files'))
		return
	}
	toast?.hideToast()
	showSuccess(t('files_trashbin', 'Permanently deleted all previously deleted files'))
}

export const emptyTrashAction = new FileListAction({
	id: 'empty-trash',

	displayName: () => t('files_trashbin', 'Empty deleted files'),
	order: 0,

	enabled: (view, nodes, { folder }) => {
		if (view.id !== 'trashbin') {
			return false
		}
		return nodes.length > 0 && folder.path === '/'
	},

	exec: async (view, nodes) => {
		const dialog = getDialogBuilder(t('files_trashbin', 'Confirm permanent deletion'))
			.setSeverity(DialogSeverity.Warning)
			// TODO Add note for groupfolders
			.setText(t('files_trashbin', 'Are you sure you want to permanently delete all previously deleted files? This cannot be undone.'))
			.setButtons([
				{
					label: t('files_trashbin', 'Cancel'),
					type: 'secondary',
					callback: () => {},
				},
				{
					label: t('files_trashbin', 'Empty deleted files'),
					type: 'error',
					callback: () => {
						emptyTrash(nodes)
					},
				},
			])
			.build()

		try {
			await dialog.show()
		} catch (error) {
			// Allow throw on dialog close
		}
	},
})
