/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { action } from './renameAction'
import { File, Permission, View, FileAction } from '@nextcloud/files'
import * as eventBus from '@nextcloud/event-bus'
import { describe, expect, test, vi } from 'vitest'

const view = {
	id: 'files',
	name: 'Files',
} as View

describe('Rename action conditions tests', () => {
	test('Default values', () => {
		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('rename')
		expect(action.displayName([], view)).toBe('Rename')
		expect(action.iconSvgInline([], view)).toMatch(/<svg.+<\/svg>/)
		expect(action.default).toBeUndefined()
		expect(action.order).toBe(10)
	})
})

describe('Rename action enabled tests', () => {
	test('Enabled for node with UPDATE permission', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.UPDATE | Permission.DELETE,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(true)
	})

	test('Disabled for node without DELETE permission', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(false)
	})

	test('Disabled if more than one node', () => {
		window.OCA = { Files: { Sidebar: {} } }

		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foo.txt',
			owner: 'admin',
			mime: 'text/plain',
		})
		const file2 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/bar.txt',
			owner: 'admin',
			mime: 'text/plain',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file1, file2], view)).toBe(false)
	})
})

describe('Rename action exec tests', () => {
	test('Rename', async () => {
		vi.spyOn(eventBus, 'emit')

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
		})

		const exec = await action.exec(file, view, '/')

		// Silent action
		expect(exec).toBe(null)
		expect(eventBus.emit).toBeCalledTimes(1)
		expect(eventBus.emit).toHaveBeenCalledWith('files:node:rename', file)
	})
})
