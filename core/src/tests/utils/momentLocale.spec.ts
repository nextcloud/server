/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import moment from 'moment'
import { afterEach, describe, expect, it } from 'vitest'
import { loadMomentLocale } from '../../utils/momentLocale.ts'

describe('loadMomentLocale', () => {
	afterEach(() => {
		moment.locale('en')
	})

	it('loads the locale on the moment instance used by core', async () => {
		await loadMomentLocale('de_DE')
		expect(moment.locale()).toBe('de')
		expect(moment.utc('2026-11-28T12:00:00Z').format('dddd, D. MMMM YYYY')).toBe('Samstag, 28. November 2026')
	})

	it('prefers the regional variant when moment has it', async () => {
		await loadMomentLocale('en_GB')
		expect(moment.locale()).toBe('en-gb')
	})

	it('maps Nextcloud Chinese locales to moment locales', async () => {
		await loadMomentLocale('zh_Hant_TW')
		expect(moment.locale()).toBe('zh-tw')
	})

	it('falls back to English for unknown locales', async () => {
		await loadMomentLocale('xx_YY')
		expect(moment.locale()).toBe('en')
	})
})
