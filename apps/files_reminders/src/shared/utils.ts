/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCanonicalLocale } from '@nextcloud/l10n'

export enum DateTimePreset {
	LaterToday = 'later-today',
	Tomorrow = 'tomorrow',
	ThisWeekend = 'this-weekend',
	NextWeek = 'next-week',
}

/**
 *
 */
function getFirstWorkdayOfWeek() {
	const now = new Date()
	now.setHours(0, 0, 0, 0)
	now.setDate(now.getDate() - now.getDay() + 1)
	return new Date(now)
}

/**
 *
 * @param date
 */
function getWeek(date: Date) {
	const dateClone = new Date(date)
	dateClone.setHours(0, 0, 0, 0)
	const firstDayOfYear = new Date(date.getFullYear(), 0, 1, 0, 0, 0, 0)
	const daysFromFirstDay = (date.getTime() - firstDayOfYear.getTime()) / 86400000
	return Math.ceil((daysFromFirstDay + firstDayOfYear.getDay() + 1) / 7)
}

/**
 *
 * @param a
 * @param b
 */
function isSameWeek(a: Date, b: Date) {
	return getWeek(a) === getWeek(b)
		&& a.getFullYear() === b.getFullYear()
}

/**
 *
 * @param a
 * @param b
 */
function isSameDate(a: Date, b: Date) {
	return a.getDate() === b.getDate()
		&& a.getMonth() === b.getMonth()
		&& a.getFullYear() === b.getFullYear()
}

/**
 *
 * @param dateTime
 */
export function getDateTime(dateTime: DateTimePreset): null | Date {
	const matchPreset: Record<DateTimePreset, () => null | Date> = {
		[DateTimePreset.LaterToday]: () => {
			const now = new Date()
			const evening = new Date()
			evening.setHours(18, 0, 0, 0)
			const cutoff = new Date()
			cutoff.setHours(17, 0, 0, 0)
			if (now >= cutoff) {
				return null
			}
			return evening
		},

		[DateTimePreset.Tomorrow]: () => {
			const now = new Date()
			const day = new Date()
			day.setDate(now.getDate() + 1)
			day.setHours(8, 0, 0, 0)
			return day
		},

		[DateTimePreset.ThisWeekend]: () => {
			const today = new Date()
			if (
				[
					5, // Friday
					6, // Saturday
					0, // Sunday
				].includes(today.getDay())
			) {
				return null
			}
			const saturday = new Date()
			const firstWorkdayOfWeek = getFirstWorkdayOfWeek()
			saturday.setDate(firstWorkdayOfWeek.getDate() + 5)
			saturday.setHours(8, 0, 0, 0)
			return saturday
		},

		[DateTimePreset.NextWeek]: () => {
			const today = new Date()
			if (today.getDay() === 0) { // Sunday
				return null
			}
			const workday = new Date()
			const firstWorkdayOfWeek = getFirstWorkdayOfWeek()
			workday.setDate(firstWorkdayOfWeek.getDate() + 7)
			workday.setHours(8, 0, 0, 0)
			return workday
		},
	}

	return matchPreset[dateTime]()
}

/**
 *
 */
export function getInitialCustomDueDate(): Date {
	const now = new Date()
	const dueDate = new Date()
	dueDate.setHours(now.getHours() + 2, 0, 0, 0)
	return dueDate
}

/**
 *
 * @param dueDate
 */
export function getDateString(dueDate: Date): string {
	let formatOptions: Intl.DateTimeFormatOptions = {
		hour: 'numeric',
		minute: '2-digit',
	}

	const today = new Date()

	if (!isSameDate(dueDate, today)) {
		formatOptions = {
			...formatOptions,
			weekday: 'short',
		}
	}

	if (!isSameWeek(dueDate, today)) {
		formatOptions = {
			...formatOptions,
			month: 'short',
			day: 'numeric',
		}
	}

	if (dueDate.getFullYear() !== today.getFullYear()) {
		formatOptions = {
			...formatOptions,
			year: 'numeric',
		}
	}

	return dueDate.toLocaleString(
		getCanonicalLocale(),
		formatOptions,
	)
}

/**
 *
 * @param dueDate
 */
export function getVerboseDateString(dueDate: Date): string {
	let formatOptions: Intl.DateTimeFormatOptions = {
		month: 'long',
		day: 'numeric',
		weekday: 'long',
		hour: 'numeric',
		minute: '2-digit',
	}

	const today = new Date()

	if (dueDate.getFullYear() !== today.getFullYear()) {
		formatOptions = {
			...formatOptions,
			year: 'numeric',
		}
	}

	return dueDate.toLocaleString(
		getCanonicalLocale(),
		formatOptions,
	)
}
