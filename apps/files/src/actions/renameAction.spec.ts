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
	const root = new Folder({
		owner: 'test',
		source: 'https://cloud.domain.com/remote.php/dav/files/admin/',
		id: 1,
		permissions: Permission.CREATE,
		root: '/files/admin',
	})
	const files = useFilesStore(getPinia())
	files.setRoot({ service: 'files', root })
})

describe('Rename action conditions tests', () => {
	test('Default values', () => {
		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('rename')
		expect(action.displayName({
			nodes: [],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe('Rename')
		expect(action.iconSvgInline({
			nodes: [],
			view,
			folder: {} as Folder,
			contents: [],
		})).toMatch(/<svg.+<\/svg>/)
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
			root: '/files/admin',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(true)
	})

	test('Disabled for node without DELETE permission', () => {
		const file = new File({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
			root: '/files/admin',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})

	test('Disabled if more than one node', () => {
		// @ts-expect-error mocking for tests
		window.OCA = { Files: { Sidebar: {} } }

		const file1 = new File({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foo.txt',
			owner: 'admin',
			mime: 'text/plain',
			root: '/files/admin',
		})
		const file2 = new File({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/bar.txt',
			owner: 'admin',
			mime: 'text/plain',
			root: '/files/admin',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [file1, file2],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
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
			root: '/files/admin',
		})

		const exec = await action.exec({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})

		// Silent action
		expect(exec).toBe(null)
		expect(eventBus.emit).toBeCalledTimes(1)
		expect(eventBus.emit).toHaveBeenCalledWith('files:node:rename', file)
	})
})
