/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate as t } from '@nextcloud/l10n'

/**
 * Returns an array
 *
 * @return {object[]}
 */
const getAllClearAtOptions = () => {
	return [{
		label: t('user_status', 'Don\'t clear'),
		clearAt: null,
	}, {
		label: t('user_status', '30 minutes'),
		clearAt: {
			type: 'period',
			time: 1800,
		},
	}, {
		label: t('user_status', '1 hour'),
		clearAt: {
			type: 'period',
			time: 3600,
		},
	}, {
		label: t('user_status', '4 hours'),
		clearAt: {
			type: 'period',
			time: 14400,
		},
	}, {
		label: t('user_status', 'Today'),
		clearAt: {
			type: 'end-of',
			time: 'day',
		},
	}, {
		label: t('user_status', 'This week'),
		clearAt: {
			type: 'end-of',
			time: 'week',
		},
	}]
}

export {
	getAllClearAtOptions,
}
