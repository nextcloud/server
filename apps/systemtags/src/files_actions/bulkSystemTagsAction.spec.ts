/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { View } from '@nextcloud/files'

import { File, FileAction, Folder, Permission } from '@nextcloud/files'
import { describe, expect, test } from 'vitest'
import { action } from './bulkSystemTagsAction.ts'

const view = {
	id: 'files',
	name: 'Files',
} as View

describe('Manage tags action conditions tests', () => {
	test('Default values', () => {
		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('systemtags:bulk')
		expect(action.displayName({
			nodes: [],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe('Manage tags')
		expect(action.iconSvgInline({
			nodes: [],
			view,
			folder: {} as Folder,
			contents: [],
		})).toMatch(/<svg.+<\/svg>/)
		expect(action.default).toBeUndefined()
		expect(action.order).toBe(undefined)
		expect(action.enabled).toBeDefined()
	})
})

describe('Manage tags action enabled tests', () => {
	test('Disabled without permissions', () => {
		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.NONE,
			root: '/files/admin',
		})

		const file2 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.UPDATE,
			root: '/files/admin',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [file1, file2],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
		expect(action.enabled!({
			nodes: [file1],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
		expect(action.enabled!({
			nodes: [file2],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(true)
	})

	test('Disabled for non-dav ressources', () => {
		const file = new File({
			id: 1,
			source: 'https://domain.com/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			root: '/',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})

	test('Enabled for files outside the user root folder', () => {
		const file = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/trashbin/admin/trash/image.jpg.d1731053878',
			owner: 'admin',
			permissions: Permission.ALL,
			root: '/trashbin/admin',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(true)
	})
})
