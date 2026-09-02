/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 *
 * @param num - The value to check for being numeric
 */
function isNumber(num): boolean {
	if (!num) {
		return false
	}
	return Number(num).toString() === num.toString()
}

export { isNumber }
