/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { AxiosResponse, AxiosError } from '@nextcloud/axios'
import type { OCSResponse } from '@nextcloud/typings/ocs'

import { emit } from '@nextcloud/event-bus'
import { generateOcsUrl } from '@nextcloud/router'
import { showError, showLoading, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import axios, { isAxiosError } from '@nextcloud/axios'
import PQueue from 'p-queue'

import { fetchNode } from '../services/WebdavClient.ts'
import logger from '../logger'

type ConversionResponse = {
	path: string
	fileId: number
}

interface PromiseRejectedResult<T> {
    status: 'rejected'
    reason: T
}

type PromiseSettledResult<T, E> = PromiseFulfilledResult<T> | PromiseRejectedResult<E>;
type ConversionSuccess = AxiosResponse<OCSResponse<ConversionResponse>>
type ConversionError = AxiosError<OCSResponse<ConversionResponse>>

const queue = new PQueue({ concurrency: 5 })
const requestConversion = function(fileId: number, targetMimeType: string): Promise<AxiosResponse> {
	return axios.post(generateOcsUrl('/apps/files/api/v1/convert'), {
		fileId,
		targetMimeType,
	})
}

export const convertFiles = async function(fileIds: number[], targetMimeType: string) {
	const conversions = fileIds.map(fileId => queue.add(() => requestConversion(fileId, targetMimeType)))

	// Start conversion
	const toast = showLoading(t('files', 'Converting files…'))

	// Handle results
	try {
		const results = await Promise.allSettled(conversions) as PromiseSettledResult<ConversionSuccess, ConversionError>[]
		const failed = results.filter(result => result.status === 'rejected') as PromiseRejectedResult<ConversionError>[]
		if (failed.length > 0) {
			const messages = failed.map(result => result.reason?.response?.data?.ocs?.meta?.message)
			logger.error('Failed to convert files', { fileIds, targetMimeType, messages })

			// If all failed files have the same error message, show it
			if (new Set(messages).size === 1 && typeof messages[0] === 'string') {
				showError(t('files', 'Failed to convert files: {message}', { message: messages[0] }))
				return
			}

			if (failed.length === fileIds.length) {
				showError(t('files', 'All files failed to be converted'))
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

			// We already check above when all files failed
			// if we're here, we have a mix of failed and successful files
			showError(t('files', '{count} files could not be converted', { count: failed.length }))
			showSuccess(t('files', '{count} files successfully converted', { count: fileIds.length - failed.length }))
			return
		}

		// All files converted
		showSuccess(t('files', 'Files successfully converted'))

		// Extract files that are within the current directory
		// in batch mode, you might have files from different directories
		// ⚠️, let's get the actual current dir, as the one from the action
		// might have changed as the user navigated away
		const currentDir = window.OCP.Files.Router.query.dir as string
		const newPaths = results
			.filter(result => result.status === 'fulfilled')
			.map(result => result.value.data.ocs.data.path)
			.filter(path => path.startsWith(currentDir))

		// Fetch the new files
		logger.debug('Files to fetch', { newPaths })
		const newFiles = await Promise.all(newPaths.map(path => fetchNode(path)))

		// Inform the file list about the new files
		newFiles.forEach(file => emit('files:node:created', file))

		// Switch to the new files
		const firstSuccess = results[0] as PromiseFulfilledResult<ConversionSuccess>
		const newFileId = firstSuccess.value.data.ocs.data.fileId
		window.OCP.Files.Router.goToRoute(null, { ...window.OCP.Files.Router.params, fileid: newFileId.toString() }, window.OCP.Files.Router.query)
	} catch (error) {
		// Should not happen as we use allSettled and handle errors above
		showError(t('files', 'Failed to convert files'))
		logger.error('Failed to convert files', { fileIds, targetMimeType, error })
	} finally {
		// Hide loading toast
		toast.hideToast()
	}
}

export const convertFile = async function(fileId: number, targetMimeType: string) {
	const toast = showLoading(t('files', 'Converting file…'))

	try {
		const result = await queue.add(() => requestConversion(fileId, targetMimeType)) as AxiosResponse<OCSResponse<ConversionResponse>>
		showSuccess(t('files', 'File successfully converted'))

		// Inform the file list about the new file
		const newFile = await fetchNode(result.data.ocs.data.path)
		emit('files:node:created', newFile)

		// Switch to the new file
		const newFileId = result.data.ocs.data.fileId
		window.OCP.Files.Router.goToRoute(null, { ...window.OCP.Files.Router.params, fileid: newFileId.toString() }, window.OCP.Files.Router.query)
	} catch (error) {
		// If the server returned an error message, show it
		if (isAxiosError(error) && error.response?.data?.ocs?.meta?.message) {
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
