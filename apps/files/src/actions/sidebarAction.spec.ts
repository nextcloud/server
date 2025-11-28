/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { View } from '@nextcloud/files'

import { File, FileAction, Folder, Permission } from '@nextcloud/files'
import { describe, expect, test, vi } from 'vitest'
import logger from '../logger.ts'
import { action } from './sidebarAction.ts'

const view = {
	id: 'files',
	name: 'Files',
} as View

describe('Open sidebar action conditions tests', () => {
	test('Default values', () => {
		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('details')
		expect(action.displayName({
			nodes: [],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe('Details')
		expect(action.iconSvgInline({
			nodes: [],
			view,
			folder: {} as Folder,
			contents: [],
		})).toMatch(/<svg.+<\/svg>/)
		expect(action.default).toBeUndefined()
		expect(action.order).toBe(-50)
	})
})

describe('Open sidebar action enabled tests', () => {
	test('Enabled for ressources within user root folder', () => {
		// @ts-expect-error mocking for tests
		window.OCA = { Files: { Sidebar: {} } }

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			root: '/files/admin',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(true)
	})

	test('Disabled without permissions', () => {
		// @ts-expect-error mocking for tests
		window.OCA = { Files: { Sidebar: {} } }

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.NONE,
			root: '/files/admin',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})

	test('Disabled if more than one node', () => {
		// @ts-expect-error mocking for tests
		window.OCA = { Files: { Sidebar: {} } }

		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foo.txt',
			owner: 'admin',
			mime: 'text/plain',
			root: '/files/admin',
		})
		const file2 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/bar.txt',
			owner: 'admin',
			mime: 'text/plain',
			root: '/files/admin',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [file1, file2],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})

	test('Disabled if no Sidebar', () => {
		// @ts-expect-error mocking for tests
		window.OCA = {}

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			root: '/files/admin',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})

	test('Disabled for non-dav ressources', () => {
		// @ts-expect-error mocking for tests
		window.OCA = { Files: { Sidebar: {} } }

		const file = new File({
			id: 1,
			source: 'https://domain.com/documents/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			root: '/documents/admin',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})
})

describe('Open sidebar action exec tests', () => {
	test('Open sidebar', async () => {
		const openMock = vi.fn()
		const defaultTabMock = vi.fn()
		// @ts-expect-error mocking for tests
		window.OCA = { Files: { Sidebar: { open: openMock, setActiveTab: defaultTabMock } } }

		const goToRouteMock = vi.fn()
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			root: '/files/admin',
		})

		const folder = new Folder({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/',
			owner: 'admin',
			root: '/files/admin',
		})

		const exec = await action.exec({
			nodes: [file],
			view,
			folder,
			contents: [],
		})
		// Silent action
		expect(exec).toBe(null)
		expect(openMock).toBeCalledWith('/foobar.txt')
		expect(defaultTabMock).toBeCalledWith('sharing')
		expect(goToRouteMock).toBeCalledWith(
			null,
			{ view: view.id, fileid: '1' },
			{ dir: '/', opendetails: 'true' },
			true,
		)
	})

	test('Open sidebar for folder', async () => {
		const openMock = vi.fn()
		const defaultTabMock = vi.fn()
		// @ts-expect-error mocking for tests
		window.OCA = { Files: { Sidebar: { open: openMock, setActiveTab: defaultTabMock } } }

		const goToRouteMock = vi.fn()
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const file = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar',
			owner: 'admin',
			mime: 'httpd/unix-directory',
			root: '/files/admin',
		})

		const folder = new Folder({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/',
			owner: 'admin',
			root: '/files/admin',
		})

		const exec = await action.exec({
			nodes: [file],
			view,
			folder,
			contents: [],
		})
		// Silent action
		expect(exec).toBe(null)
		expect(openMock).toBeCalledWith('/foobar')
		expect(defaultTabMock).toBeCalledWith('sharing')
		expect(goToRouteMock).toBeCalledWith(
			null,
			{ view: view.id, fileid: '1' },
			{ dir: '/', opendetails: 'true' },
			true,
		)
	})

	test('Open sidebar fails', async () => {
		const openMock = vi.fn(() => {
			throw new Error('Mock error')
		})
		const defaultTabMock = vi.fn()
		// @ts-expect-error mocking for tests
		window.OCA = { Files: { Sidebar: { open: openMock, setActiveTab: defaultTabMock } } }
		vi.spyOn(logger, 'error').mockImplementation(() => vi.fn())

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			root: '/files/admin',
		})

		const exec = await action.exec({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})
		expect(exec).toBe(false)
		expect(openMock).toBeCalledTimes(1)
		expect(logger.error).toBeCalledTimes(1)
	})
})
