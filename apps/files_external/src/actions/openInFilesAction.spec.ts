/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import { action } from './openInFilesAction'
import { expect } from '@jest/globals'
import { Folder, Permission, View } from '@nextcloud/files'
import { DefaultType, FileAction } from '../../../files/src/services/FileAction'
import type { StorageConfig } from '../services/externalStorage'
import { STORAGE_STATUS } from '../utils/credentialsUtils'

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
					status: STORAGE_STATUS.SUCCESS,
				} as StorageConfig,
			},
		})

		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('open-in-files-external-storage')
		expect(action.displayName([storage], externalStorageView)).toBe('Open in files')
		expect(action.iconSvgInline([storage], externalStorageView)).toBe('')
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
					status: STORAGE_STATUS.ERROR,
				} as StorageConfig,
			},
		})
		expect(action.displayName([failingStorage], externalStorageView)).toBe('Examine this faulty external storage configuration')
	})
})

describe('Open in files action enabled tests', () => {
	test('Enabled with on valid view', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!([], externalStorageView)).toBe(true)
	})

	test('Disabled on wrong view', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!([], view)).toBe(false)
	})
})

describe('Open in files action execute tests', () => {
	test('Open in files', async () => {
		const goToRouteMock = jest.fn()
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const storage = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/Bar',
			owner: 'admin',
			root: '/files/admin',
			permissions: Permission.ALL,
			attributes: {
				config: {
					status: STORAGE_STATUS.SUCCESS,
				} as StorageConfig,
			},
		})

		const exec = await action.exec(storage, externalStorageView, '/')
		// Silent action
		expect(exec).toBe(null)
		expect(goToRouteMock).toBeCalledTimes(1)
		expect(goToRouteMock).toBeCalledWith(null, { view: 'files' }, { dir: '/Foo/Bar' })
	})

	test('Open in files broken storage', async () => {
		const confirmMock = jest.fn()
		window.OC = { dialogs: { confirm: confirmMock } }

		const storage = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/Bar',
			owner: 'admin',
			root: '/files/admin',
			permissions: Permission.ALL,
			attributes: {
				config: {
					status: STORAGE_STATUS.ERROR,
				} as StorageConfig,
			},
		})

		const exec = await action.exec(storage, externalStorageView, '/')
		// Silent action
		expect(exec).toBe(null)
		expect(confirmMock).toBeCalledTimes(1)
	})
})
