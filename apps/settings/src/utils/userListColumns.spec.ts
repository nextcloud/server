/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it } from 'vitest'
import {
	clampColumnWidth,
	COLUMN_MAX_WIDTH,
	COLUMN_MIN_WIDTH,
	loadColumnWidths,
	saveColumnWidths,
} from './userListColumns.ts'

const STORAGE_KEY = 'settings:accounts-list:column-widths'

describe('clampColumnWidth', () => {
	it('keeps values within bounds', () => {
		expect(clampColumnWidth(250)).toBe(250)
	})

	it('clamps values below the minimum', () => {
		expect(clampColumnWidth(1)).toBe(COLUMN_MIN_WIDTH)
	})

	it('clamps values above the maximum', () => {
		expect(clampColumnWidth(99999)).toBe(COLUMN_MAX_WIDTH)
	})

	it('rounds fractional values', () => {
		expect(clampColumnWidth(250.4)).toBe(250)
		expect(clampColumnWidth(250.6)).toBe(251)
	})
})

describe('loadColumnWidths and saveColumnWidths', () => {
	beforeEach(() => {
		window.localStorage.clear()
	})

	it('returns an empty object when nothing is stored', () => {
		expect(loadColumnWidths()).toEqual({})
	})

	it('round-trips stored widths', () => {
		saveColumnWidths({ email: 250, groups: 500 })
		expect(loadColumnWidths()).toEqual({ email: 250, groups: 500 })
	})

	it('returns an empty object on invalid JSON', () => {
		window.localStorage.setItem(STORAGE_KEY, 'not json')
		expect(loadColumnWidths()).toEqual({})
	})

	it('drops non numeric values', () => {
		window.localStorage.setItem(STORAGE_KEY, JSON.stringify({ email: 'wide', quota: null, groups: 300 }))
		expect(loadColumnWidths()).toEqual({ groups: 300 })
	})

	it('clamps out of range stored values', () => {
		window.localStorage.setItem(STORAGE_KEY, JSON.stringify({ email: 1, groups: 99999 }))
		expect(loadColumnWidths()).toEqual({ email: COLUMN_MIN_WIDTH, groups: COLUMN_MAX_WIDTH })
	})
})
