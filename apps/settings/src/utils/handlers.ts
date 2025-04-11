/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { AxiosError } from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'

import logger from '../logger.ts'

/**
 * @param error the error
 * @param message the message to display
 */
export function handleError(error: AxiosError, message: string) {
	let fullMessage = ''

	if (message) {
		fullMessage += message
	}

	if (error.response?.status === 429) {
		if (fullMessage) {
			fullMessage += '\n'
		}
		fullMessage += t('settings', 'There were too many requests from your network. Retry later or contact your administrator if this is an error.')
	}

	fullMessage = fullMessage || t('settings', 'Error')
	showError(fullMessage)
	logger.error(fullMessage, { error })
}
