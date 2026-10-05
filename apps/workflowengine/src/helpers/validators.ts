/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Check } from '../types.ts'

const regexRegex = /^\/(.*)\/([gui]{0,3})$/
const regexIPv4 = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\/(3[0-2]|[1-2][0-9]|[1-9])$/
const regexIPv6 = /^(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))\/(1([01][0-9]|2[0-8])|[1-9][0-9]|[0-9])$/

/**
 * Whether a string is a delimited regular expression, e.g. `/^a.*$/i`.
 *
 * @param value - The string to check
 */
export function validateRegex(value: string): boolean {
	if (!value) {
		return false
	}
	return regexRegex.exec(value) !== null
}

/**
 * Whether a string is an IPv4 range in CIDR notation, e.g. `127.0.0.1/32`.
 *
 * @param value - The string to check
 */
export function validateIPv4(value: string): boolean {
	if (!value) {
		return false
	}
	return regexIPv4.exec(value) !== null
}

/**
 * Whether a string is an IPv6 range in CIDR notation, e.g. `::1/128`.
 *
 * @param value - The string to check
 */
export function validateIPv6(value: string): boolean {
	if (!value) {
		return false
	}
	return regexIPv6.exec(value) !== null
}

/**
 * Whether the value of a check is usable with its comparison. Only the
 * regular expression comparisons constrain the value.
 *
 * @param check - The check to validate
 */
export function stringValidator(check: Check): boolean {
	if (check.operator === 'matches' || check.operator === '!matches') {
		return validateRegex(check.value)
	}
	return true
}
