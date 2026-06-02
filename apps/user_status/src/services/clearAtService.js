/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { formatRelativeTime, getFirstDay, t } from '@nextcloud/l10n'
import { dateFactory } from './dateService.js'

/**
 * Calculates the actual clearAt timestamp
 *
 * @param {object | null} clearAt The clear-at config
 * @return {number | null}
 */
export function getTimestampForClearAt(clearAt) {
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
				return Math.floor(getEndOfDay(date).getTime() / 1000)
			case 'week':
				return Math.floor(getEndOfWeek(date).getTime() / 1000)
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
export function clearAtFormat(clearAt) {
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
		return formatRelativeTime(Date.now() + clearAt.time * 1000)
	}

	// This is not an officially supported type
	// but only used internally to show the remaining time
	// in the Set Status Modal
	if (clearAt.type === '_time') {
		return formatRelativeTime(clearAt.time * 1000)
	}

	return null
}

/**
 * @param {Date} date - The date to calculate the end of the day for
 */
function getEndOfDay(date) {
	const endOfDay = new Date(date)
	endOfDay.setHours(23, 59, 59, 999)
	return endOfDay
}

/**
 * Calculates the end of the week for a given date
 *
 * @param {Date} date - The date to calculate the end of the week for
 */
function getEndOfWeek(date) {
	const endOfWeek = getEndOfDay(date)
	endOfWeek.setDate(date.getDate() + ((getFirstDay() - 1 - endOfWeek.getDay() + 7) % 7))
	return endOfWeek
}
