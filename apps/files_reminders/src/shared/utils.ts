/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import moment from '@nextcloud/moment'
import { getCanonicalLocale } from '@nextcloud/l10n'

export enum DateTimePreset {
	LaterToday,
	Tomorrow,
	ThisWeekend,
	NextWeek,
}

export const getDateTime = (dateTime: DateTimePreset): null | Date => {
	const matchPreset: Record<DateTimePreset, () => null | Date> = {
		[DateTimePreset.LaterToday]: () => {
			const now = moment()
			const evening = moment()
				.startOf('day')
				.add(18, 'hour')
			const cutoff = evening
				.clone()
				.subtract(1, 'hour')
			if (now.isSameOrAfter(cutoff)) {
				return null
			}
			return evening.toDate()
		},

		[DateTimePreset.Tomorrow]: () => {
			const day = moment()
				.add(1, 'day')
				.startOf('day')
				.add(8, 'hour')
			return day.toDate()
		},

		[DateTimePreset.ThisWeekend]: () => {
			const today = moment()
			if (
				[
					5, // Friday
					6, // Saturday
					7, // Sunday
				].includes(today.isoWeekday())
			) {
				return null
			}
			const saturday = moment()
				.startOf('isoWeek')
				.add(5, 'day')
				.add(8, 'hour')
			return saturday.toDate()
		},

		[DateTimePreset.NextWeek]: () => {
			const today = moment()
			if (today.isoWeekday() === 7) { // Sunday
				return null
			}
			const workday = moment()
				.startOf('isoWeek')
				.add(1, 'week')
				.add(8, 'hour')
			return workday.toDate()
		},
	}

	return matchPreset[dateTime]()
}

export const getInitialCustomDueDate = (): Date => {
	const hour = moment().get('hour')
	const dueDate = moment()
		.startOf('day')
		.add(hour + 2, 'hour')
	return dueDate.toDate()
}

export const getDateString = (dueDate: Date): string => {
	let formatOptions: Intl.DateTimeFormatOptions = {
		hour: 'numeric',
		minute: '2-digit',
	}

	const dueDateMoment = moment(dueDate)
	const today = moment()

	if (!dueDateMoment.isSame(today, 'date')) {
		formatOptions = {
			...formatOptions,
			weekday: 'short',
		}
	}

	if (!dueDateMoment.isSame(today, 'week')) {
		formatOptions = {
			...formatOptions,
			month: 'short',
			day: 'numeric',
		}
	}

	return dueDate.toLocaleString(
		getCanonicalLocale(),
		formatOptions,
	)
}

export const getVerboseDateString = (dueDate: Date): string => {
	const formatOptions: Intl.DateTimeFormatOptions = {
		weekday: 'long',
		hour: 'numeric',
		minute: '2-digit',
		month: 'long',
		day: 'numeric',
	}

	return dueDate.toLocaleString(
		getCanonicalLocale(),
		formatOptions,
	)
}
