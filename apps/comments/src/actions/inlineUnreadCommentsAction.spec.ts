/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { File, Permission, View, FileAction } from '@nextcloud/files'
import { describe, expect, test, vi } from 'vitest'

import { action } from './inlineUnreadCommentsAction'
import logger from '../logger'

const view = {
	id: 'files',
	name: 'Files',
} as View

describe('Inline unread comments action display name tests', () => {
	test('Default values', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			attributes: {
				'comments-unread': 1,
			},
		})

		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('comments-unread')
		expect(action.displayName([file], view)).toBe('')
		expect(action.title!([file], view)).toBe('1 new comment')
		expect(action.iconSvgInline([], view)).toMatch(/<svg.+<\/svg>/)
		expect(action.enabled!([file], view)).toBe(true)
		expect(action.inline!(file, view)).toBe(true)
		expect(action.default).toBeUndefined()
		expect(action.order).toBe(-140)
	})

	test('Display name when file has two new comments', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			attributes: {
				'comments-unread': 2,
			},
		})

		expect(action.displayName([file], view)).toBe('')
		expect(action.title!([file], view)).toBe('2 new comments')
	})
})

describe('Inline unread comments action enabled tests', () => {
	test('Action is disabled when comments-unread attribute is missing', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			attributes: { },
		})

		expect(action.enabled!([file], view)).toBe(false)
	})

	test('Action is disabled when file does not have unread comments', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			attributes: {
				'comments-unread': 0,
			},
		})

		expect(action.enabled!([file], view)).toBe(false)
	})

	test('Action is enabled when file has a single unread comment', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			attributes: {
				'comments-unread': 1,
			},
		})

		expect(action.enabled!([file], view)).toBe(true)
	})

	test('Action is enabled when file has a two unread comments', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			attributes: {
				'comments-unread': 2,
			},
		})

		expect(action.enabled!([file], view)).toBe(true)
	})
})

describe('Inline unread comments action execute tests', () => {
	test('Action opens sidebar', async () => {
		const openMock = vi.fn()
		const setActiveTabMock = vi.fn()
		window.OCA = {
			Files: {
				Sidebar: {
					open: openMock,
					setActiveTab: setActiveTabMock,
				},
			},
		}

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			attributes: {
				'comments-unread': 1,
			},
		})

		const result = await action.exec!(file, view, '/')

		expect(result).toBe(null)
		expect(setActiveTabMock).toBeCalledWith('comments')
		expect(openMock).toBeCalledWith('/foobar.txt')
	})

	test('Action handles sidebar open failure', async () => {
		const openMock = vi.fn(() => { throw new Error('Mock error') })
		const setActiveTabMock = vi.fn()
		window.OCA = {
			Files: {
				Sidebar: {
					open: openMock,
					setActiveTab: setActiveTabMock,
				},
			},
		}
		vi.spyOn(logger, 'error').mockImplementation(() => vi.fn())

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			attributes: {
				'comments-unread': 1,
			},
		})

		const result = await action.exec!(file, view, '/')

		expect(result).toBe(false)
		expect(setActiveTabMock).toBeCalledWith('comments')
		expect(openMock).toBeCalledWith('/foobar.txt')
		expect(logger.error).toBeCalledTimes(1)
	})
})
