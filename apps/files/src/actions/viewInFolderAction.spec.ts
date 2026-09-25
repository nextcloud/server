/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IView } from '@nextcloud/files'

import { File, Folder, Permission } from '@nextcloud/files'
import { describe, expect, test, vi } from 'vitest'
import { action } from './viewInFolderAction.ts'

const view = {
	id: 'trashbin',
	name: 'Trashbin',
} as IView

const viewFiles = {
	id: 'files',
	name: 'Files',
} as IView

const viewSearch = {
	id: 'search',
	name: 'Search',
} as IView

const rootFolder = new Folder({
	id: 10,
	source: 'https://cloud.domain.com/remote.php/dav/files/admin/',
	owner: 'admin',
	permissions: Permission.ALL,
	root: '/files/admin',
})

const nestedFolder = new Folder({
	id: 11,
	source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/Bar/',
	owner: 'admin',
	permissions: Permission.ALL,
	root: '/files/admin',
})

describe('View in folder action conditions tests', () => {
	test('Default values', () => {
		expect(action.id).toBe('view-in-folder')
		expect(action.displayName({
			nodes: [],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe('View in folder')
		expect(action.iconSvgInline({
			nodes: [],
			view,
			folder: {} as Folder,
			contents: [],
		})).toMatch(/<svg.+<\/svg>/)
		expect(action.default).toBeUndefined()
		expect(action.order).toBe(10)
		expect(action.enabled).toBeDefined()
	})
})

describe('View in folder action enabled tests', () => {
	test('Enabled when search result is shown outside its parent folder', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/Bar/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			root: '/files/admin',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [file],
			view: viewSearch,
			folder: rootFolder,
			contents: [],
		})).toBe(true)
	})

	test('Disabled when file is already shown in its parent folder', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/Bar/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			root: '/files/admin',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [file],
			view: viewFiles,
			folder: nestedFolder,
			contents: [],
		})).toBe(false)
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
			folder: nestedFolder,
			contents: [],
		})).toBe(false)
	})

	test('Disabled for non-dav ressources', () => {
		const file = new File({
			id: 1,
			source: 'https://domain.com/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			root: '/',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [file],
			view,
			folder: nestedFolder,
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
			folder: nestedFolder,
			contents: [],
		})).toBe(false)
	})

	test('Disabled for folders', () => {
		const folder = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/FooBar/',
			owner: 'admin',
			permissions: Permission.READ,
			root: '/files/admin',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [folder],
			view,
			folder: rootFolder,
			contents: [],
		})).toBe(false)
	})

	test('Disabled for files outside the user root folder', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/trashbin/admin/trash/image.jpg.d1731053878',
			owner: 'admin',
			mime: 'image/jpeg',
			permissions: Permission.READ,
			root: '/trashbin/admin',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [file],
			view,
			folder: rootFolder,
			contents: [],
		})).toBe(false)
	})
})

describe('View in folder action execute tests', () => {
	test('View in folder', async () => {
		const goToRouteMock = vi.fn()
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
			root: '/files/admin',
		})

		const exec = await action.exec({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})
		// Silent action
		expect(exec).toBe(null)
		expect(goToRouteMock).toBeCalledTimes(1)
		expect(goToRouteMock).toBeCalledWith(null, { fileid: '1', view: 'files' }, { dir: '/' })
	})

	test('View in (sub) folder', async () => {
		const goToRouteMock = vi.fn()
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/Bar/foobar.txt',
			root: '/files/admin',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
		})

		const exec = await action.exec({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})
		// Silent action
		expect(exec).toBe(null)
		expect(goToRouteMock).toBeCalledTimes(1)
		expect(goToRouteMock).toBeCalledWith(null, { fileid: '1', view: 'files' }, { dir: '/Foo/Bar' })
	})

	test('View in folder fails without node', async () => {
		const goToRouteMock = vi.fn()
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const exec = await action.exec({
			// @ts-expect-error We want to test without node
			nodes: [],
			view,
			folder: {} as Folder,
			contents: [],
		})
		expect(exec).toBe(false)
		expect(goToRouteMock).toBeCalledTimes(0)
	})

	test('View in folder fails without File', async () => {
		const goToRouteMock = vi.fn()
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const folder = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
			owner: 'admin',
			root: '/files/admin',
		})

		const exec = await action.exec({
			nodes: [folder],
			view,
			folder: {} as Folder,
			contents: [],
		})
		expect(exec).toBe(false)
		expect(goToRouteMock).toBeCalledTimes(0)
	})
})
