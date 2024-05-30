/**
 * @copyright 2023 Ferdinand Thiessen <opensource@fthiessen.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import type { DAVResultResponseProps } from 'webdav'
import type { ServerTag, Tag } from './types.js'

import { describe, it, expect } from '@jest/globals'
import { formatTag, parseIdFromLocation, parseTags } from './utils'

describe('systemtags - utils', () => {
	describe('parseTags', () => {
		it('renames properties', () => {
			const tags = parseTags([{
				props: {
					displayname: 'display-name',
					resourcetype: {
						collection: false,
					},
					'user-visible': true,
					'user-assignable': true,
					'can-assign': true,
				} as DAVResultResponseProps,
			}])

			expect(tags).toEqual([
				{
					displayname: 'display-name',
					resourcetype: {
						collection: false,
					},
					userVisible: true,
					userAssignable: true,
					canAssign: true,
				},
			])
		})
	})

	describe('parseIdFromLocation', () => {
		it('works with simple url', () => {
			const url = 'http://some.domain/remote.php/dav/3'
			expect(parseIdFromLocation(url)).toEqual(3)
		})
		it('works with trailing slash', () => {
			const url = 'http://some.domain/remote.php/dav/3/'
			expect(parseIdFromLocation(url)).toEqual(3)
		})
		it('works with query', () => {
			const url = 'http://some.domain/remote.php/dav/3?some-value'
			expect(parseIdFromLocation(url)).toEqual(3)
		})
	})

	describe('formatTag', () => {
		it('handles tags', () => {
			const tag: Tag = {
				canAssign: true,
				displayName: 'DisplayName',
				userAssignable: true,
				userVisible: true,
			}

			expect(formatTag(tag)).toEqual({
				canAssign: true,
				name: 'DisplayName',
				userAssignable: true,
				userVisible: true,
			})
		})
		it('handles server tags', () => {
			const tag: ServerTag = {
				canAssign: true,
				name: 'DisplayName',
				userAssignable: true,
				userVisible: true,
			}

			expect(formatTag(tag)).toEqual({
				canAssign: true,
				name: 'DisplayName',
				userAssignable: true,
				userVisible: true,
			})
		})
	})
})
