/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export const hashCode = function(str: string): number {
	return str.split('').reduce(function(a, b) {
		a = ((a << 5) - a) + b.charCodeAt(0)
		return a & a
	}, 0)
}
