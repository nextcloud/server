/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it } from 'vitest'
import { currentTimezone, isKnownTimezone, listTimezones } from './timezones.ts'

describe('listTimezones', () => {
	it('offers the zones of the browser', () => {
		const zones = listTimezones()
		expect(zones.length).toBeGreaterThan(100)
		expect(zones).toContain('Europe/Berlin')
	})

	it('offers UTC, which Intl leaves out of its canonical list', () => {
		expect(Intl.supportedValuesOf('timeZone')).not.toContain('UTC')
		expect(listTimezones()).toContain('UTC')
	})
})

describe('currentTimezone', () => {
	it('is a zone that can be resolved', () => {
		expect(isKnownTimezone(currentTimezone())).toBe(true)
	})
})

describe('isKnownTimezone', () => {
	it('accepts an offered zone', () => {
		expect(isKnownTimezone('Europe/Berlin')).toBe(true)
	})

	it('accepts a legacy alias, so a saved rule stays valid', () => {
		expect(isKnownTimezone('Europe/Kiev')).toBe(true)
	})

	it('rejects a zone that cannot be resolved', () => {
		expect(isKnownTimezone('Mars/Olympus_Mons')).toBe(false)
	})

	it('rejects a missing zone', () => {
		expect(isKnownTimezone('')).toBe(false)
		expect(isKnownTimezone(null)).toBe(false)
	})
})
