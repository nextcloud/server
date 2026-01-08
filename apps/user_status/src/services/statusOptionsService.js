/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'

/**
 * Returns a list of all user-definable statuses
 *
 * @return {object[]}
 */
function getAllStatusOptions() {
	return [{
		type: 'online',
		label: t('user_status', 'Online'),
	}, {
		type: 'away',
		label: t('user_status', 'Away'),
	}, {
		type: 'busy',
		label: t('user_status', 'Busy'),
	}, {
		type: 'dnd',
		label: t('user_status', 'Do not disturb'),
		subline: t('user_status', 'Mute all notifications'),
	}, {
		type: 'invisible',
		label: t('user_status', 'Invisible'),
		subline: t('user_status', 'Appear offline'),
	}]
}

export {
	getAllStatusOptions,
}
