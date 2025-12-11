/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { View } from '@nextcloud/files'

import { DefaultType, File, FileAction, Folder, Permission } from '@nextcloud/files'
import { describe, expect, test, vi } from 'vitest'
import { action } from './openInFilesAction.ts'

const view = {
	id: 'files',
	name: 'Files',
} as View

const systemTagsView = {
	id: 'tags',
	name: 'tags',
} as View

const validNode = new Folder({
	id: 1,
	source: 'https://cloud.domain.com/remote.php/dav/files/admin/foo',
	owner: 'admin',
	mime: 'httpd/unix-directory',
	root: '/files/admin',
	permissions: Permission.ALL,
})

const validTag = new Folder({
	id: 1,
	source: 'https://cloud.domain.com/remote.php/dav/systemtags/2',
	displayname: 'Foo',
	owner: 'admin',
	mime: 'httpd/unix-directory',
	root: '/systemtags',
	permissions: Permission.ALL,
	attributes: {
		'is-tag': true,
	},
})

describe('Open in files action conditions tests', () => {
	test('Default values', () => {
		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('systemtags:open-in-files')
		expect(action.displayName({
			nodes: [],
			view: systemTagsView,
			folder: {} as Folder,
			contents: [],
		})).toBe('Open in Files')
		expect(action.iconSvgInline({
			nodes: [],
			view: systemTagsView,
			folder: {} as Folder,
			contents: [],
		})).toBe('')
		expect(action.default).toBe(DefaultType.HIDDEN)
		expect(action.order).toBe(-1000)
		expect(action.inline).toBeUndefined()
	})
})

describe('Open in files action enabled tests', () => {
	test('Enabled with on valid view', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [validNode],
			view: systemTagsView,
			folder: {} as Folder,
			contents: [],
		})).toBe(true)
	})

	test('Disabled on wrong view', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [validNode],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})

	test('Disabled without nodes', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [],
			view: systemTagsView,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})

	test('Disabled with too many nodes', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [validNode, validNode],
			view: systemTagsView,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})

	test('Disabled with when node is a tag', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [validTag],
			view: systemTagsView,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})
})

describe('Open in files action execute tests', () => {
	test('Open in files', async () => {
		const goToRouteMock = vi.fn()
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			root: '/files/admin',
			permissions: Permission.ALL,
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
		expect(goToRouteMock).toBeCalledWith(null, { fileid: '1', view: 'files' }, { dir: '/Foo', openfile: 'true' })
	})

	test('Open in files with folder', async () => {
		const goToRouteMock = vi.fn()
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const file = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/Bar',
			owner: 'admin',
			root: '/files/admin',
			permissions: Permission.ALL,
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
		expect(goToRouteMock).toBeCalledWith(null, { fileid: '1', view: 'files' }, { dir: '/Foo/Bar', openfile: 'true' })
	})
})
