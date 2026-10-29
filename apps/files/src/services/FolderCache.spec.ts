/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { File, FileType, Folder } from '@nextcloud/files'
import { IDBFactory } from 'fake-indexeddb'
import { beforeEach, describe, expect, test, vi } from 'vitest'

const auth = vi.hoisted(() => ({ uid: 'test' as string | null }))
vi.mock('@nextcloud/auth', () => ({
	getCurrentUser: () => (auth.uid ? { uid: auth.uid, displayName: auth.uid, isAdmin: false } : null),
}))

const source = 'http://example.com/remote.php/dav/files/test'
const folder = new Folder({ owner: 'test', source: `${source}/folder`, id: 1, root: '/files/test' })
const subfolder = new Folder({ owner: 'test', source: `${source}/folder/sub`, id: 2, root: '/files/test' })
const file = new File({ owner: 'test', source: `${source}/folder/a.txt`, id: 3, mime: 'text/plain', size: 12, root: '/files/test' })

/**
 * Import a fresh module so each test opens its own database connection
 */
async function loadCache() {
	vi.resetModules()
	return await import('./FolderCache.ts')
}

describe('Folder cache', () => {
	beforeEach(() => {
		auth.uid = 'test'
		vi.stubGlobal('indexedDB', new IDBFactory())
	})

	test('returns nothing for an unknown folder', async () => {
		const cache = await loadCache()
		expect(await cache.getCachedListing('files', '/folder')).toBeUndefined()
	})

	test('restores the folder and its contents as nodes', async () => {
		const cache = await loadCache()
		await cache.setCachedListing('files', '/folder', folder, [subfolder, file])

		const listing = await cache.getCachedListing('files', '/folder')
		expect(listing!.folder.type).toBe(FileType.Folder)
		expect(listing!.folder.source).toBe(folder.source)
		expect(listing!.folder.isDavResource).toBe(true)
		expect(listing!.contents).toHaveLength(2)
		expect(listing!.contents[0]!.type).toBe(FileType.Folder)
		expect(listing!.contents[1]!.type).toBe(FileType.File)
		expect(listing!.contents[1]!.path).toBe('/folder/a.txt')
		expect(listing!.contents[1]!.size).toBe(12)
	})

	test('replaces a previous listing of the same folder', async () => {
		const cache = await loadCache()
		await cache.setCachedListing('files', '/folder', folder, [subfolder, file])
		await cache.setCachedListing('files', '/folder', folder, [file])

		const listing = await cache.getCachedListing('files', '/folder')
		expect(listing!.contents.map((node) => node.source)).toEqual([file.source])
	})

	test('keeps listings of different views apart', async () => {
		const cache = await loadCache()
		await cache.setCachedListing('files', '/folder', folder, [file])

		expect(await cache.getCachedListing('personal', '/folder')).toBeUndefined()
	})

	test('does not cache views that are not plain folders', async () => {
		const cache = await loadCache()
		await cache.setCachedListing('search', '/', folder, [file])
		await cache.setCachedListing('favorites', '/', folder, [file])

		expect(await cache.getCachedListing('search', '/')).toBeUndefined()
		expect(await cache.getCachedListing('favorites', '/')).toBeUndefined()
	})

	test('does not cache folders above the size limit and forgets their listing', async () => {
		const cache = await loadCache()
		const contents = Array.from({ length: cache.MAX_CACHED_NODES + 1 }, (_, i) => new File({
			owner: 'test',
			source: `${source}/folder/${i}.txt`,
			id: 10 + i,
			mime: 'text/plain',
			root: '/files/test',
		}))
		await cache.setCachedListing('files', '/folder', folder, [file])
		await cache.setCachedListing('files', '/folder', folder, contents)

		expect(await cache.getCachedListing('files', '/folder')).toBeUndefined()
	})

	test('forgets a deleted listing', async () => {
		const cache = await loadCache()
		await cache.setCachedListing('files', '/folder', folder, [file])
		await cache.deleteCachedListing('files', '/folder')

		expect(await cache.getCachedListing('files', '/folder')).toBeUndefined()
	})

	test('evicts the oldest listings above the limit', async () => {
		const cache = await loadCache()
		let now = 0
		vi.spyOn(Date, 'now').mockImplementation(() => ++now)

		for (let i = 0; i <= cache.MAX_CACHED_LISTINGS; i++) {
			await cache.setCachedListing('files', `/folder-${i}`, folder, [])
		}

		expect(await cache.getCachedListing('files', '/folder-0')).toBeUndefined()
		expect(await cache.getCachedListing('files', '/folder-1')).toBeDefined()
		expect(await cache.getCachedListing('files', `/folder-${cache.MAX_CACHED_LISTINGS}`)).toBeDefined()
	})

	test('does not share listings between users', async () => {
		const cacheOfTest = await loadCache()
		await cacheOfTest.setCachedListing('files', '/folder', folder, [file])

		auth.uid = 'other'
		const cacheOfOther = await loadCache()
		expect(await cacheOfOther.getCachedListing('files', '/folder')).toBeUndefined()
	})

	test('stays disabled without a logged in user', async () => {
		auth.uid = null
		const cache = await loadCache()
		await cache.setCachedListing('files', '/folder', folder, [file])

		expect(await cache.getCachedListing('files', '/folder')).toBeUndefined()
	})
})
