/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Format a date as 'YYYY-MM-DD'.
 *
 * @param {Date} date A date instance to format.
 * @return {string} 'YYYY-MM-DD'
 */
export function formatDateAsYMD(date) {
	const year = date.getFullYear()
	const month = (date.getMonth() + 1).toString().padStart(2, '0')
	const day = date.getDate().toString().padStart(2, '0')
	return `${year}-${month}-${day}`
}
