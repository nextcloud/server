/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showInfo } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'

interface IUpdateNotificationState {
	updateLink: string
	updateVersion: string
}

/**
 * This only gets loaded if an update is available and the notifications app is not enabled for the user.
 */
window.addEventListener('DOMContentLoaded', function() {
	const { updateLink, updateVersion } = loadState<IUpdateNotificationState>('updatenotification', 'updateState')
	const text = t('core', '{version} is available. Get more information on how to update.', { version: updateVersion })

	// On click open the update link in a new tab
	showInfo(text, { onClick: () => window.open(updateLink, '_blank') })
})
