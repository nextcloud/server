/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * The time zones to offer.
 *
 * `Intl` lists the canonical zones only, which leaves out `UTC` — a zone rules
 * were able to pick before and that saved rules still carry.
 */
export function listTimezones(): string[] {
	return ['UTC', ...Intl.supportedValuesOf('timeZone')]
}

/**
 * The time zone of this browser, used for a check that has none yet.
 */
export function currentTimezone(): string {
	return Intl.DateTimeFormat().resolvedOptions().timeZone
}

/**
 * Whether a time zone can be resolved.
 *
 * Rules saved earlier may name a zone that is no longer offered, for instance a
 * legacy alias such as `Europe/Kiev`. Those still resolve, so a saved rule does
 * not turn invalid just because its zone left the list.
 *
 * @param timezone - The time zone to check
 */
export function isKnownTimezone(timezone: string | null): boolean {
	if (!timezone) {
		return false
	}
	try {
		// throws a RangeError for a zone that cannot be resolved
		new Intl.DateTimeFormat(undefined, { timeZone: timezone })
		return true
	} catch {
		return false
	}
}
