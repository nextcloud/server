/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it } from 'vitest'
import { contrastingTextColor, DEFAULT_TEXT_COLOR, iconFilterFor, knownTextColor } from './contrast.ts'

describe('knownTextColor', () => {
	it('reads a card without a colour as ordinary text', () => {
		expect(knownTextColor('transparent')).toBe(DEFAULT_TEXT_COLOR)
	})

	it('pairs the primary element with its own text colour', () => {
		expect(knownTextColor('var(--color-primary-element)')).toBe('var(--color-primary-element-text)')
	})

	it('leaves a colour only the browser can resolve undecided', () => {
		expect(knownTextColor('var(--color-warning)')).toBeNull()
		expect(knownTextColor('#ff5900')).toBeNull()
	})
})

describe('contrastingTextColor', () => {
	it('puts white on a background dark enough to need it', () => {
		expect(contrastingTextColor('#000000')).toBe('#ffffff')
		expect(contrastingTextColor('#1a1a1a')).toBe('#ffffff')
	})

	it('puts black on everything that white would not read on', () => {
		expect(contrastingTextColor('#ffffff')).toBe('#000000')
		// the colour the files versions app registers its operation with
		expect(contrastingTextColor('#ff5900')).toBe('#000000')
		// a mid tone: white only wins above a contrast of 4.5
		expect(contrastingTextColor('#0082c9')).toBe('#000000')
	})

	it('accepts a resolved rgb value', () => {
		expect(contrastingTextColor('rgb(255, 89, 0)')).toBe('#000000')
	})

	it('falls back to ordinary text for a value it cannot read', () => {
		expect(contrastingTextColor('not a colour')).toBe(DEFAULT_TEXT_COLOR)
		expect(contrastingTextColor('')).toBe(DEFAULT_TEXT_COLOR)
	})
})

describe('iconFilterFor', () => {
	it('inverts a dark icon so it stays visible on black text', () => {
		expect(iconFilterFor('#000000')).toBe('invert(100%)')
	})

	it('leaves the icon alone otherwise', () => {
		expect(iconFilterFor('#ffffff')).toBe('none')
		expect(iconFilterFor(DEFAULT_TEXT_COLOR)).toBe('none')
	})
})
