/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeAll, beforeEach, describe, expect, it, vi } from 'vitest'
import { getContents } from './Search.ts'
import { Folder, Permission } from '@nextcloud/files'

const searchNodes = vi.hoisted(() => vi.fn())
vi.mock('./WebDavSearch.ts', () => ({ searchNodes }))
vi.mock('@nextcloud/auth')

describe('Search service', () => {
	const fakeFolder = new Folder({ owner: 'owner', source: 'https://cloud.example.com/remote.php/dav/files/owner/folder', root: '/files/owner' })

	beforeAll(() => {
		window.OCP ??= {}
		window.OCP.Files ??= {}
		window.OCP.Files.Router ??= { params: {}, query: {} }
		vi.spyOn(window.OCP.Files.Router, 'params', 'get').mockReturnValue({ view: 'files' })
	})

	beforeEach(() => {
		vi.restoreAllMocks()
		setActivePinia(createPinia())
	})

	it('rejects on error', async () => {
		searchNodes.mockImplementationOnce(() => { throw new Error('expected error') })
		expect(getContents).rejects.toThrow('expected error')
	})

	it('returns the search results and a fake root', async () => {
		searchNodes.mockImplementationOnce(() => [fakeFolder])
		const { contents, folder } = await getContents()

		expect(searchNodes).toHaveBeenCalledOnce()
		expect(contents).toHaveLength(1)
		expect(contents).toEqual([fakeFolder])
		// read only root
		expect(folder.permissions).toBe(Permission.READ)
	})

	it('can be cancelled', async () => {
		const { promise, resolve } = Promise.withResolvers<Event>()
		searchNodes.mockImplementationOnce(async (_, { signal }: { signal: AbortSignal}) => {
			signal.addEventListener('abort', resolve)
			await promise
			return []
		})

		const content = getContents()
		content.cancel()

		// its cancelled thus the promise returns the event
		const event = await promise
		expect(event.type).toBe('abort')
	})
})
