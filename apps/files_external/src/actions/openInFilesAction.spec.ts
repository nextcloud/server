/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { View } from '@nextcloud/files'
import type { IStorage } from '../types.ts'

import * as dialogs from '@nextcloud/dialogs'
import { DefaultType, FileAction, Folder, Permission } from '@nextcloud/files'
import { describe, expect, test, vi } from 'vitest'
import { StorageStatus } from '../types.ts'
import { action } from './openInFilesAction.ts'

vi.mock('@nextcloud/dialogs', { spy: true })

const view = {
	id: 'files',
	name: 'Files',
} as View

const externalStorageView = {
	id: 'extstoragemounts',
	name: 'External storage',
} as View

describe('Open in files action conditions tests', () => {
	test('Default values', () => {
		const storage = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
			owner: 'admin',
			root: '/files/admin',
			permissions: Permission.ALL,
			attributes: {
				config: {
					status: StorageStatus.Success,
				} as IStorage,
			},
		})

		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('open-in-files-external-storage')
		expect(action.displayName({
			nodes: [storage],
			view: externalStorageView,
			folder: {} as Folder,
			contents: [],
		})).toBe('Open in Files')
		expect(action.iconSvgInline({
			nodes: [storage],
			view: externalStorageView,
			folder: {} as Folder,
			contents: [],
		})).toBe('')
		expect(action.default).toBe(DefaultType.HIDDEN)
		expect(action.order).toBe(-1000)
		expect(action.inline).toBeUndefined()
	})

	test('Default values', () => {
		const failingStorage = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
			owner: 'admin',
			root: '/files/admin',
			permissions: Permission.ALL,
			attributes: {
				config: {
					status: StorageStatus.Error,
				} as IStorage,
			},
		})
		expect(action.displayName({
			nodes: [failingStorage],
			view: externalStorageView,
			folder: {} as Folder,
			contents: [],
		})).toBe('Examine this faulty external storage configuration')
	})
})

describe('Open in files action enabled tests', () => {
	test('Enabled with on valid view', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [],
			view: externalStorageView,
			folder: {} as Folder,
			contents: [],
		})).toBe(true)
	})

	test('Disabled on wrong view', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})
})

describe('Open in files action execute tests', () => {
	test('Open in files', async () => {
		const goToRouteMock = vi.fn()
		// @ts-expect-error - mocking for tests
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const storage = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/Bar',
			owner: 'admin',
			root: '/files/admin',
			permissions: Permission.ALL,
			attributes: {
				config: {
					status: StorageStatus.Success,
				} as IStorage,
			},
		})

		const exec = await action.exec({
			nodes: [storage],
			view: externalStorageView,
			folder: {} as Folder,
			contents: [],
		})
		// Silent action
		expect(exec).toBe(null)
		expect(goToRouteMock).toBeCalledTimes(1)
		expect(goToRouteMock).toBeCalledWith(null, { view: 'files' }, { dir: '/Foo/Bar' })
	})

	test('Open in files broken storage', async () => {
		// @ts-expect-error - spy added by vitest
		dialogs.showConfirmation.mockImplementationOnce(() => Promise.resolve(true))

		const storage = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/Bar',
			owner: 'admin',
			root: '/files/admin',
			permissions: Permission.ALL,
			attributes: {
				config: {
					status: StorageStatus.Error,
				} as IStorage,
			},
		})

		const exec = await action.exec({
			nodes: [storage],
			view: externalStorageView,
			folder: {} as Folder,
			contents: [],
		})
		// Silent action
		expect(exec).toBe(null)
		expect(dialogs.showConfirmation).toHaveBeenCalledOnce()
	})
})
