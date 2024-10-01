/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate as t } from '@nextcloud/l10n'

/**
 * Returns a list of all user-definable statuses
 *
 * @return {object[]}
 */
const getAllStatusOptions = () => {
	return [{
		type: 'online',
		label: t('user_status', 'Online'),
	}, {
		type: 'away',
		label: t('user_status', 'Away'),
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
