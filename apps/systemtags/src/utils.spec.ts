/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { DAVResultResponseProps } from 'webdav'
import type { ServerTag, Tag } from './types.js'
import { describe, expect, it } from 'vitest'

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
