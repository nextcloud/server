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
import { action } from './enterCredentialsAction'
import { expect } from '@jest/globals'
import { File, Folder, Permission, View } from '@nextcloud/files'
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

describe('Enter credentials action conditions tests', () => {
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
		expect(action.id).toBe('credentials-external-storage')
		expect(action.displayName([storage], externalStorageView)).toBe('Enter missing credentials')
		expect(action.iconSvgInline([storage], externalStorageView)).toBe('<svg>SvgMock</svg>')
		expect(action.default).toBe(DefaultType.DEFAULT)
		expect(action.order).toBe(-1000)
		expect(action.inline!(storage, externalStorageView)).toBe(true)
	})
})

describe('Enter credentials action enabled tests', () => {
	const storage = new Folder({
		id: 1,
		source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
		owner: 'admin',
		root: '/files/admin',
		permissions: Permission.ALL,
		attributes: {
			scope: 'system',
			backend: 'SFTP',
			config: {
				status: STORAGE_STATUS.SUCCESS,
			} as StorageConfig,
		},
	})

	const userProvidedStorage = new Folder({
		id: 1,
		source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
		owner: 'admin',
		root: '/files/admin',
		permissions: Permission.ALL,
		attributes: {
			scope: 'system',
			backend: 'SFTP',
			config: {
				status: STORAGE_STATUS.INCOMPLETE_CONF,
				userProvided: true,
			} as StorageConfig,
		},
	})

	const globalAuthUserStorage = new Folder({
		id: 1,
		source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
		owner: 'admin',
		root: '/files/admin',
		permissions: Permission.ALL,
		attributes: {
			scope: 'system',
			backend: 'SFTP',
			config: {
				status: STORAGE_STATUS.INCOMPLETE_CONF,
				authMechanism: 'password::global::user',
			} as StorageConfig,
		},
	})

	const missingConfig = new Folder({
		id: 1,
		source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
		owner: 'admin',
		root: '/files/admin',
		permissions: Permission.ALL,
		attributes: {
			scope: 'system',
			backend: 'SFTP',
			config: {
			} as StorageConfig,
		},
	})

	const notAStorage = new File({
		id: 1,
		source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/test.txt',
		mime: 'text/plain',
		owner: 'admin',
		root: '/files/admin',
		permissions: Permission.ALL,
	})

	test('Disabled with on success storage', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!([storage], externalStorageView)).toBe(false)
	})

	test('Disabled for multiple nodes', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!([storage, storage], view)).toBe(false)
	})

	test('Enabled for missing user auth storage', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!([userProvidedStorage], view)).toBe(true)
	})

	test('Enabled for missing  global user auth storage', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!([globalAuthUserStorage], view)).toBe(true)
	})

	test('Disabled for missing config', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!([missingConfig], view)).toBe(false)
	})

	test('Disabled for normal nodes', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!([notAStorage], view)).toBe(false)
	})
})
