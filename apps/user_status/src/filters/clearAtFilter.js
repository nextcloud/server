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

import { translate as t } from '@nextcloud/l10n'
import moment from '@nextcloud/moment'
import { dateFactory } from '../services/dateService'

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
