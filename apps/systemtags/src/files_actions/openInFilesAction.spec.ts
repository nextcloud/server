/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { action } from './openInFilesAction'
import { describe, expect, test, vi } from 'vitest'
import { File, Folder, Permission, View, DefaultType, FileAction } from '@nextcloud/files'

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
		expect(action.displayName([], systemTagsView)).toBe('Open in Files')
		expect(action.iconSvgInline([], systemTagsView)).toBe('')
		expect(action.default).toBe(DefaultType.HIDDEN)
		expect(action.order).toBe(-1000)
		expect(action.inline).toBeUndefined()
	})
})

describe('Open in files action enabled tests', () => {
	test('Enabled with on valid view', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!([validNode], systemTagsView)).toBe(true)
	})

	test('Disabled on wrong view', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!([validNode], view)).toBe(false)
	})

	test('Disabled without nodes', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!([], view)).toBe(false)
	})

	test('Disabled with too many nodes', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!([validNode, validNode], view)).toBe(false)
	})

	test('Disabled with when node is a tag', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!([validTag], view)).toBe(false)
	})
})

describe('Open in files action execute tests', () => {
	test('Open in files', async () => {
		const goToRouteMock = vi.fn()
		// @ts-expect-error We only mock what needed, we do not need Files.Router.goTo or Files.Navigation
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			root: '/files/admin',
			permissions: Permission.ALL,
		})

		const exec = await action.exec(file, view, '/')

		// Silent action
		expect(exec).toBe(null)
		expect(goToRouteMock).toBeCalledTimes(1)
		expect(goToRouteMock).toBeCalledWith(null, { fileid: '1', view: 'files' }, { dir: '/Foo', openfile: 'true' })
	})

	test('Open in files with folder', async () => {
		const goToRouteMock = vi.fn()
		// @ts-expect-error We only mock what needed, we do not need Files.Router.goTo or Files.Navigation
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const file = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/Bar',
			owner: 'admin',
			root: '/files/admin',
			permissions: Permission.ALL,
		})

		const exec = await action.exec(file, view, '/')

		// Silent action
		expect(exec).toBe(null)
		expect(goToRouteMock).toBeCalledTimes(1)
		expect(goToRouteMock).toBeCalledWith(null, { fileid: '1', view: 'files' }, { dir: '/Foo/Bar', openfile: 'true' })
	})
})
