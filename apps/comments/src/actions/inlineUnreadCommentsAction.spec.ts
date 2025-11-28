/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Folder, View } from '@nextcloud/files'

import { File, FileAction, Permission } from '@nextcloud/files'
import { describe, expect, test, vi } from 'vitest'
import logger from '../logger.js'
import { action } from './inlineUnreadCommentsAction.ts'

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
			root: '/files/admin',
		})

		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('comments-unread')
		expect(action.displayName({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe('')
		expect(action.title!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe('1 new comment')
		expect(action.iconSvgInline({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toMatch(/<svg.+<\/svg>/)
		expect(action.enabled!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(true)
		expect(action.inline!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(true)
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
			root: '/files/admin',
		})

		expect(action.displayName({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe('')
		expect(action.title!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe('2 new comments')
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
			attributes: {},
			root: '/files/admin',
		})

		expect(action.enabled!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
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
			root: '/files/admin',
		})

		expect(action.enabled!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
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
			root: '/files/admin',
		})

		expect(action.enabled!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(true)
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
			root: '/files/admin',
		})

		expect(action.enabled!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(true)
	})
})

describe('Inline unread comments action execute tests', () => {
	test('Action opens sidebar', async () => {
		const openMock = vi.fn()
		const setActiveTabMock = vi.fn()
		window.OCA = {
			Files: {
				// @ts-expect-error Mocking for testing
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
			root: '/files/admin',
		})

		const result = await action.exec!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})

		expect(result).toBe(null)
		expect(setActiveTabMock).toBeCalledWith('comments')
		expect(openMock).toBeCalledWith('/foobar.txt')
	})

	test('Action handles sidebar open failure', async () => {
		const openMock = vi.fn(() => {
			throw new Error('Mock error')
		})
		const setActiveTabMock = vi.fn()
		window.OCA = {
			Files: {
				// @ts-expect-error Mocking for testing
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
			root: '/files/admin',
		})

		const result = await action.exec!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})

		expect(result).toBe(false)
		expect(setActiveTabMock).toBeCalledWith('comments')
		expect(openMock).toBeCalledWith('/foobar.txt')
		expect(logger.error).toBeCalledTimes(1)
	})
})
