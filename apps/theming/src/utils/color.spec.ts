/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from 'vitest'
import { getTextColor } from './color.ts'

test('getTextColor returns black for light backgrounds', () => {
	expect(getTextColor('#FFFFFF')).toBe('#000000') // white background
	expect(getTextColor('#DDDDDD')).toBe('#000000') // light gray background
	expect(getTextColor('#FFFFAA')).toBe('#000000') // light yellow background
})

test('getTextColor returns white for dark backgrounds', () => {
	expect(getTextColor('#000000')).toBe('#ffffff') // black background
	expect(getTextColor('#333333')).toBe('#ffffff') // dark gray background
	expect(getTextColor('#0000AA')).toBe('#ffffff') // dark blue background
})

test('getTextColor handles edge cases', () => {
	expect(getTextColor('#808080')).toBe('#ffffff') // medium gray background
	expect(getTextColor('#C0C0C0')).toBe('#000000') // silver background
	expect(getTextColor('#404040')).toBe('#ffffff') // dark gray background
})

test('getTextColor handles shorthand hex colors', () => {
	expect(getTextColor('#FFF')).toBe('#000000') // white background
	expect(getTextColor('#000')).toBe('#ffffff') // black background
	expect(getTextColor('#888')).toBe('#ffffff') // medium gray background
})

test('getTextColor handles invalid hex colors', () => {
	expect(getTextColor('invalid')).toBe('#ffffff')
	expect(getTextColor('#GG')).toBe('#ffffff')
})
