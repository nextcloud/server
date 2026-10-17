/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder, INode } from '@nextcloud/files'

import { getCurrentUser } from '@nextcloud/auth'
import { File, Folder } from '@nextcloud/files'
import { logger } from '../utils/logger.ts'

const DB_VERSION = 1
const STORE_LISTINGS = 'listings'
const INDEX_CACHED_AT = 'cachedAt'

export const MAX_CACHED_LISTINGS = 200
export const MAX_CACHED_NODES = 2000

// Views whose contents only depend on the folder path. Search, recent,
// favorites and the sharing views list nodes another way.
const CACHED_VIEWS = new Set(['files', 'personal'])

interface CachedListing {
	folder: string
	contents: string[]
	cachedAt: number
}

export interface FolderListing {
	folder: IFolder
	contents: INode[]
}

let dbPromise: Promise<IDBDatabase> | undefined

/**
 * Open the cache database of the current user on first use.
 * One database per user, so a session expiring without a logout
 * never exposes a listing to the next user of the browser.
 */
function openDb(): Promise<IDBDatabase> {
	dbPromise ??= new Promise((resolve, reject) => {
		const uid = getCurrentUser()?.uid
		if (!uid || typeof indexedDB === 'undefined') {
			reject(new Error('Folder cache is not available'))
			return
		}

		const request = indexedDB.open(`nextcloud_files_folders_${uid}`, DB_VERSION)
		request.onupgradeneeded = () => {
			const store = request.result.createObjectStore(STORE_LISTINGS)
			store.createIndex(INDEX_CACHED_AT, 'cachedAt')
		}
		request.onsuccess = () => resolve(request.result)
		request.onerror = () => reject(request.error)
	})
	return dbPromise
}

/**
 * @param request - The request to wait for
 */
function promisify<T>(request: IDBRequest<T>): Promise<T> {
	return new Promise((resolve, reject) => {
		request.onsuccess = () => resolve(request.result)
		request.onerror = () => reject(request.error)
	})
}

/**
 * @param service - The files view id
 * @param path - The folder path within the view
 */
function listingKey(service: string, path: string): string {
	return `${service}:${path}`
}

/**
 * @param json - A node serialized with `toJSON()`
 */
function deserializeNode(json: string): INode {
	const args = JSON.parse(json) as ConstructorParameters<typeof File>
	return args[0].mime === 'httpd/unix-directory'
		? new Folder(...args)
		: new File(...args)
}

/**
 * Get the last known listing of a folder.
 *
 * @param service - The files view id
 * @param path - The folder path within the view
 * @return The cached listing, or undefined if none or unreadable
 */
export async function getCachedListing(service: string, path: string): Promise<FolderListing | undefined> {
	if (!CACHED_VIEWS.has(service)) {
		return undefined
	}

	try {
		const db = await openDb()
		const store = db.transaction(STORE_LISTINGS, 'readonly').objectStore(STORE_LISTINGS)
		const listing = await promisify<CachedListing | undefined>(store.get(listingKey(service, path)))
		if (!listing) {
			return undefined
		}
		return {
			folder: deserializeNode(listing.folder) as IFolder,
			contents: listing.contents.map(deserializeNode),
		}
	} catch (error) {
		logger.debug('Could not read folder listing from cache', { error, service, path })
		return undefined
	}
}

/**
 * Store the listing of a folder, evicting the oldest listings above the limit.
 * Folders with more than `MAX_CACHED_NODES` entries are not cached.
 *
 * @param service - The files view id
 * @param path - The folder path within the view
 * @param folder - The folder
 * @param contents - The folder contents
 */
export async function setCachedListing(service: string, path: string, folder: IFolder, contents: INode[]): Promise<void> {
	if (!CACHED_VIEWS.has(service)) {
		return
	}
	if (contents.length > MAX_CACHED_NODES) {
		return deleteCachedListing(service, path)
	}

	try {
		const db = await openDb()
		const transaction = db.transaction(STORE_LISTINGS, 'readwrite')
		const store = transaction.objectStore(STORE_LISTINGS)
		const listing: CachedListing = {
			folder: folder.toJSON(),
			contents: contents.map((node) => node.toJSON()),
			cachedAt: Date.now(),
		}
		store.put(listing, listingKey(service, path))

		const count = store.count()
		count.onsuccess = () => {
			let excess = count.result - MAX_CACHED_LISTINGS
			if (excess <= 0) {
				return
			}
			const cursors = store.index(INDEX_CACHED_AT).openCursor()
			cursors.onsuccess = () => {
				const cursor = cursors.result
				if (cursor && excess-- > 0) {
					cursor.delete()
					cursor.continue()
				}
			}
		}

		await new Promise<void>((resolve, reject) => {
			transaction.oncomplete = () => resolve()
			transaction.onerror = () => reject(transaction.error)
			transaction.onabort = () => reject(transaction.error)
		})
	} catch (error) {
		logger.debug('Could not write folder listing to cache', { error, service, path })
	}
}

/**
 * Forget the listing of a folder.
 *
 * @param service - The files view id
 * @param path - The folder path within the view
 */
export async function deleteCachedListing(service: string, path: string): Promise<void> {
	try {
		const db = await openDb()
		const store = db.transaction(STORE_LISTINGS, 'readwrite').objectStore(STORE_LISTINGS)
		await promisify(store.delete(listingKey(service, path)))
	} catch (error) {
		logger.debug('Could not delete folder listing from cache', { error, service, path })
	}
}
