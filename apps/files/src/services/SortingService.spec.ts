/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { describe, expect } from '@jest/globals'
import { orderBy } from './SortingService'

describe('SortingService', () => {
	test('By default the identify and ascending order is used', () => {
		const array = ['a', 'z', 'b']
		expect(orderBy(array)).toEqual(['a', 'b', 'z'])
	})

	test('Use identifiy but descending', () => {
		const array = ['a', 'z', 'b']
		expect(orderBy(array, undefined, ['desc'])).toEqual(['z', 'b', 'a'])
	})

	test('Can set identifier function', () => {
		const array = [
			{ text: 'a', order: 2 },
			{ text: 'z', order: 1 },
			{ text: 'b', order: 3 },
		] as const
		expect(orderBy(array, [(v) => v.order]).map((v) => v.text)).toEqual(['z', 'a', 'b'])
	})

	test('Can set multiple identifier functions', () => {
		const array = [
			{ text: 'a', order: 2, secondOrder: 2 },
			{ text: 'z', order: 1, secondOrder: 3 },
			{ text: 'b', order: 2, secondOrder: 1 },
		] as const
		expect(orderBy(array, [(v) => v.order, (v) => v.secondOrder]).map((v) => v.text)).toEqual(['z', 'b', 'a'])
	})

	test('Can set order partially', () => {
		const array = [
			{ text: 'a', order: 2, secondOrder: 2 },
			{ text: 'z', order: 1, secondOrder: 3 },
			{ text: 'b', order: 2, secondOrder: 1 },
		] as const

		expect(
			orderBy(
				array,
				[(v) => v.order, (v) => v.secondOrder],
				['desc'],
			).map((v) => v.text),
		).toEqual(['b', 'a', 'z'])
	})

	test('Can set order array', () => {
		const array = [
			{ text: 'a', order: 2, secondOrder: 2 },
			{ text: 'z', order: 1, secondOrder: 3 },
			{ text: 'b', order: 2, secondOrder: 1 },
		] as const

		expect(
			orderBy(
				array,
				[(v) => v.order, (v) => v.secondOrder],
				['desc', 'desc'],
			).map((v) => v.text),
		).toEqual(['a', 'b', 'z'])
	})

	test('Numbers are handled correctly', () => {
		const array = [
			{ text: '2.3' },
			{ text: '2.10' },
			{ text: '2.0' },
			{ text: '2.2' },
		] as const

		expect(
			orderBy(
				array,
				[(v) => v.text],
			).map((v) => v.text),
		).toEqual(['2.0', '2.2', '2.3', '2.10'])
	})

	test('Numbers with suffixes are handled correctly', () => {
		const array = [
			{ text: '2024-01-05' },
			{ text: '2024-05-01' },
			{ text: '2024-01-10' },
			{ text: '2024-01-05 Foo' },
		] as const

		expect(
			orderBy(
				array,
				[(v) => v.text],
			).map((v) => v.text),
		).toEqual(['2024-01-05', '2024-01-05 Foo', '2024-01-10', '2024-05-01'])
	})
})
