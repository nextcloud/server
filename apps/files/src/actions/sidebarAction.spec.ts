/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { File, Permission, View, FileAction, Folder } from '@nextcloud/files'
import { describe, expect, test, vi } from 'vitest'

import { action } from './sidebarAction'
import logger from '../logger'

const view = {
	id: 'files',
	name: 'Files',
} as View

describe('Open sidebar action conditions tests', () => {
	test('Default values', () => {
		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('details')
		expect(action.displayName([], view)).toBe('Open details')
		expect(action.iconSvgInline([], view)).toMatch(/<svg.+<\/svg>/)
		expect(action.default).toBeUndefined()
		expect(action.order).toBe(-50)
	})
})

describe('Open sidebar action enabled tests', () => {
	test('Enabled for ressources within user root folder', () => {
		window.OCA = { Files: { Sidebar: {} } }

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(true)
	})

	test('Disabled without permissions', () => {
		window.OCA = { Files: { Sidebar: {} } }

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.NONE,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(false)

	})

	test('Disabled if more than one node', () => {
		window.OCA = { Files: { Sidebar: {} } }

		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foo.txt',
			owner: 'admin',
			mime: 'text/plain',
		})
		const file2 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/bar.txt',
			owner: 'admin',
			mime: 'text/plain',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file1, file2], view)).toBe(false)
	})

	test('Disabled if no Sidebar', () => {
		window.OCA = {}

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(false)
	})

	test('Disabled for non-dav ressources', () => {
		window.OCA = { Files: { Sidebar: {} } }

		const file = new File({
			id: 1,
			source: 'https://domain.com/documents/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(false)
	})
})

describe('Open sidebar action exec tests', () => {
	test('Open sidebar', async () => {
		const openMock = vi.fn()
		const defaultTabMock = vi.fn()
		window.OCA = { Files: { Sidebar: { open: openMock, setActiveTab: defaultTabMock } } }

		const goToRouteMock = vi.fn()
		// @ts-expect-error We only mock what needed, we do not need Files.Router.goTo or Files.Navigation
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
		})

		const exec = await action.exec(file, view, '/')
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
		window.OCA = { Files: { Sidebar: { open: openMock, setActiveTab: defaultTabMock } } }

		const goToRouteMock = vi.fn()
		// @ts-expect-error We only mock what needed, we do not need Files.Router.goTo or Files.Navigation
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const file = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar',
			owner: 'admin',
			mime: 'httpd/unix-directory',
		})

		const exec = await action.exec(file, view, '/')
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
		const openMock = vi.fn(() => { throw new Error('Mock error') })
		const defaultTabMock = vi.fn()
		window.OCA = { Files: { Sidebar: { open: openMock, setActiveTab: defaultTabMock } } }
		vi.spyOn(logger, 'error').mockImplementation(() => vi.fn())

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
		})

		const exec = await action.exec(file, view, '/')
		expect(exec).toBe(false)
		expect(openMock).toBeCalledTimes(1)
		expect(logger.error).toBeCalledTimes(1)
	})
})
