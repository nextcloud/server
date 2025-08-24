/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { File, Folder, Permission, View, FileAction } from '@nextcloud/files'
import { describe, expect, test } from 'vitest'
import { action } from './bulkSystemTagsAction'

const view = {
	id: 'files',
	name: 'Files',
} as View

describe('Manage tags action conditions tests', () => {
	test('Default values', () => {
		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('systemtags:bulk')
		expect(action.displayName([], view)).toBe('Manage tags')
		expect(action.iconSvgInline([], view)).toMatch(/<svg.+<\/svg>/)
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
		})

		const file2 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.UPDATE,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file1, file2], view)).toBe(false)
		expect(action.enabled!([file1], view)).toBe(false)
		expect(action.enabled!([file2], view)).toBe(true)
	})

	test('Disabled for non-dav ressources', () => {
		const file = new File({
			id: 1,
			source: 'https://domain.com/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(false)
	})

	test('Enabled for files outside the user root folder', () => {
		const file = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/trashbin/admin/trash/image.jpg.d1731053878',
			owner: 'admin',
			permissions: Permission.ALL,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(true)
	})
})
