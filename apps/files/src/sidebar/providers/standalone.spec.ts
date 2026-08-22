/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { File } from '@nextcloud/files'
import { beforeEach, describe, expect, test, vi } from 'vitest'
import { createStandaloneDataProvider } from './standalone.ts'

vi.mock('@nextcloud/auth')

const node = new File({
	id: 1,
	source: 'https://cloud.example.com/remote.php/dav/files/test/folder/file.txt',
	owner: 'test',
	mime: 'text/plain',
	root: '/files/test',
})

describe('Standalone sidebar data provider', () => {
	let provider: ReturnType<typeof createStandaloneDataProvider>

	beforeEach(() => {
		provider = createStandaloneDataProvider()
	})

	test('has no node, folder or view by default', () => {
		expect(provider.node.value).toBeUndefined()
		expect(provider.folder.value).toBeUndefined()
		expect(provider.view.value).toBeUndefined()
	})

	test('sets and resets the current node', () => {
		provider.setNode(node)
		expect(provider.node.value).toBe(node)

		provider.setNode()
		expect(provider.node.value).toBeUndefined()
	})

	test('never provides a folder or view', () => {
		provider.setNode(node)

		expect(provider.folder.value).toBeUndefined()
		expect(provider.view.value).toBeUndefined()
	})
})
