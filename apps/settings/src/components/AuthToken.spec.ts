/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it } from 'vitest'
import { detect } from '../utils/userAgentDetect.ts'

describe('Android Chrome detection', () => {
	it('modern Android Chrome (no Build/ string, post-2021) should match androidChrome', () => {
		const ua = 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Mobile Safari/537.36'
		expect(detect(ua)).toEqual({
			id: 'androidChrome',
			version: '132',
		})
	})

	it('legacy Android Chrome (with Build/ string, pre-2021) should match androidChrome', () => {
		const ua = 'Mozilla/5.0 (Linux; Android 10; SM-G973F Build/QP1A) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36'
		expect(detect(ua)).toEqual({
			id: 'androidChrome',
			version: '130',
		})
	})

	it('Android Chrome on tablet (no "Mobile" in UA) should match androidChrome', () => {
		const ua = 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
		expect(detect(ua)).toEqual({
			id: 'androidChrome',
			version: '131',
		})
	})
})

describe('Desktop Chrome regression tests', () => {
	it('Desktop Chrome on Linux should still match chrome', () => {
		const ua = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36'
		expect(detect(ua)).toEqual({
			id: 'chrome',
			version: '132',
			os: 'Linux',
		})
	})
})

describe('Desktop Firefox regression tests', () => {
	it('Desktop Firefox on Linux should still match firefox', () => {
		const ua = 'Mozilla/5.0 (X11; Linux x86_64; rv:124.0) Gecko/20100101 Firefox/124.0'
		expect(detect(ua)).toEqual({
			id: 'firefox',
			version: '124',
			os: 'Linux',
		})
	})
})
