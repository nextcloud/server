/**
 * @copyright Copyright (c) 2020 Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import {
	dateFactory,
} from './dateService'
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
