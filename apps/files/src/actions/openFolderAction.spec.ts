/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { View } from '@nextcloud/files'

import { DefaultType, File, FileAction, Folder, Permission } from '@nextcloud/files'
import { describe, expect, test, vi } from 'vitest'
import { action } from './openFolderAction.ts'

const view = {
	id: 'files',
	name: 'Files',
} as View

describe('Open folder action conditions tests', () => {
	test('Default values', () => {
		const folder = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/FooBar/',
			owner: 'admin',
			permissions: Permission.READ,
			root: '/files/admin',
		})

		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('open-folder')
		expect(action.displayName({
			nodes: [folder],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe('Open folder FooBar')
		expect(action.iconSvgInline({
			nodes: [folder],
			view,
			folder: {} as Folder,
			contents: [],
		})).toMatch(/<svg.+<\/svg>/)
		expect(action.default).toBe(DefaultType.HIDDEN)
		expect(action.order).toBe(-100)
	})
})

describe('Open folder action enabled tests', () => {
	test('Enabled for folders', () => {
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
			folder: {} as Folder,
			contents: [],
		})).toBe(true)
	})

	test('Disabled for non-dav ressources', () => {
		const folder = new Folder({
			id: 1,
			source: 'https://domain.com/data/FooBar/',
			owner: 'admin',
			permissions: Permission.NONE,
			root: '/',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [folder],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})

	test('Disabled if more than one node', () => {
		const folder1 = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
			owner: 'admin',
			permissions: Permission.READ,
			root: '/files/admin',
		})
		const folder2 = new Folder({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Bar/',
			owner: 'admin',
			permissions: Permission.READ,
			root: '/files/admin',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [folder1, folder2],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})

	test('Disabled for files', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
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

	test('Disabled without READ permissions', () => {
		const folder = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
			owner: 'admin',
			permissions: Permission.NONE,
			root: '/files/admin',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [folder],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})
})

describe('Open folder action execute tests', () => {
	test('Open folder', async () => {
		const goToRouteMock = vi.fn()
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const folder = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/FooBar/',
			owner: 'admin',
			permissions: Permission.READ,
			root: '/files/admin',
		})

		const root = new Folder({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/',
			owner: 'admin',
			root: '/files/admin',
		})

		const exec = await action.exec({
			nodes: [folder],
			view,
			folder: root,
			contents: [],
		})
		// Silent action
		expect(exec).toBe(null)
		expect(goToRouteMock).toBeCalledTimes(1)
		expect(goToRouteMock).toBeCalledWith(null, { fileid: '1', view: 'files' }, { dir: '/FooBar' })
	})

	test('Open folder fails without node', async () => {
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

	test('Open folder fails without Folder', async () => {
		const goToRouteMock = vi.fn()
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
			owner: 'admin',
			mime: 'text/plain',
			root: '/files/admin',
		})

		const root = new Folder({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/',
			owner: 'admin',
			root: '/files/admin',
		})

		const exec = await action.exec({
			nodes: [file],
			view,
			folder: root,
			contents: [],
		})
		expect(exec).toBe(false)
		expect(goToRouteMock).toBeCalledTimes(0)
	})
})
