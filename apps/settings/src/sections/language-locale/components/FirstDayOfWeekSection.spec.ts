/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type * as L10n from '@nextcloud/l10n'

import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'

const l10n = vi.hoisted(() => ({
	getCanonicalLocale: vi.fn(() => 'en-US'),
	getFirstDay: vi.fn(() => 0),
}))
vi.mock('@nextcloud/l10n', async (importOriginal) => ({
	...await importOriginal<typeof L10n>(),
	...l10n,
}))

import { t } from '@nextcloud/l10n'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import FirstDayOfWeekSection from './FirstDayOfWeekSection.vue'

function localeOptionLabel(): string {
	const wrapper = mount(FirstDayOfWeekSection, { global: { mocks: { t } } })
	return wrapper.findComponent(NcSelect).props('options')[0].label
}

describe('FirstDayOfWeekSection', () => {
	afterEach(() => {
		vi.resetAllMocks()
	})

	it.each([
		['en-US', 'Sunday'],
		['de-DE', 'Monday'],
		['ar-EG', 'Saturday'],
	])('names the first day of the %s locale', (locale, day) => {
		l10n.getCanonicalLocale.mockReturnValue(locale)

		expect(localeOptionLabel()).toBe(`Derived from your locale (${day})`)
	})

	it('reads the older weekInfo getter without getWeekInfo', () => {
		const prototype = Intl.Locale.prototype as Intl.Locale & { getWeekInfo?: unknown }
		const getWeekInfo = Object.getOwnPropertyDescriptor(prototype, 'getWeekInfo')!
		delete prototype.getWeekInfo
		l10n.getCanonicalLocale.mockReturnValue('de-DE')
		l10n.getFirstDay.mockReturnValue(3)

		try {
			expect(localeOptionLabel()).toBe('Derived from your locale (Monday)')
		} finally {
			Object.defineProperty(prototype, 'getWeekInfo', getWeekInfo)
		}
	})

	it('falls back to the first day in use without Intl week info', () => {
		const prototype = Intl.Locale.prototype as Intl.Locale & { getWeekInfo?: unknown, weekInfo?: unknown }
		const getWeekInfo = Object.getOwnPropertyDescriptor(prototype, 'getWeekInfo')!
		const weekInfo = Object.getOwnPropertyDescriptor(prototype, 'weekInfo')!
		delete prototype.getWeekInfo
		delete prototype.weekInfo
		l10n.getCanonicalLocale.mockReturnValue('de-DE')
		l10n.getFirstDay.mockReturnValue(3)

		try {
			expect(localeOptionLabel()).toBe('Derived from your locale (Wednesday)')
		} finally {
			Object.defineProperty(prototype, 'getWeekInfo', getWeekInfo)
			Object.defineProperty(prototype, 'weekInfo', weekInfo)
		}
	})
})
