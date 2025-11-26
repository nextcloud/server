/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { View } from '@nextcloud/files'

import * as eventBus from '@nextcloud/event-bus'
import { File, FileAction, Folder, Permission } from '@nextcloud/files'
import { beforeEach, describe, expect, test, vi } from 'vitest'
import { useFilesStore } from '../store/files.ts'
import { getPinia } from '../store/index.ts'
import { action } from './renameAction.ts'

const view = {
	id: 'files',
	name: 'Files',
} as View

beforeEach(() => {
	const root = new Folder({ owner: 'test', source: 'https://cloud.domain.com/remote.php/dav/files/admin/', id: 1, permissions: Permission.CREATE })
	const files = useFilesStore(getPinia())
	files.setRoot({ service: 'files', root })
})

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
			id: 2,
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
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(false)
	})

	test('Disabled if more than one node', () => {
		// @ts-expect-error mocking for tests
		window.OCA = { Files: { Sidebar: {} } }

		const file1 = new File({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foo.txt',
			owner: 'admin',
			mime: 'text/plain',
		})
		const file2 = new File({
			id: 2,
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
			id: 2,
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
