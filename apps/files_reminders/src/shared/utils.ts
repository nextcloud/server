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

export const getDateTime = (dateTime: DateTimePreset): Date => {
	const matchPreset = {
		[DateTimePreset.LaterToday]: () => {
			const hour = moment().get('hour')
			const later = moment()
				.startOf('day')
				.add(hour + 3, 'hour')
			return later.toDate()
		},

		[DateTimePreset.Tomorrow]: () => {
			const day = moment()
				.add(1, 'day')
				.startOf('day')
				.add(9, 'hour')
				.toDate()
			return day
		},

		[DateTimePreset.ThisWeekend]: () => {
			const today = moment()
			const weekendFirstDay = moment()
				.startOf('isoWeek')
				.add(5, 'day')
				.add(9, 'hour')
			const weekendSecondDay = moment()
				.startOf('isoWeek')
				.add(6, 'day')
				.add(9, 'hour')
			if (today.isSame(weekendFirstDay, 'date')) {
				return weekendFirstDay
					.add(1, 'day')
					.toDate()
			}
			if (today.isSame(weekendSecondDay, 'date')) {
				return weekendSecondDay
					.add(1, 'week')
					.startOf('isoWeek')
					.add(5, 'day')
					.add(9, 'hour')
					.toDate()
			}
			return weekendFirstDay.toDate()
		},

		[DateTimePreset.NextWeek]: () => {
			const day = moment()
				.startOf('isoWeek')
				.add(1, 'week')
				.add(9, 'hour')
				.toDate()
			return day
		},
	}

	return matchPreset[dateTime]()
}

export const getDateString = (dueDate: Date): string => {
	let localeOptions: Intl.DateTimeFormatOptions = {
		weekday: 'short',
		hour: 'numeric',
		minute: '2-digit',
	}

	const today = moment()
	const dueDateMoment = moment(dueDate)
	if (!dueDateMoment.isSame(today, 'week')) {
		localeOptions = {
			...localeOptions,
			month: 'short',
			day: 'numeric',
		}
	}

	return dueDate.toLocaleString(
		getCanonicalLocale(),
		localeOptions,
	)
}

export const getVerboseDateString = (dueDate: Date): string => {
	const localeOptions: Intl.DateTimeFormatOptions = {
		weekday: 'long',
		hour: 'numeric',
		minute: '2-digit',
		month: 'long',
		day: 'numeric',
	}

	return dueDate.toLocaleString(
		getCanonicalLocale(),
		localeOptions,
	)
}
