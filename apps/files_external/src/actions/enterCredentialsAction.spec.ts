/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { View } from '@nextcloud/files'
import type { StorageConfig } from '../services/externalStorage.ts'

import { DefaultType, File, FileAction, Folder, Permission } from '@nextcloud/files'
import { describe, expect, test } from 'vitest'
import { STORAGE_STATUS } from '../utils/credentialsUtils.ts'
import { action } from './enterCredentialsAction.ts'

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
		expect(action.displayName({
			view: externalStorageView,
			nodes: [storage],
			folder: {} as Folder,
			contents: [],
		})).toBe('Enter missing credentials')
		expect(action.iconSvgInline({
			view: externalStorageView,
			nodes: [storage],
			folder: {} as Folder,
			contents: [],
		})).toMatch(/<svg.+<\/svg>/)
		expect(action.default).toBe(DefaultType.DEFAULT)
		expect(action.order).toBe(-1000)
		expect(action.inline!({
			view: externalStorageView,
			nodes: [storage],
			folder: {} as Folder,
			contents: [],
		})).toBe(true)
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
		expect(action.enabled!({
			nodes: [storage],
			view: externalStorageView,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})

	test('Disabled for multiple nodes', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [storage, storage],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})

	test('Enabled for missing user auth storage', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [userProvidedStorage],
			view: externalStorageView,
			folder: {} as Folder,
			contents: [],
		})).toBe(true)
	})

	test('Enabled for missing  global user auth storage', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [globalAuthUserStorage],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(true)
	})

	test('Disabled for missing config', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [missingConfig],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})

	test('Disabled for normal nodes', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [notAStorage],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})
})
