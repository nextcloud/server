/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { defaultRemoteURL } from '@nextcloud/files/dav'
import { t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'

import { logger } from '../logger.ts'

/**
 * Send API request to empty the trashbin.
 * Returns true if request succeeded - otherwise false is returned.
 */
export async function emptyTrash(): Promise<boolean> {
	try {
		await axios.delete(`${defaultRemoteURL}/trashbin/${getCurrentUser()!.uid}/trash`)
		showSuccess(t('files_trashbin', 'All files have been permanently deleted'))
		return true
	} catch (error) {
		showError(t('files_trashbin', 'Failed to empty deleted files'))
		logger.error('Failed to empty deleted files', { error })
		return false
	}
}
