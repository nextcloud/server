/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { File, Folder, Node, Permission, View, FileAction } from '@nextcloud/files'
import { describe, expect, test, vi } from 'vitest'
import { action } from './viewInFolderAction'

const view = {
	id: 'trashbin',
	name: 'Trashbin',
} as View

const viewFiles = {
	id: 'files',
	name: 'Files',
} as View

describe('View in folder action conditions tests', () => {
	test('Default values', () => {
		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('view-in-folder')
		expect(action.displayName([], view)).toBe('View in folder')
		expect(action.iconSvgInline([], view)).toMatch(/<svg.+<\/svg>/)
		expect(action.default).toBeUndefined()
		expect(action.order).toBe(80)
		expect(action.enabled).toBeDefined()
	})
})

describe('View in folder action enabled tests', () => {
	test('Enabled for trashbin', () => {
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

	test('Disabled for files', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], viewFiles)).toBe(false)
	})

	test('Disabled without permissions', () => {
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

	test('Disabled for non-dav ressources', () => {
		const file = new File({
			id: 1,
			source: 'https://domain.com/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(false)
	})

	test('Disabled if more than one node', () => {
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

	test('Disabled for folders', () => {
		const folder = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/FooBar/',
			owner: 'admin',
			permissions: Permission.READ,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([folder], view)).toBe(false)
	})

	test('Disabled for files outside the user root folder', () => {
		const file = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/trashbin/admin/trash/image.jpg.d1731053878',
			owner: 'admin',
			permissions: Permission.READ,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(false)
	})
})

describe('View in folder action execute tests', () => {
	test('View in folder', async () => {
		const goToRouteMock = vi.fn()
		// @ts-expect-error We only mock what needed, we do not need Files.Router.goTo or Files.Navigation
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
		})

		const exec = await action.exec(file, view, '/')
		// Silent action
		expect(exec).toBe(null)
		expect(goToRouteMock).toBeCalledTimes(1)
		expect(goToRouteMock).toBeCalledWith(null, { fileid: '1', view: 'files' }, { dir: '/' })
	})

	test('View in (sub) folder', async () => {
		const goToRouteMock = vi.fn()
		// @ts-expect-error We only mock what needed, we do not need Files.Router.goTo or Files.Navigation
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/Bar/foobar.txt',
			root: '/files/admin',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
		})

		const exec = await action.exec(file, view, '/')
		// Silent action
		expect(exec).toBe(null)
		expect(goToRouteMock).toBeCalledTimes(1)
		expect(goToRouteMock).toBeCalledWith(null, { fileid: '1', view: 'files' }, { dir: '/Foo/Bar' })
	})

	test('View in folder fails without node', async () => {
		const goToRouteMock = vi.fn()
		// @ts-expect-error We only mock what needed, we do not need Files.Router.goTo or Files.Navigation
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const exec = await action.exec(null as unknown as Node, view, '/')
		expect(exec).toBe(false)
		expect(goToRouteMock).toBeCalledTimes(0)
	})

	test('View in folder fails without File', async () => {
		const goToRouteMock = vi.fn()
		// @ts-expect-error We only mock what needed, we do not need Files.Router.goTo or Files.Navigation
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const folder = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
			owner: 'admin',
		})

		const exec = await action.exec(folder, view, '/')
		expect(exec).toBe(false)
		expect(goToRouteMock).toBeCalledTimes(0)
	})
})
