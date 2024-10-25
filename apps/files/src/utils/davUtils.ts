/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'
import type { WebDAVClientError } from 'webdav'

/**
 * Whether error is a WebDAVClientError
 * @param error - Any exception
 * @return {boolean} - Whether error is a WebDAVClientError
 */
function isWebDAVClientError(error: unknown): error is WebDAVClientError {
	return error instanceof Error && 'status' in error && 'response' in error
}

/**
 * Get a localized error message from webdav request
 * @param error - An exception from webdav request
 * @return {string} Localized error message for end user
 */
export function humanizeWebDAVError(error: unknown) {
	if (error instanceof Error) {
		if (isWebDAVClientError(error)) {
			const status = error.status || error.response?.status || 0
			if ([400, 404, 405].includes(status)) {
				return t('files', 'Folder not found')
			} else if (status === 403) {
				return t('files', 'This operation is forbidden')
			} else if (status === 500) {
				return t('files', 'This directory is unavailable, please check the logs or contact the administrator')
			} else if (status === 503) {
				return t('files', 'Storage is temporarily not available')
			}
		}
		return t('files', 'Unexpected error: {error}', { error: error.message })
	}

	return t('files', 'Unknown error')
}
