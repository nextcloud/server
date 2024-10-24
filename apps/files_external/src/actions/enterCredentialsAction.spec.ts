/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { StorageConfig } from '../services/externalStorage'

import { File, Folder, Permission, View, DefaultType, FileAction } from '@nextcloud/files'
import { describe, expect, test } from 'vitest'
import { action } from './enterCredentialsAction'
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
		expect(action.iconSvgInline([storage], externalStorageView)).toMatch(/<svg.+<\/svg>/)
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
