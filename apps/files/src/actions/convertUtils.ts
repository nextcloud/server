/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { AxiosResponse } from '@nextcloud/axios'
import type { Folder, View } from '@nextcloud/files'

import { emit } from '@nextcloud/event-bus'
import { generateOcsUrl } from '@nextcloud/router'
import { showError, showLoading, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'
import PQueue from 'p-queue'

import logger from '../logger'
import { useFilesStore } from '../store/files'
import { getPinia } from '../store'
import { usePathsStore } from '../store/paths'

const queue = new PQueue({ concurrency: 5 })

const requestConversion = function(fileId: number, targetMimeType: string): Promise<AxiosResponse> {
	return axios.post(generateOcsUrl('/apps/files/api/v1/convert'), {
		fileId,
		targetMimeType,
	})
}

export const convertFiles = async function(fileIds: number[], targetMimeType: string, parentFolder: Folder | null) {
	const conversions = fileIds.map(fileId => queue.add(() => requestConversion(fileId, targetMimeType)))

	// Start conversion
	const toast = showLoading(t('files', 'Converting files…'))

	// Handle results
	try {
		const results = await Promise.allSettled(conversions)
		const failed = results.filter(result => result.status === 'rejected')
		if (failed.length) {
			const messages = failed.map(result => result.reason?.response?.data?.ocs?.meta?.message) as string[]
			logger.error('Failed to convert files', { fileIds, targetMimeType, error })

			// If all failed files have the same error message, show it
			if (new Set(messages).size === 1) {
				showError(t('files', 'Failed to convert files: {message}', { message: messages[0] }))
				return
			}

			if (failed.length === fileIds.length) {
				showError(t('files', 'Failed to convert files'))
				return
			}

			// A single file failed
			if (failed.length === 1) {
				// If we have a message for the failed file, show it
				if (messages[0]) {
					showError(t('files', 'One file could not be converted: {message}', { message: messages[0] }))
					return
				}

				// Otherwise, show a generic error
				showError(t('files', 'One file could not be converted'))
				return
			}

			showError(t('files', '{count} files could not be converted', { count: failed.length }))
			return
		}

		// All files converted
		showSuccess(t('files', 'Files successfully converted'))

		// Trigger a reload of the file list
		if (parentFolder) {
			emit('files:node:updated', parentFolder)
		}

		// Switch to the new files
		const firstSuccess = results[0] as PromiseFulfilledResult<AxiosResponse>
		const newFileId = firstSuccess.value.data.ocs.data.fileId
		window.OCP.Files.Router.goToRoute(null, { ...window.OCP.Files.Router.params, fileid: newFileId }, window.OCP.Files.Router.query)
	} catch (error) {
		// Should not happen as we use allSettled and handle errors above
		showError(t('files', 'Failed to convert files'))
		logger.error('Failed to convert files', { fileIds, targetMimeType, error })
	} finally {
		// Hide loading toast
		toast.hideToast()
	}
}

export const convertFile = async function(fileId: number, targetMimeType: string, parentFolder: Folder | null) {
	const toast = showLoading(t('files', 'Converting file…'))

	try {
		const result = await queue.add(() => requestConversion(fileId, targetMimeType)) as AxiosResponse
		showSuccess(t('files', 'File successfully converted'))

		// Trigger a reload of the file list
		if (parentFolder) {
			emit('files:node:updated', parentFolder)
		}

		// Switch to the new file
		const newFileId = result.data.ocs.data.fileId
		window.OCP.Files.Router.goToRoute(null, { ...window.OCP.Files.Router.params, fileid: newFileId }, window.OCP.Files.Router.query)
	} catch (error) {
		// If the server returned an error message, show it
		if (error.response?.data?.ocs?.meta?.message) {
			showError(t('files', 'Failed to convert file: {message}', { message: error.response.data.ocs.meta.message }))
			return
		}

		logger.error('Failed to convert file', { fileId, targetMimeType, error })
		showError(t('files', 'Failed to convert file'))
	} finally {
		// Hide loading toast
		toast.hideToast()
	}
}

/**
 * Get the parent folder of a path
 *
 * TODO: replace by the parent node straight away when we
 * update the Files actions api accordingly.
 *
 * @param view The current view
 * @param path The path to the file
 * @returns The parent folder
 */
export const getParentFolder = function(view: View, path: string): Folder | null {
	const filesStore = useFilesStore(getPinia())
	const pathsStore = usePathsStore(getPinia())

	const parentSource = pathsStore.getPath(view.id, path)
	if (!parentSource) {
		return null
	}

	const parentFolder = filesStore.getNode(parentSource) as Folder | undefined
	return parentFolder ?? null
}
