/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'
import moment from '@nextcloud/moment'
import { dateFactory } from './dateService.js'

/**
 * Calculates the actual clearAt timestamp
 *
 * @param {object | null} clearAt The clear-at config
 * @return {number | null}
 */
function getTimestampForClearAt(clearAt) {
	if (clearAt === null) {
		return null
	}

	const date = dateFactory()

	if (clearAt.type === 'period') {
		date.setSeconds(date.getSeconds() + clearAt.time)
		return Math.floor(date.getTime() / 1000)
	}
	if (clearAt.type === 'end-of') {
		switch (clearAt.time) {
			case 'day':
			case 'week':
				return Number(moment(date).endOf(clearAt.time).format('X'))
		}
	}
	// This is not an officially supported type
	// but only used internally to show the remaining time
	// in the Set Status Modal
	if (clearAt.type === '_time') {
		return clearAt.time
	}

	return null
}

/**
 * Formats a clearAt object to be human readable
 *
 * @param {object} clearAt The clearAt object
 * @return {string|null}
 */
function clearAtFormat(clearAt) {
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
	clearAtFormat,
	getTimestampForClearAt,
}
