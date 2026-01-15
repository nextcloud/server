/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IView } from '@nextcloud/files'

import { File, FileAction, Folder, Permission } from '@nextcloud/files'
import { beforeEach, describe, expect, test, vi } from 'vitest'
import logger from '../logger.ts'
import { action } from './sidebarAction.ts'

const sidebar = vi.hoisted(() => ({
	available: true,
	open: vi.fn(),
}))

vi.mock('@nextcloud/files', async (original) => ({
	...(await original()),
	getSidebar: () => sidebar,
}))

const view = {
	id: 'files',
	name: 'Files',
} as IView

beforeEach(() => {
	sidebar.available = true
	vi.clearAllMocks()
})

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
		sidebar.available = false

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
		sidebar.available = true
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

		// Silent action
		expect(await action.exec({
			nodes: [file],
			view,
			folder,
			contents: [],
		})).toBeNull()
		expect(sidebar.open).toBeCalledWith(file, 'sharing')
	})

	test('Open sidebar for folder', async () => {
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
		expect(sidebar.open).toBeCalledWith(file, 'sharing')
	})

	test('Open sidebar fails', async () => {
		sidebar.open.mockImplementationOnce(() => {
			throw new Error('Sidebar error')
		})
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
		expect(sidebar.open).toHaveBeenCalledOnce()
		expect(logger.error).toHaveBeenCalledOnce()
	})
})
