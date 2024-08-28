/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate as t } from '@nextcloud/l10n'
import moment from '@nextcloud/moment'
import { dateFactory } from '../services/dateService.js'

/**
 * Formats a clearAt object to be human readable
 *
 * @param {object} clearAt The clearAt object
 * @return {string|null}
 */
const clearAtFilter = (clearAt) => {
	if (clearAt === null) {
		return t('user_status', 'Don\'t clear')
	}

	if (clearAt.type === 'end-of') {
		switch (clearAt.time) {
		case 'day':
			return t('user_status', 'Today')
		case 'week':
			return t('user_status', 'This week')

		default:
			return null
		}
	}

	if (clearAt.type === 'period') {
		return moment.duration(clearAt.time * 1000).humanize()
	}

	// This is not an officially supported type
	// but only used internally to show the remaining time
	// in the Set Status Modal
	if (clearAt.type === '_time') {
		const momentNow = moment(dateFactory())
		const momentClearAt = moment(clearAt.time, 'X')

		return moment.duration(momentNow.diff(momentClearAt)).humanize()
	}

	return null
}

export {
	clearAtFilter,
}
