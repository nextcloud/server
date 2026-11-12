/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { isAxiosError } from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import logger from '../logger.ts'

/**
 * @param error the error
 * @param message the message to display
 */
export function handleError(error: unknown, message: string) {
	let fullMessage = ''

	if (message) {
		fullMessage += message
	}

	if (isAxiosError(error) && error.response?.status === 429) {
		if (fullMessage) {
			fullMessage += '\n'
		}
		fullMessage += t('settings', 'There were too many requests from your network. Retry later or contact your administrator if this is an error.')
	}

	fullMessage = fullMessage || t('settings', 'Error')
	showError(fullMessage)
	logger.error(fullMessage, { error })
}

/**
 * @param response Response of an OCS request
 * @param response.data The response body
 * @return The response body
 * @throws {Error} If the server did not report success
 */
export function ocsData<T extends { ocs?: { meta?: { status?: string } } }>(response: { data: T }): T {
	if (response.data.ocs?.meta?.status !== 'ok') {
		throw new Error('Unexpected response')
	}
	return response.data
}
