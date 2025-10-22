/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Format a date as 'YYYY-MM-DD'.
 *
 * @param date - A date instance to format.
 */
export function formatDateAsYMD(date: Date): `${number}-${number}-${number}` {
	const year = date.getFullYear()
	const month = (date.getMonth() + 1).toString().padStart(2, '0') as `${number}`
	const day = date.getDate().toString().padStart(2, '0') as `${number}`
	return `${year}-${month}-${day}`
}
