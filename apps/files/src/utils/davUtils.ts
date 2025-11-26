/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { WebDAVClientError } from 'webdav'

import { t } from '@nextcloud/l10n'

/**
 * Whether error is a WebDAVClientError
 *
 * @param error - Any exception
 * @return - Whether error is a WebDAVClientError
 */
function isWebDAVClientError(error: unknown): error is WebDAVClientError {
	return error instanceof Error && 'status' in error && 'response' in error
}

/**
 * Get a localized error message from webdav request
 *
 * @param error - An exception from webdav request
 * @return Localized error message for end user
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
				return t('files', 'This folder is unavailable, please try again later or contact the administration')
			} else if (status === 503) {
				return t('files', 'Storage is temporarily not available')
			}
		}
		return t('files', 'Unexpected error: {error}', { error: error.message })
	}

	return t('files', 'Unknown error')
}
