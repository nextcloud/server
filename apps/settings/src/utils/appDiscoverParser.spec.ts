/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import type { IAppDiscoverElement } from '../constants/AppDiscoverTypes'

import { describe, expect, it } from '@jest/globals'
import { filterElements, parseApiResponse } from './appDiscoverParser'

describe('App Discover API parser', () => {
	describe('filterElements', () => {
		it('can filter expired elements', () => {
			const result = filterElements({ id: 'test', type: 'post', expiryDate: 100 })
			expect(result).toBe(false)
		})

		it('can filter upcoming elements', () => {
			const result = filterElements({ id: 'test', type: 'post', date: Date.now() + 10000 })
			expect(result).toBe(false)
		})

		it('ignores element without dates', () => {
			const result = filterElements({ id: 'test', type: 'post' })
			expect(result).toBe(true)
		})

		it('allows not yet expired elements', () => {
			const result = filterElements({ id: 'test', type: 'post', expiryDate: Date.now() + 10000 })
			expect(result).toBe(true)
		})

		it('allows yet included elements', () => {
			const result = filterElements({ id: 'test', type: 'post', date: 100 })
			expect(result).toBe(true)
		})

		it('allows elements included and not expired', () => {
			const result = filterElements({ id: 'test', type: 'post', date: 100, expiryDate: Date.now() + 10000 })
			expect(result).toBe(true)
		})

		it('can handle null values', () => {
			const result = filterElements({ id: 'test', type: 'post', date: null, expiryDate: null } as unknown as IAppDiscoverElement)
			expect(result).toBe(true)
		})
	})

	describe('parseApiResponse', () => {
		it('can handle basic post', () => {
			const result = parseApiResponse({ id: 'test', type: 'post' })
			expect(result).toEqual({ id: 'test', type: 'post' })
		})

		it('can handle carousel', () => {
			const result = parseApiResponse({ id: 'test', type: 'carousel' })
			expect(result).toEqual({ id: 'test', type: 'carousel' })
		})

		it('can handle showcase', () => {
			const result = parseApiResponse({ id: 'test', type: 'showcase' })
			expect(result).toEqual({ id: 'test', type: 'showcase' })
		})

		it('throws on unknown type', () => {
			expect(() => parseApiResponse({ id: 'test', type: 'foo-bar' })).toThrow()
		})

		it('parses the date', () => {
			const result = parseApiResponse({ id: 'test', type: 'showcase', date: '2024-03-19T17:28:19+0000' })
			expect(result).toEqual({ id: 'test', type: 'showcase', date: 1710869299000 })
		})

		it('parses the expiryDate', () => {
			const result = parseApiResponse({ id: 'test', type: 'showcase', expiryDate: '2024-03-19T17:28:19Z' })
			expect(result).toEqual({ id: 'test', type: 'showcase', expiryDate: 1710869299000 })
		})
	})
})
