/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {
	dateFactory,
} from './dateService.js'
import moment from '@nextcloud/moment'

/**
 * Calculates the actual clearAt timestamp
 *
 * @param {object | null} clearAt The clear-at config
 * @return {number | null}
 */
const getTimestampForClearAt = (clearAt) => {
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

export {
	getTimestampForClearAt,
}
